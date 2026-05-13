<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resident\ListUnpaidSplitsRequest;
use App\Http\Requests\Resident\ListWalletTransactionsRequest;
use App\Models\Apartment;
use App\Models\ExpenseSplit;
use App\Models\Resident;
use App\Models\WalletTransaction;
use App\Support\ApiResponse;
use App\Services\DebitService;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ResidentPortalController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DebitService $debitService,
    ) {
    }

    public function walletSummary(Request $request)
    {
        $resident = $this->resident($request);
        $apartment = $this->apartment($request);

        return $this->apiSuccess([
            'resident' => $this->residentPayload($resident),
            'apartment' => $this->apartmentPayload($apartment),
            'currency' => config('tenant.defaults.currency', 'EGP'),
            'balance' => round($this->walletService->getBalance($apartment), 2),
        ]);
    }

    public function walletHistory(ListWalletTransactionsRequest $request)
    {
        $resident = $this->resident($request);
        $apartment = $this->apartment($request);

        $transactions = WalletTransaction::query()
            ->forApartment($apartment)
            ->latestFirst()
            ->paginate((int) $request->integer('per_page', 15));

        $transactions->getCollection()->transform(function (WalletTransaction $transaction): array {
            return [
                'id' => $transaction->id,
                'resident_id' => $transaction->resident_id,
                'financial_period_id' => $transaction->financial_period_id,
                'type' => $transaction->type,
                'direction' => $transaction->direction,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'reference_type' => $transaction->reference_type,
                'reference_id' => $transaction->reference_id,
                'description' => $transaction->description,
                'reversed_at' => $transaction->reversed_at?->toISOString(),
                'reversal_of_id' => $transaction->reversal_of_id,
                'created_at' => $transaction->created_at?->toISOString(),
                'updated_at' => $transaction->updated_at?->toISOString(),
            ];
        });

        return $this->paginatedResponse(
            $transactions,
            'Wallet history retrieved successfully.',
            [
                'resident' => $this->residentPayload($resident),
                'apartment' => $this->apartmentPayload($apartment),
            ],
        );
    }

    public function debitSummary(Request $request)
    {
        $resident = $this->resident($request);
        $apartment = $this->apartment($request);

        return $this->apiSuccess([
            'resident' => $this->residentPayload($resident),
            'apartment' => $this->apartmentPayload($apartment),
            'currency' => config('tenant.defaults.currency', 'EGP'),
            'balance' => round($this->debitService->getBalance($apartment), 2),
        ]);
    }

    public function unpaidSplits(ListUnpaidSplitsRequest $request)
    {
        $resident = $this->resident($request);
        $apartment = $this->apartment($request);

        $query = ExpenseSplit::query()
            ->with(['expense', 'financialPeriod'])
            ->outstandingForApartment($apartment)
            ->latest('id');

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->integer('building_id'));
        }

        if ($request->filled('financial_period_id')) {
            $query->where('financial_period_id', $request->integer('financial_period_id'));
        }

        $splits = $query->paginate((int) $request->integer('per_page', 15));

        $splits->getCollection()->transform(function (ExpenseSplit $split): array {
            return [
                'id' => $split->id,
                'expense_id' => $split->expense_id,
                'building_id' => $split->building_id,
                'apartment_id' => $split->apartment_id,
                'financial_period_id' => $split->financial_period_id,
                'amount' => (float) $split->amount,
                'currency' => $split->currency,
                'is_confirmed' => $split->is_confirmed,
                'is_paid' => $split->is_paid,
                'is_reversed' => $split->is_reversed,
                'confirmed_at' => $split->confirmed_at?->toISOString(),
                'expense' => [
                    'title' => $split->expense?->title,
                    'expense_date' => $split->expense?->expense_date?->toDateString(),
                ],
                'financial_period' => [
                    'id' => $split->financialPeriod?->id,
                    'name' => $split->financialPeriod?->name,
                ],
                'created_at' => $split->created_at?->toISOString(),
                'updated_at' => $split->updated_at?->toISOString(),
            ];
        });

        return $this->paginatedResponse(
            $splits,
            'Unpaid debit splits retrieved successfully.',
            [
                'resident' => $this->residentPayload($resident),
                'apartment' => $this->apartmentPayload($apartment),
                'filters' => array_filter([
                    'building_id' => $request->filled('building_id') ? $request->integer('building_id') : null,
                    'financial_period_id' => $request->filled('financial_period_id') ? $request->integer('financial_period_id') : null,
                ], fn ($value) => $value !== null),
            ],
        );
    }

    private function resident(Request $request): Resident
    {
        /** @var Resident $resident */
        $resident = $request->attributes->get('resident');

        return $resident;
    }

    private function apartment(Request $request): Apartment
    {
        /** @var Apartment $apartment */
        $apartment = $request->attributes->get('resident_apartment');

        return $apartment;
    }

    /**
     * @return array<string, mixed>
     */
    private function residentPayload(Resident $resident): array
    {
        return [
            'id' => $resident->id,
            'tenant_id' => $resident->tenant_id,
            'full_name' => $resident->full_name,
            'resident_type' => $resident->resident_type,
            'status' => $resident->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apartmentPayload(Apartment $apartment): array
    {
        return [
            'id' => $apartment->id,
            'tenant_id' => $apartment->tenant_id,
            'building_id' => $apartment->building_id,
            'unit_number' => $apartment->unit_number,
            'display_label' => $apartment->display_label,
        ];
    }

    private function paginatedResponse(LengthAwarePaginator $paginator, string $message, array $meta = [])
    {
        return ApiResponse::success(
            $paginator->items(),
            $message,
            meta: array_merge([
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ], $meta),
        );
    }
}
