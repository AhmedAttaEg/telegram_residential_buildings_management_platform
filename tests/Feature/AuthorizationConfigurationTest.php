<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_users_table_supports_multi_tenant_roles(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'tenant_id',
            'status',
            'preferred_locale',
        ]));
    }

    public function test_default_roles_are_seeded(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => 'platform_owner']);
        $this->assertDatabaseHas('roles', ['slug' => 'tenant_owner']);
        $this->assertDatabaseHas('roles', ['slug' => 'accountant']);
        $this->assertDatabaseHas('roles', ['slug' => 'maintenance']);
        $this->assertDatabaseHas('roles', ['slug' => 'resident']);
    }

    public function test_platform_owner_route_requires_the_platform_owner_role(): void
    {
        $owner = User::factory()->create();
        $owner->roles()->attach(Role::query()->where('slug', 'platform_owner')->value('id'));

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/owner/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'platform_owner');

        $tenantUser = User::factory()->forTenant(Tenant::factory()->create())->create();
        $tenantUser->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($tenantUser);

        $this->getJson('/api/v1/owner/dashboard')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Platform owner access is required.');
    }

    public function test_permission_middleware_allows_accounting_access(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'accountant')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/accounting/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.permission', 'accounting.access');
    }

    public function test_cross_tenant_access_is_blocked_for_authenticated_users(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'maintenance')->value('id'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/t/{$otherTenant->slug}/health")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cross-tenant access is not allowed.');
    }

    public function test_platform_owner_cannot_use_resident_portal_routes_without_resident_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create();
        $owner->roles()->attach(Role::query()->where('slug', 'platform_owner')->value('id'));

        Sanctum::actingAs($owner->load('roles.permissions'));

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/999999/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resident profile is required.');
    }

    public function test_tenant_permission_route_requires_assigned_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $maintenanceUser = User::factory()->forTenant($tenant)->create();
        $maintenanceUser->roles()->attach(Role::query()->where('slug', 'maintenance')->value('id'));

        Sanctum::actingAs($maintenanceUser);

        $this->getJson("/api/v1/t/{$tenant->slug}/maintenance")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.feature', 'maintenance');

        $residentUser = User::factory()->forTenant($tenant)->create();
        $residentUser->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($residentUser);

        $this->getJson("/api/v1/t/{$tenant->slug}/maintenance")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Permission [maintenance.access] is required.');
    }

    public function test_accounting_dashboard_requires_accounting_permission_even_for_authenticated_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $residentUser = User::factory()->forTenant($tenant)->create();
        $residentUser->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($residentUser->load('roles.permissions'));

        $this->getJson('/api/v1/accounting/dashboard')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Permission [accounting.access] is required.');
    }
}
