<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\FinancialPeriod;
use App\Models\Resident;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getBalance(Apartment $apartment): float
    {
        $balance = WalletTransaction::query()
            ->forApartment($apartment)
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN direction = 'credit' THEN amount
                    ELSE -amount
                END), 0) as balance
            ")
            ->value('balance');

        return round((float) $balance, 2);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function deposit(Apartment $apartment, float $amount, array $attributes = []): WalletTransaction
    {
        return $this->createTransaction($apartment, $amount, 'credit', array_merge([
            'type' => 'deposit',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function deduct(Apartment $apartment, float $amount, array $attributes = []): WalletTransaction
    {
        return $this->createTransaction($apartment, $amount, 'debit', array_merge([
            'type' => 'deduction',
        ], $attributes));
    }

    public function reverse(WalletTransaction $transaction, ?string $description = null): WalletTransaction
    {
        if ($transaction->reversed_at !== null || $transaction->reversals()->exists()) {
            throw new DomainException('Wallet transaction has already been reversed.');
        }

        $this->assertPeriodWritable($transaction->financialPeriod);

        return DB::transaction(function () use ($transaction, $description): WalletTransaction {
            $transaction->forceFill([
                'reversed_at' => now(),
            ])->save();

            return WalletTransaction::query()->create([
                'tenant_id' => $transaction->tenant_id,
                'apartment_id' => $transaction->apartment_id,
                'resident_id' => $transaction->resident_id,
                'financial_period_id' => $transaction->financial_period_id,
                'type' => 'reversal',
                'direction' => $transaction->direction === 'credit' ? 'debit' : 'credit',
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'reference_type' => $transaction->reference_type,
                'reference_id' => $transaction->reference_id,
                'description' => $description ?? 'Wallet reversal',
                'reversal_of_id' => $transaction->id,
            ]);
        });
    }

    public function assertSufficientBalance(Apartment $apartment, float $requiredAmount): void
    {
        if ($this->getBalance($apartment) < $requiredAmount) {
            throw new DomainException('Insufficient wallet balance.');
        }
    }

    public function assertPeriodWritable(?FinancialPeriod $period): void
    {
        if ($period !== null && ($period->status === 'locked' || $period->locked_at !== null)) {
            throw new DomainException('Financial period is locked.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTransaction(Apartment $apartment, float $amount, string $direction, array $attributes): WalletTransaction
    {
        $resident = $attributes['resident'] ?? null;
        $period = $attributes['financial_period'] ?? null;

        if ($resident instanceof Resident && $resident->tenant_id !== $apartment->tenant_id) {
            throw new DomainException('Resident tenant mismatch.');
        }

        if ($period instanceof FinancialPeriod && $period->tenant_id !== $apartment->tenant_id) {
            throw new DomainException('Financial period tenant mismatch.');
        }

        $this->assertPeriodWritable($period);

        return WalletTransaction::query()->create([
            'tenant_id' => $apartment->tenant_id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident?->id,
            'financial_period_id' => $period?->id,
            'type' => $attributes['type'],
            'direction' => $direction,
            'amount' => round($amount, 2),
            'currency' => $attributes['currency'] ?? config('tenant.defaults.currency', 'EGP'),
            'reference_type' => $attributes['reference_type'] ?? null,
            'reference_id' => $attributes['reference_id'] ?? null,
            'description' => $attributes['description'] ?? null,
        ]);
    }
}
