<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerWebAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_platform_owner_can_manage_tenants_from_web(): void
    {
        $owner = $this->platformOwner();

        $this->actingAs($owner)
            ->post(route('owner.tenants.store'), [
                'name' => 'Gamma Residences',
                'slug' => 'gamma-residences',
                'status' => 'active',
                'subscription_status' => 'trial',
                'subscription_plan' => 'monthly',
                'brand_name' => 'Gamma Brand',
                'primary_color' => '#123456',
                'feature_flags' => [
                    'maintenance' => '1',
                    'resident_app' => '1',
                ],
            ])->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'gamma-residences')->firstOrFail();

        $this->actingAs($owner)
            ->put(route('owner.tenants.update', $tenant), [
                'name' => 'Gamma Residences Updated',
                'slug' => 'gamma-residences',
                'subscription_plan' => 'annual',
                'brand_name' => 'Gamma Admin',
                'primary_color' => '#654321',
                'feature_flags' => [
                    'maintenance' => '1',
                ],
            ])->assertRedirect(route('owner.tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Gamma Residences Updated',
            'subscription_plan' => 'annual',
            'brand_name' => 'Gamma Admin',
            'primary_color' => '#654321',
        ]);

        $this->actingAs($owner)
            ->patch(route('owner.tenants.status', $tenant), [
                'action' => 'suspend',
                'reason' => 'Invoice overdue',
            ])->assertRedirect(route('owner.tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
            'subscription_status' => 'suspended',
        ]);
    }

    public function test_platform_owner_can_manage_subscription_plans_from_web(): void
    {
        $owner = $this->platformOwner();

        $this->actingAs($owner)
            ->post(route('owner.subscription-plans.store'), [
                'name' => 'Premium',
                'slug' => 'premium',
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'price_amount' => '499.99',
                'currency' => 'EGP',
                'trial_days' => 14,
                'description' => 'Premium plan',
                'feature_limits_json' => '{"users":50}',
            ])->assertRedirect();

        $plan = SubscriptionPlan::query()->where('slug', 'premium')->firstOrFail();

        $this->actingAs($owner)
            ->put(route('owner.subscription-plans.update', $plan), [
                'name' => 'Premium Plus',
                'slug' => 'premium',
                'status' => 'active',
                'billing_cycle' => 'annual',
                'price_amount' => '4999.99',
                'currency' => 'EGP',
                'trial_days' => 21,
                'description' => 'Premium plus plan',
                'feature_limits_json' => '{"users":100}',
            ])->assertRedirect(route('owner.subscription-plans.show', $plan));

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Premium Plus',
            'billing_cycle' => 'annual',
        ]);
    }

    public function test_platform_owner_can_view_audit_logs_and_system_health(): void
    {
        $owner = $this->platformOwner();
        $tenant = Tenant::factory()->create();
        $tenant->update([
            'brand_name' => 'Audited Brand',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit logs');

        $auditLog = \App\Models\AuditLog::query()->latest('id')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('owner.audit-logs.show', $auditLog))
            ->assertOk()
            ->assertSee($auditLog->event);

        $this->actingAs($owner)
            ->get(route('owner.system-health'))
            ->assertOk()
            ->assertSee('System health');
    }

    public function test_tenant_user_cannot_access_owner_web_routes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($this->roleId('tenant_owner'));

        $this->actingAs($user)
            ->get(route('owner.tenants.index'))
            ->assertForbidden();
    }

    private function platformOwner(): User
    {
        $owner = User::factory()->create();
        $owner->roles()->attach($this->roleId('platform_owner'));

        return $owner;
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->value('id');
    }
}
