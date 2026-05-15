<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ExpensePayment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DebitService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentDashboardController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DebitService $debitService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $resident = $user->resident()->firstOrFail();

        $apartment = $resident->apartments()
            ->wherePivot('occupancy_status', 'active')
            ->wherePivotNull('move_out_at')
            ->with('building')
            ->first();

        $walletBalance = $apartment instanceof Apartment
            ? $this->walletService->getBalance($apartment)
            : 0.0;

        $debitBalance = $apartment instanceof Apartment
            ? $this->debitService->getBalance($apartment)
            : 0.0;

        $stats = [
            'wallet_balance' => $walletBalance,
            'debit_balance' => $debitBalance,
            'payments_total' => ExpensePayment::query()
                ->where('tenant_id', $resident->tenant_id)
                ->whereHas('expenseSplit', function ($query) use ($apartment): void {
                    if ($apartment instanceof Apartment) {
                        $query->where('apartment_id', $apartment->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                })
                ->count(),
            'tickets_open' => Ticket::query()
                ->where('tenant_id', $resident->tenant_id)
                ->where('resident_id', $resident->id)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
                ->count(),
        ];

        return view('resident.dashboard', [
            'resident' => $resident,
            'apartment' => $apartment,
            'stats' => $stats,
        ]);
    }
}
