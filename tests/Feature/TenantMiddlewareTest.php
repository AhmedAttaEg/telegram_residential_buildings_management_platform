<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_valid_tenant_slug_resolves_and_attaches_tenant_context(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$tenant->slug}/health")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.tenant', $tenant->slug)
            ->assertJsonPath('data.context_tenant', $tenant->slug)
            ->assertJsonPath('data.request_tenant', $tenant->slug);
    }

    public function test_missing_tenant_slug_returns_not_found(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/t/missing-tenant/health')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant not found.');
    }

    public function test_suspended_tenant_is_blocked(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$tenant->slug}/health")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant is suspended.');
    }

    public function test_expired_subscription_is_blocked(): void
    {
        $tenant = Tenant::factory()->expired()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$tenant->slug}/health")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant subscription is inactive.');
    }

    public function test_enabled_feature_allows_access(): void
    {
        $tenant = Tenant::factory()->withFeature('maintenance', true)->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'maintenance')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$tenant->slug}/maintenance")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.feature', 'maintenance')
            ->assertJsonPath('data.tenant', $tenant->slug);
    }

    public function test_disabled_feature_blocks_access(): void
    {
        $tenant = Tenant::factory()->withFeature('ai_features', false)->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$tenant->slug}/ai")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant feature [ai_features] is disabled.');
    }

    public function test_missing_stored_feature_flag_falls_back_to_config_default(): void
    {
        $tenant = Tenant::factory()->create([
            'feature_flags' => [
                'maintenance' => true,
            ],
        ]);
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'maintenance')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$tenant->slug}/maintenance")
            ->assertOk();
    }
}
