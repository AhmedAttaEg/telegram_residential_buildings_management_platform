<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TenantSubscriptionService
{
    public function attachPlan(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $status = TenantSubscription::STATUS_TRIAL,
        ?Carbon $startsAt = null,
        ?string $notes = null,
    ): TenantSubscription {
        $startsAt ??= now();

        return DB::transaction(function () use ($tenant, $plan, $status, $startsAt, $notes): TenantSubscription {
            $this->expireCurrentLifecycleSubscriptions($tenant, $startsAt);

            $subscription = TenantSubscription::query()->create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'trial_ends_at' => $status === TenantSubscription::STATUS_TRIAL && $plan->trial_days !== null
                    ? $startsAt->copy()->addDays((int) $plan->trial_days)
                    : null,
                'renews_at' => $this->calculateRenewsAt($plan, $startsAt),
                'ends_at' => $this->calculateEndsAt($plan, $status, $startsAt),
                'notes' => $notes,
            ]);

            $this->syncTenantFromSubscription($tenant->fresh(), $subscription->fresh(['subscriptionPlan']));

            return $subscription->fresh(['tenant', 'subscriptionPlan']);
        });
    }

    public function activate(TenantSubscription $subscription, ?Carbon $renewsAt = null): TenantSubscription
    {
        $subscription->forceFill([
            'status' => TenantSubscription::STATUS_ACTIVE,
            'trial_ends_at' => $subscription->trial_ends_at,
            'grace_ends_at' => null,
            'suspended_at' => null,
            'cancelled_at' => null,
            'renews_at' => $renewsAt ?? $subscription->renews_at ?? $this->calculateRenewsAt($subscription->subscriptionPlan, $subscription->starts_at),
            'ends_at' => $renewsAt ?? $subscription->renews_at ?? $this->calculateRenewsAt($subscription->subscriptionPlan, $subscription->starts_at),
        ])->save();

        $this->syncTenantFromSubscription($subscription->tenant()->firstOrFail(), $subscription->fresh(['subscriptionPlan']));

        return $subscription->fresh(['tenant', 'subscriptionPlan']);
    }

    public function placeInGrace(TenantSubscription $subscription, ?Carbon $graceEndsAt = null): TenantSubscription
    {
        $subscription->forceFill([
            'status' => TenantSubscription::STATUS_GRACE,
            'grace_ends_at' => $graceEndsAt ?? now()->addDays(7),
            'suspended_at' => null,
        ])->save();

        $this->syncTenantFromSubscription($subscription->tenant()->firstOrFail(), $subscription->fresh(['subscriptionPlan']));

        return $subscription->fresh(['tenant', 'subscriptionPlan']);
    }

    public function suspend(TenantSubscription $subscription, ?Carbon $suspendedAt = null): TenantSubscription
    {
        $subscription->forceFill([
            'status' => TenantSubscription::STATUS_SUSPENDED,
            'suspended_at' => $suspendedAt ?? now(),
        ])->save();

        $this->syncTenantFromSubscription($subscription->tenant()->firstOrFail(), $subscription->fresh(['subscriptionPlan']));

        return $subscription->fresh(['tenant', 'subscriptionPlan']);
    }

    public function cancel(TenantSubscription $subscription, ?Carbon $cancelledAt = null): TenantSubscription
    {
        $subscription->forceFill([
            'status' => TenantSubscription::STATUS_CANCELLED,
            'cancelled_at' => $cancelledAt ?? now(),
        ])->save();

        $this->syncTenantFromSubscription($subscription->tenant()->firstOrFail(), $this->currentLifecycleSubscription($subscription->tenant()->firstOrFail()));

        return $subscription->fresh(['tenant', 'subscriptionPlan']);
    }

    public function markReminderSent(TenantSubscription $subscription, ?Carbon $sentAt = null): TenantSubscription
    {
        $subscription->forceFill([
            'reminder_sent_at' => $sentAt ?? now(),
        ])->save();

        $this->syncTenantFromSubscription($subscription->tenant()->firstOrFail(), $subscription->fresh(['subscriptionPlan']));

        return $subscription->fresh(['tenant', 'subscriptionPlan']);
    }

    public function syncTenantFromSubscription(Tenant $tenant, ?TenantSubscription $subscription): Tenant
    {
        $tenant->forceFill([
            'subscription_status' => $subscription?->status ?? TenantSubscription::STATUS_EXPIRED,
            'subscription_plan' => $subscription?->subscriptionPlan?->slug,
            'trial_ends_at' => $subscription?->trial_ends_at,
            'grace_ends_at' => $subscription?->grace_ends_at,
            'subscription_ends_at' => $subscription?->ends_at,
            'suspended_at' => $subscription?->suspended_at,
            'reminder_sent_at' => $subscription?->reminder_sent_at,
        ])->save();

        return $tenant->refresh();
    }

    public function currentLifecycleSubscription(Tenant $tenant): ?TenantSubscription
    {
        return TenantSubscription::query()
            ->forTenant($tenant)
            ->currentLifecycle()
            ->latest('starts_at')
            ->with('subscriptionPlan')
            ->first();
    }

    private function expireCurrentLifecycleSubscriptions(Tenant $tenant, Carbon $endedAt): void
    {
        TenantSubscription::query()
            ->forTenant($tenant)
            ->currentLifecycle()
            ->update([
                'status' => TenantSubscription::STATUS_EXPIRED,
                'ends_at' => $endedAt,
            ]);
    }

    private function calculateRenewsAt(SubscriptionPlan $plan, Carbon $startsAt): Carbon
    {
        return match ($plan->billing_cycle) {
            SubscriptionPlan::BILLING_CYCLE_ANNUAL => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth(),
        };
    }

    private function calculateEndsAt(SubscriptionPlan $plan, string $status, Carbon $startsAt): ?Carbon
    {
        if ($status === TenantSubscription::STATUS_TRIAL && $plan->trial_days !== null) {
            return $startsAt->copy()->addDays((int) $plan->trial_days);
        }

        if ($status === TenantSubscription::STATUS_ACTIVE) {
            return $this->calculateRenewsAt($plan, $startsAt);
        }

        return null;
    }
}
