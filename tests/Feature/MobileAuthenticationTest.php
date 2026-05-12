<?php

namespace Tests\Feature;

use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_mobile_login_returns_token_metadata_and_persists_device_tracking(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create([
            'email' => 'mobile@example.com',
        ]);
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'mobile@example.com',
            'password' => 'password',
            'device_name' => 'Pixel 9',
            'device_platform' => 'android',
            'app_version' => '1.2.3',
            'push_token' => 'push-token-1',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'mobile@example.com')
            ->assertJsonPath('data.device.device_name', 'Pixel 9')
            ->assertJsonPath('data.device.device_platform', 'android')
            ->assertJsonPath('data.device.app_version', '1.2.3')
            ->assertJsonPath('data.device.push_token', 'push-token-1');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'Pixel 9',
            'device_name' => 'Pixel 9',
            'device_platform' => 'android',
            'app_version' => '1.2.3',
            'push_token' => 'push-token-1',
        ]);
    }

    public function test_mobile_refresh_rotates_the_current_token_and_preserves_device_context(): void
    {
        $user = $this->createAuthenticatedMobileUser();

        $login = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone 16',
            'device_platform' => 'ios',
            'app_version' => '2.0.0',
            'push_token' => 'push-old',
        ])->assertOk();

        $oldPlainTextToken = (string) $login->json('data.token');
        $oldTokenId = (int) $login->json('data.device.token_id');

        $refresh = $this->withToken($oldPlainTextToken)
            ->postJson('/api/v1/mobile/auth/refresh', [
                'app_version' => '2.0.1',
                'push_token' => 'push-new',
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device.device_name', 'iPhone 16')
            ->assertJsonPath('data.device.device_platform', 'ios')
            ->assertJsonPath('data.device.app_version', '2.0.1')
            ->assertJsonPath('data.device.push_token', 'push-new');

        $newPlainTextToken = (string) $refresh->json('data.token');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $oldTokenId,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotSame($oldPlainTextToken, $newPlainTextToken);

        $newToken = PersonalAccessToken::query()->sole();
        $this->assertSame('iPhone 16', $newToken->device_name);
        $this->assertSame('ios', $newToken->device_platform);
        $this->assertSame('2.0.1', $newToken->app_version);
        $this->assertSame('push-new', $newToken->push_token);

        $this->withToken($newPlainTextToken)
            ->postJson('/api/v1/mobile/auth/logout')
            ->assertOk();
    }

    public function test_mobile_logout_revokes_only_the_current_device_token(): void
    {
        $user = $this->createAuthenticatedMobileUser();

        $firstToken = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Galaxy',
            'device_platform' => 'android',
            'app_version' => '3.0.0',
        ])->json('data.token');

        $secondToken = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPad',
            'device_platform' => 'ios',
            'app_version' => '3.0.0',
        ])->json('data.token');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->withToken($firstToken)
            ->postJson('/api/v1/mobile/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($secondToken)
            ->postJson('/api/v1/mobile/auth/logout-all')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out from all devices successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_mobile_auth_validates_inputs_and_blocks_inactive_users(): void
    {
        $this->postJson('/api/v1/mobile/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'errors' => ['email', 'password', 'device_name', 'device_platform', 'app_version'],
            ]);

        User::factory()->create([
            'email' => 'wrong-mobile@example.com',
        ]);

        $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'wrong-mobile@example.com',
            'password' => 'invalid-password',
            'device_name' => 'Android',
            'device_platform' => 'android',
            'app_version' => '1.0.0',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        $user = User::factory()->inactive()->create([
            'email' => 'inactive-mobile@example.com',
        ]);
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'inactive-mobile@example.com',
            'password' => 'password',
            'device_name' => 'Android',
            'device_platform' => 'android',
            'app_version' => '1.0.0',
        ])->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'User account is inactive.');
    }

    private function createAuthenticatedMobileUser(): User
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        return $user;
    }
}
