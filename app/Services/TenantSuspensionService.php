<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Carbon;

class TenantSuspensionService
{
    public function activate(Tenant $tenant): Tenant
    {
        $tenant->forceFill([
            'status' => 'active',
            'subscription_status' => $tenant->subscription_status === 'suspended' ? 'active' : $tenant->subscription_status,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        return $tenant->refresh();
    }

    public function placeInGrace(Tenant $tenant, ?Carbon $graceEndsAt = null, ?string $reason = null): Tenant
    {
        $tenant->forceFill([
            'status' => 'active',
            'subscription_status' => 'grace',
            'grace_ends_at' => $graceEndsAt ?? now()->addDays(7),
            'suspended_at' => null,
            'suspension_reason' => $reason,
        ])->save();

        return $tenant->refresh();
    }

    public function suspend(Tenant $tenant, ?string $reason = null): Tenant
    {
        $tenant->forceFill([
            'status' => 'suspended',
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();

        return $tenant->refresh();
    }

    public function markReminderSent(Tenant $tenant): Tenant
    {
        $tenant->forceFill([
            'reminder_sent_at' => now(),
        ])->save();

        return $tenant->refresh();
    }
}
