<?php

namespace App\Services;

use App\Models\ExpensePayment;
use App\Models\ExpenseSplit;
use App\Models\Resident;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExpensePaymentService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DebitService $debitService,
        private readonly PaymentReversalService $paymentReversalService,
    ) {
    }

    public function paySplit(ExpenseSplit $split, User $actor, ?Resident $resident = null): ExpensePayment
    {
        $split->loadMissing(['apartment', 'financialPeriod', 'payments', 'expense']);

        $this->assertActorCanOperate($split, $actor);
        $this->assertSplitPayable($split);
        $this->walletService->assertPeriodWritable($split->financialPeriod);
        $this->walletService->assertSufficientBalance($split->apartment, (float) $split->amount);

        return DB::transaction(function () use ($split, $actor, $resident): ExpensePayment {
            $walletTransaction = $this->walletService->deduct($split->apartment, (float) $split->amount, [
                'type' => 'payment',
                'resident' => $resident,
                'financial_period' => $split->financialPeriod,
                'reference_type' => ExpenseSplit::class,
                'reference_id' => $split->id,
                'description' => 'Expense split payment',
            ]);

            $debitTransaction = $this->debitService->recordPayment($split->apartment, (float) $split->amount, [
                'resident' => $resident,
                'financial_period' => $split->financialPeriod,
                'payment_reference_type' => ExpenseSplit::class,
                'payment_reference_id' => $split->id,
                'description' => 'Expense split debit payment',
            ]);

            $payment = ExpensePayment::query()->create([
                'tenant_id' => $split->tenant_id,
                'expense_split_id' => $split->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'debit_transaction_id' => $debitTransaction->id,
                'created_by' => $actor->id,
                'amount' => $split->amount,
                'currency' => $split->currency,
                'paid_at' => now(),
            ]);

            $split->forceFill([
                'is_paid' => true,
            ])->save();

            return $payment->refresh();
        });
    }

    public function reversePayment(ExpensePayment $payment, User $actor, string $reason): ExpensePayment
    {
        return $this->paymentReversalService->reverse($payment, $actor, $reason);
    }

    private function assertActorCanOperate(ExpenseSplit $split, User $actor): void
    {
        if ($actor->tenant_id !== null && $actor->tenant_id !== $split->tenant_id) {
            throw new DomainException('Actor tenant mismatch.');
        }
    }

    private function assertSplitPayable(ExpenseSplit $split): void
    {
        if (! $split->is_confirmed) {
            throw new DomainException('Expense split must be confirmed before payment.');
        }

        if ($split->is_reversed) {
            throw new DomainException('Reversed expense splits cannot be paid.');
        }

        if ($split->is_paid || $split->payments()->whereNull('reversed_at')->whereNull('reversal_of_id')->exists()) {
            throw new DomainException('Expense split has already been paid.');
        }
    }
}
