<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_table_contains_lifecycle_feature_and_branding_columns(): void
    {
        $this->assertTrue(Schema::hasTable('tenants'));
        $this->assertTrue(Schema::hasColumns('tenants', [
            'name',
            'slug',
            'status',
            'subscription_status',
            'subscription_plan',
            'trial_ends_at',
            'grace_ends_at',
            'subscription_ends_at',
            'suspended_at',
            'feature_flags',
            'brand_name',
            'logo_path',
            'primary_color',
        ]));
    }

    public function test_tenant_model_casts_scopes_and_helpers_work(): void
    {
        $activeTenant = Tenant::factory()->create([
            'feature_flags' => [
                'maintenance' => false,
            ],
        ]);

        $suspendedTenant = Tenant::factory()->suspended()->create();
        $expiredTenant = Tenant::factory()->expired()->create();

        $this->assertInstanceOf(Carbon::class, $activeTenant->fresh()->trial_ends_at);
        $this->assertSame('slug', $activeTenant->getRouteKeyName());
        $this->assertCount(2, Tenant::active()->get());
        $this->assertCount(1, Tenant::suspended()->get());
        $this->assertCount(1, Tenant::subscriptionActive()->get());

        $this->assertTrue($activeTenant->isAccessible());
        $this->assertFalse($suspendedTenant->isAccessible());
        $this->assertFalse($expiredTenant->isAccessible());
        $this->assertFalse($activeTenant->hasFeature('maintenance'));
        $this->assertTrue($activeTenant->hasFeature('resident_app'));
    }

    public function test_tenant_feature_flags_fall_back_to_configuration_defaults(): void
    {
        $tenant = Tenant::factory()->create([
            'feature_flags' => [
                'maintenance' => false,
            ],
        ]);

        $this->assertFalse($tenant->hasFeature('maintenance'));
        $this->assertTrue($tenant->hasFeature('resident_app'));
        $this->assertFalse($tenant->hasFeature('ai_features'));
    }
}
