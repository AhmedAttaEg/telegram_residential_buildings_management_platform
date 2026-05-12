<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformOwnerAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_only_platform_owners_can_access_owner_routes(): void
    {
        $tenantUser = User::factory()->forTenant(Tenant::factory()->create())->create();
        $tenantUser->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        Sanctum::actingAs($tenantUser);

        $this->getJson('/api/v1/owner/tenants')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Platform owner access is required.');

        $platformOwner = User::factory()->create();
        $platformOwner->roles()->attach(Role::query()->where('slug', 'platform_owner')->value('id'));

        Sanctum::actingAs($platformOwner);

        $this->getJson('/api/v1/owner/tenants')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_tenant_bound_platform_owner_assignment_is_blocked_from_owner_routes(): void
    {
        $tenantUser = User::factory()->forTenant(Tenant::factory()->create())->create();
        $tenantUser->roles()->attach(Role::query()->where('slug', 'platform_owner')->value('id'));

        Sanctum::actingAs($tenantUser);

        $this->getJson('/api/v1/owner/tenants')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant-bound users cannot access owner administration.');
    }

    public function test_platform_owner_can_create_list_filter_update_and_delete_tenants(): void
    {
        $owner = $this->actingAsPlatformOwner();

        Tenant::factory()->create([
            'name' => 'Alpha Towers',
            'slug' => 'alpha-towers',
            'subscription_status' => 'active',
            'subscription_plan' => 'annual',
        ]);
        Tenant::factory()->create([
            'name' => 'Beta Homes',
            'slug' => 'beta-homes',
            'status' => 'suspended',
            'subscription_status' => 'suspended',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/owner/tenants?status=active&search=alpha&per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'alpha-towers');

        $this->postJson('/api/v1/owner/tenants', [
            'name' => 'Gamma Residences',
            'slug' => 'gamma-residences',
            'subscription_status' => 'trial',
            'subscription_plan' => 'monthly',
            'feature_flags' => [
                'maintenance' => false,
            ],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'gamma-residences');

        $this->patchJson('/api/v1/owner/tenants/gamma-residences', [
            'brand_name' => 'Gamma Admin',
            'primary_color' => '#123456',
        ])->assertOk()
            ->assertJsonPath('data.brand_name', 'Gamma Admin')
            ->assertJsonPath('data.primary_color', '#123456');

        $this->deleteJson('/api/v1/owner/tenants/gamma-residences')
            ->assertNoContent();
    }

    public function test_platform_owner_can_update_tenant_status_and_send_reminders(): void
    {
        CarbonImmutable::setTestNow('2026-05-07 12:00:00');

        $owner = $this->actingAsPlatformOwner();
        $tenant = Tenant::factory()->create([
            'subscription_status' => 'active',
        ]);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/owner/tenants/{$tenant->slug}/status", [
            'action' => 'grace',
            'reason' => 'Invoice overdue',
            'grace_ends_at' => '2026-05-10 12:00:00',
        ])->assertOk()
            ->assertJsonPath('data.subscription_status', 'grace')
            ->assertJsonPath('data.suspension_reason', 'Invoice overdue');

        $this->patchJson("/api/v1/owner/tenants/{$tenant->slug}/status", [
            'action' => 'remind',
        ])->assertOk()
            ->assertJsonPath('data.reminder_sent_at', now()->utc()->format('Y-m-d\TH:i:s.000000\Z'));

        $this->patchJson("/api/v1/owner/tenants/{$tenant->slug}/status", [
            'action' => 'suspend',
            'reason' => 'Grace expired',
        ])->assertOk()
            ->assertJsonPath('data.status', 'suspended')
            ->assertJsonPath('data.subscription_status', 'suspended');

        CarbonImmutable::setTestNow();
    }

    public function test_grace_period_keeps_tenant_accessible_until_expiry_and_suspension_blocks_it(): void
    {
        $owner = $this->actingAsPlatformOwner();
        $tenant = Tenant::factory()->create([
            'subscription_status' => 'active',
        ]);
        $tenantUser = User::factory()->forTenant($tenant)->create();
        $tenantUser->roles()->attach(Role::query()->where('slug', 'resident')->value('id'));

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/owner/tenants/{$tenant->slug}/status", [
            'action' => 'grace',
            'grace_ends_at' => now()->addDay()->toISOString(),
        ])->assertOk();

        Sanctum::actingAs($tenantUser);

        $this->getJson("/api/v1/t/{$tenant->slug}/health")
            ->assertOk()
            ->assertJsonPath('success', true);

        $tenant->update([
            'grace_ends_at' => now()->subMinute(),
        ]);

        $this->getJson("/api/v1/t/{$tenant->slug}/health")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant subscription is inactive.');

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/owner/tenants/{$tenant->slug}/status", [
            'action' => 'suspend',
        ])->assertOk();

        Sanctum::actingAs($tenantUser);

        $this->getJson("/api/v1/t/{$tenant->slug}/health")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tenant is suspended.');
    }

    private function actingAsPlatformOwner(): User
    {
        $owner = User::factory()->create();
        $owner->roles()->attach(Role::query()->where('slug', 'platform_owner')->value('id'));

        return $owner;
    }
}
