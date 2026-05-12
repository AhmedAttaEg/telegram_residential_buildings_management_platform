<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = \App\Models\User::query()
            ->with(['tenant', 'roles.permissions'])
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return $this->apiError('The provided credentials are incorrect.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $user->isActive()) {
            return $this->apiError('User account is inactive.', Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? 'mobile',
            $user->permissionSlugs()->all(),
            now()->addMinutes((int) config('sanctum.expiration')),
        );

        return $this->apiSuccess([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user->toAuthSummary(),
            'tenant' => $user->tenant?->only(['id', 'name', 'slug', 'status']),
            'roles' => $user->roles->pluck('slug')->values(),
        ], 'Authenticated successfully.');
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->apiSuccess(null, 'Logged out successfully.');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['tenant', 'roles.permissions']);

        return $this->apiSuccess([
            'user' => $user->toAuthSummary(),
            'tenant' => $user->tenant?->only(['id', 'name', 'slug', 'status']),
            'roles' => $user->roles->pluck('slug')->values(),
            'permissions' => $user->permissionSlugs(),
        ]);
    }
}
