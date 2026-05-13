<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MobileAuthService
{
    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function login(array $credentials, ?string $ipAddress = null): array
    {
        $user = User::query()
            ->withAuthContext()
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check((string) $credentials['password'], $user->password)) {
            throw new HttpException(422, 'The provided credentials are incorrect.');
        }

        if (! $user->isActive()) {
            throw new HttpException(403, 'User account is inactive.');
        }

        return $this->issueToken($user, $credentials, $ipAddress);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function refresh(
        User $user,
        ?PersonalAccessToken $currentToken,
        array $attributes,
        ?string $ipAddress = null,
    ): array {
        if (! $currentToken instanceof PersonalAccessToken) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        return DB::transaction(function () use ($user, $currentToken, $attributes, $ipAddress): array {
            $payload = [
                'device_name' => $attributes['device_name'] ?? $currentToken->device_name ?? $currentToken->name,
                'device_platform' => $attributes['device_platform'] ?? $currentToken->device_platform,
                'app_version' => $attributes['app_version'] ?? $currentToken->app_version,
                'push_token' => array_key_exists('push_token', $attributes)
                    ? $attributes['push_token']
                    : $currentToken->push_token,
            ];

            $result = $this->issueToken($user->loadMissing(['tenant', 'roles.permissions']), $payload, $ipAddress, $currentToken->abilities ?? []);

            $currentToken->delete();

            return $result;
        });
    }

    public function logoutCurrentDevice(?PersonalAccessToken $currentToken): void
    {
        $currentToken?->delete();
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $abilities
     * @return array<string, mixed>
     */
    private function issueToken(
        User $user,
        array $attributes,
        ?string $ipAddress = null,
        array $abilities = [],
    ): array {
        $token = $user->createToken(
            (string) $attributes['device_name'],
            $abilities !== [] ? $abilities : $user->permissionSlugs()->all(),
            now()->addMinutes((int) config('sanctum.expiration')),
        );

        $accessToken = $token->accessToken;
        $accessToken->forceFill([
            'device_name' => (string) $attributes['device_name'],
            'device_platform' => $attributes['device_platform'] ?? null,
            'app_version' => $attributes['app_version'] ?? null,
            'push_token' => $attributes['push_token'] ?? null,
            'last_used_ip' => $ipAddress,
            'last_used_at' => now(),
        ])->save();

        return $this->payload($user, $token);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user, NewAccessToken $token): array
    {
        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken->fresh();

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessToken->expires_at?->toISOString(),
            'user' => $user->toAuthSummary(),
            'tenant' => $user->tenant?->only(['id', 'name', 'slug', 'status']),
            'roles' => $user->roleSlugs(),
            'permissions' => $user->permissionSlugs(),
            'device' => [
                'token_id' => $accessToken->id,
                'device_name' => $accessToken->device_name,
                'device_platform' => $accessToken->device_platform,
                'app_version' => $accessToken->app_version,
                'push_token' => $accessToken->push_token,
                'last_used_ip' => $accessToken->last_used_ip,
                'last_used_at' => $accessToken->last_used_at?->toISOString(),
            ],
        ];
    }
}
