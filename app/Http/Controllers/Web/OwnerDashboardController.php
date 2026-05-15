<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'tenants_total' => Tenant::query()->count(),
            'tenants_active' => Tenant::query()->where('status', 'active')->count(),
            'tenants_suspended' => Tenant::query()->where('status', 'suspended')->count(),
            'plans_total' => SubscriptionPlan::query()->count(),
            'audit_events' => AuditLog::query()->count(),
        ];

        $recentTenants = Tenant::query()
            ->latest('id')
            ->limit(5)
            ->get();

        return view('owner.dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
        ]);
    }
}
