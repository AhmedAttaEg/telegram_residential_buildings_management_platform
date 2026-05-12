<?php

namespace App\Services;

use App\Models\ExpensePayment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentReversalService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DebitService $debitService,
    ) {
    }

    public function reverse(ExpensePayment $payment, User $actor, string $reason): ExpensePayment
    {
        $payment->loadMissing([
            'expenseSplit.apartment',
            'expenseSplit.financialPeriod',
            'walletTransaction.financialPeriod',
            'debitTransaction.financialPeriod',
        ]);

        if ($payment->reversed_at !== null || $payment->reversals()->exists()) {
            throw new DomainException('Expense payment has already been reversed.');
        }

        if ($actor->tenant_id !== null && $actor->tenant_id !== $payment->expenseSplit->tenant_id) {
            throw new DomainException('Actor tenant mismatch.');
        }

        $this->walletService->assertPeriodWritable($payment->expenseSplit->financialPeriod);

        return DB::transaction(function () use ($payment, $actor, $reason): ExpensePayment {
            $reversedAt = now();

            $walletReversal = $this->walletService->reverse($payment->walletTransaction, 'Expense payment reversal');
            $debitReversal = $payment->debitTransaction !== null
                ? $this->debitService->reverse($payment->debitTransaction, 'Expense payment reversal')
                : null;

            $payment->forceFill([
                'reversed_by' => $actor->id,
                'reversed_at' => $reversedAt,
                'reversal_reason' => $reason,
            ])->save();

            $payment->expenseSplit->forceFill([
                'is_paid' => false,
                'is_reversed' => false,
            ])->save();

            $reversal = ExpensePayment::query()->create([
                'tenant_id' => $payment->tenant_id,
                'expense_split_id' => $payment->expense_split_id,
                'wallet_transaction_id' => $walletReversal->id,
                'debit_transaction_id' => $debitReversal?->id,
                'created_by' => $actor->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'paid_at' => $reversedAt,
                'reversal_reason' => $reason,
                'reversal_of_id' => $payment->id,
            ]);

            Log::channel('audit')->info('Expense payment reversed', [
                'event' => 'expense_payment_reversed',
                'tenant_id' => $payment->tenant_id,
                'actor_user_id' => $actor->id,
                'original_payment_id' => $payment->id,
                'reversal_payment_id' => $reversal->id,
                'expense_split_id' => $payment->expense_split_id,
                'original_wallet_transaction_id' => $payment->wallet_transaction_id,
                'reversal_wallet_transaction_id' => $walletReversal->id,
                'original_debit_transaction_id' => $payment->debit_transaction_id,
                'reversal_debit_transaction_id' => $debitReversal?->id,
                'reason' => $reason,
                'reversed_at' => $reversedAt->toIso8601String(),
            ]);

            return $reversal;
        });
    }
}
