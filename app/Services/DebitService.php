<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\DebitTransaction;
use App\Models\ExpenseSplit;
use App\Models\FinancialPeriod;
use App\Models\Resident;
use DomainException;
use Illuminate\Support\Facades\DB;

class DebitService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function getBalance(Apartment $apartment): float
    {
        $unpaidSplits = ExpenseSplit::query()
            ->outstandingForApartment($apartment)
            ->sum('amount');

        $ledgerBalance = DebitTransaction::query()
            ->from('debit_transactions as transactions')
            ->leftJoin('debit_transactions as original_transactions', 'original_transactions.id', '=', 'transactions.reversal_of_id')
            ->where('transactions.tenant_id', $apartment->tenant_id)
            ->where('transactions.apartment_id', $apartment->id)
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN transactions.type = 'payment' THEN -transactions.amount
                    WHEN transactions.type = 'reversal' AND original_transactions.type = 'payment' THEN transactions.amount
                    WHEN transactions.type = 'reversal' THEN -transactions.amount
                    ELSE transactions.amount
                END), 0) as balance
            ")
            ->value('balance');

        return round((float) $unpaidSplits + (float) $ledgerBalance, 2);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createManualDebit(Apartment $apartment, float $amount, array $attributes = []): DebitTransaction
    {
        return $this->createTransaction($apartment, $amount, array_merge([
            'type' => 'manual_debit',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordPayment(Apartment $apartment, float $amount, array $attributes = []): DebitTransaction
    {
        return $this->createTransaction($apartment, $amount, array_merge([
            'type' => 'payment',
        ], $attributes));
    }

    public function reverse(DebitTransaction $transaction, ?string $description = null): DebitTransaction
    {
        if ($transaction->reversed_at !== null || $transaction->reversals()->exists()) {
            throw new DomainException('Debit transaction has already been reversed.');
        }

        $this->walletService->assertPeriodWritable($transaction->financialPeriod);

        return DB::transaction(function () use ($transaction, $description): DebitTransaction {
            $transaction->forceFill([
                'reversed_at' => now(),
            ])->save();

            return DebitTransaction::query()->create([
                'tenant_id' => $transaction->tenant_id,
                'apartment_id' => $transaction->apartment_id,
                'resident_id' => $transaction->resident_id,
                'financial_period_id' => $transaction->financial_period_id,
                'type' => 'reversal',
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_reference_type' => $transaction->payment_reference_type,
                'payment_reference_id' => $transaction->payment_reference_id,
                'description' => $description ?? 'Debit reversal',
                'reversal_of_id' => $transaction->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTransaction(Apartment $apartment, float $amount, array $attributes): DebitTransaction
    {
        $resident = $attributes['resident'] ?? null;
        $period = $attributes['financial_period'] ?? null;

        if ($resident instanceof Resident && $resident->tenant_id !== $apartment->tenant_id) {
            throw new DomainException('Resident tenant mismatch.');
        }

        if ($period instanceof FinancialPeriod && $period->tenant_id !== $apartment->tenant_id) {
            throw new DomainException('Financial period tenant mismatch.');
        }

        $this->walletService->assertPeriodWritable($period);

        return DebitTransaction::query()->create([
            'tenant_id' => $apartment->tenant_id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident?->id,
            'financial_period_id' => $period?->id,
            'type' => $attributes['type'],
            'amount' => round($amount, 2),
            'currency' => $attributes['currency'] ?? config('tenant.defaults.currency', 'EGP'),
            'payment_reference_type' => $attributes['payment_reference_type'] ?? null,
            'payment_reference_id' => $attributes['payment_reference_id'] ?? null,
            'description' => $attributes['description'] ?? null,
        ]);
    }
}
