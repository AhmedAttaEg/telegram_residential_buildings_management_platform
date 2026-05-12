<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\TenantSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_billing_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('subscription_plans'));
        $this->assertTrue(Schema::hasColumns('subscription_plans', [
            'name',
            'slug',
            'status',
            'billing_cycle',
            'price_amount',
            'currency',
            'trial_days',
            'feature_limits',
        ]));

        $this->assertTrue(Schema::hasTable('tenant_subscriptions'));
        $this->assertTrue(Schema::hasColumns('tenant_subscriptions', [
            'tenant_id',
            'subscription_plan_id',
            'status',
            'starts_at',
            'trial_ends_at',
            'grace_ends_at',
            'renews_at',
            'ends_at',
            'cancelled_at',
            'suspended_at',
            'reminder_sent_at',
        ]));
    }

    public function test_subscription_plans_support_pricing_cycles_and_feature_limits(): void
    {
        $monthly = SubscriptionPlan::query()->create([
            'name' => 'Starter Monthly',
            'slug' => 'starter-monthly',
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
            'price_amount' => 199.99,
            'currency' => 'EGP',
            'trial_days' => 14,
            'feature_limits' => [
                'buildings' => 3,
                'users' => 10,
                'maintenance' => true,
            ],
        ]);

        $annual = SubscriptionPlan::query()->create([
            'name' => 'Growth Annual',
            'slug' => 'growth-annual',
            'status' => SubscriptionPlan::STATUS_INACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_ANNUAL,
            'price_amount' => 1999.00,
            'currency' => 'EGP',
            'feature_limits' => [
                'buildings' => 20,
                'users' => 100,
            ],
        ]);

        $this->assertSame('199.99', $monthly->fresh()->price_amount);
        $this->assertSame(3, $monthly->fresh()->feature_limits['buildings']);
        $this->assertCount(1, SubscriptionPlan::active()->get());
        $this->assertSame(SubscriptionPlan::BILLING_CYCLE_ANNUAL, $annual->billing_cycle);
    }

    public function test_tenant_subscription_service_attaches_plan_and_syncs_trial_mirror_fields(): void
    {
        Carbon::setTestNow('2026-05-11 12:00:00');

        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Starter Monthly',
            'slug' => 'starter-monthly',
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
            'price_amount' => 99.00,
            'currency' => 'EGP',
            'trial_days' => 14,
        ]);

        $service = app(TenantSubscriptionService::class);
        $subscription = $service->attachPlan($tenant, $plan);

        $tenant->refresh();

        $this->assertSame(TenantSubscription::STATUS_TRIAL, $subscription->status);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertSame('starter-monthly', $tenant->subscription_plan);
        $this->assertSame(TenantSubscription::STATUS_TRIAL, $tenant->subscription_status);
        $this->assertTrue($tenant->trial_ends_at?->equalTo(now()->addDays(14)));
        $this->assertTrue($tenant->subscription_ends_at?->equalTo(now()->addDays(14)));

        Carbon::setTestNow();
    }

    public function test_tenant_subscription_service_can_activate_place_in_grace_suspend_and_mark_reminder_sent(): void
    {
        Carbon::setTestNow('2026-05-11 12:00:00');

        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Growth Annual',
            'slug' => 'growth-annual',
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_ANNUAL,
            'price_amount' => 1200,
            'currency' => 'EGP',
        ]);

        $service = app(TenantSubscriptionService::class);
        $subscription = $service->attachPlan(
            $tenant,
            $plan,
            TenantSubscription::STATUS_ACTIVE,
            now(),
        );

        $tenant->refresh();
        $this->assertSame(TenantSubscription::STATUS_ACTIVE, $tenant->subscription_status);
        $this->assertTrue($tenant->subscription_ends_at?->equalTo(now()->addYear()));

        $subscription = $service->placeInGrace($subscription, now()->addDays(5));
        $tenant->refresh();
        $this->assertSame(TenantSubscription::STATUS_GRACE, $subscription->status);
        $this->assertTrue($tenant->grace_ends_at?->equalTo(now()->addDays(5)));

        $subscription = $service->markReminderSent($subscription, now()->addHour());
        $tenant->refresh();
        $this->assertTrue($tenant->reminder_sent_at?->equalTo(now()->addHour()));

        $subscription = $service->suspend($subscription, now()->addDays(6));
        $tenant->refresh();
        $this->assertSame(TenantSubscription::STATUS_SUSPENDED, $tenant->subscription_status);
        $this->assertTrue($tenant->suspended_at?->equalTo(now()->addDays(6)));

        Carbon::setTestNow();
    }

    public function test_historical_tenant_subscriptions_can_coexist_while_only_latest_lifecycle_record_remains_current(): void
    {
        Carbon::setTestNow('2026-05-11 12:00:00');

        $tenant = Tenant::factory()->create();
        $monthly = SubscriptionPlan::query()->create([
            'name' => 'Starter Monthly',
            'slug' => 'starter-monthly',
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
            'price_amount' => 100,
            'currency' => 'EGP',
        ]);
        $annual = SubscriptionPlan::query()->create([
            'name' => 'Growth Annual',
            'slug' => 'growth-annual',
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_ANNUAL,
            'price_amount' => 1000,
            'currency' => 'EGP',
        ]);

        $service = app(TenantSubscriptionService::class);
        $first = $service->attachPlan($tenant, $monthly, TenantSubscription::STATUS_ACTIVE, now()->subMonth());
        $second = $service->attachPlan($tenant, $annual, TenantSubscription::STATUS_ACTIVE, now());

        $first->refresh();
        $tenant->refresh();

        $this->assertSame(TenantSubscription::STATUS_EXPIRED, $first->status);
        $this->assertSame(TenantSubscription::STATUS_ACTIVE, $second->status);
        $this->assertCount(2, $tenant->tenantSubscriptions);
        $this->assertSame($second->id, $service->currentLifecycleSubscription($tenant)?->id);
        $this->assertSame('growth-annual', $tenant->subscription_plan);

        Carbon::setTestNow();
    }
}
