<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Resident;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant()->firstOrFail();

        $stats = [
            'buildings_total' => Building::query()->forTenant($tenant)->count(),
            'apartments_total' => Apartment::query()->forTenant($tenant)->count(),
            'residents_total' => Resident::query()->forTenant($tenant)->count(),
            'users_total' => User::query()->forTenant($tenant)->count(),
            'open_tickets' => Ticket::query()->forTenant($tenant)->where('status', Ticket::STATUS_OPEN)->count(),
            'audit_events' => AuditLog::query()->where('tenant_id', $tenant->id)->count(),
        ];

        $recentResidents = Resident::query()
            ->forTenant($tenant)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'recentResidents' => $recentResidents,
        ]);
    }
}
