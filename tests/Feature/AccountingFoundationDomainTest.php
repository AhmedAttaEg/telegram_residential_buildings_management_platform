<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\DebitTransaction;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\ExpenseSplit;
use App\Models\FinancialPeriod;
use App\Models\Resident;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingFoundationDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_foundation_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('financial_periods'));
        $this->assertTrue(Schema::hasColumns('financial_periods', [
            'tenant_id',
            'period_type',
            'status',
            'locked_at',
            'locked_by',
        ]));

        $this->assertTrue(Schema::hasTable('wallet_transactions'));
        $this->assertTrue(Schema::hasColumns('wallet_transactions', [
            'tenant_id',
            'apartment_id',
            'resident_id',
            'financial_period_id',
            'type',
            'direction',
            'amount',
            'reversed_at',
            'reversal_of_id',
        ]));

        $this->assertTrue(Schema::hasTable('debit_transactions'));
        $this->assertTrue(Schema::hasColumns('debit_transactions', [
            'tenant_id',
            'apartment_id',
            'resident_id',
            'financial_period_id',
            'type',
            'payment_reference_type',
            'payment_reference_id',
            'reversed_at',
            'reversal_of_id',
        ]));

        $this->assertTrue(Schema::hasTable('expenses'));
        $this->assertTrue(Schema::hasColumns('expenses', [
            'tenant_id',
            'building_id',
            'created_by',
            'approved_by',
            'status',
            'approved_at',
        ]));

        $this->assertTrue(Schema::hasTable('expense_splits'));
        $this->assertTrue(Schema::hasColumns('expense_splits', [
            'tenant_id',
            'expense_id',
            'building_id',
            'apartment_id',
            'financial_period_id',
            'is_confirmed',
            'confirmed_at',
            'confirmed_by',
            'is_paid',
            'is_reversed',
        ]));

        $this->assertTrue(Schema::hasTable('expense_payments'));
        $this->assertTrue(Schema::hasColumns('expense_payments', [
            'tenant_id',
            'expense_split_id',
            'wallet_transaction_id',
            'debit_transaction_id',
            'created_by',
            'reversed_by',
            'reversed_at',
            'reversal_reason',
            'reversal_of_id',
        ]));

        $this->assertFalse(Schema::hasColumn('apartments', 'balance'));
        $this->assertFalse(Schema::hasColumn('apartments', 'debt'));
    }

    public function test_financial_period_ledger_expense_split_and_payment_relationships_work(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $apartment = Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
        ]);
        $resident = Resident::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $period = FinancialPeriod::create([
            'tenant_id' => $tenant->id,
            'name' => 'January 2026',
            'period_type' => 'monthly',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'status' => 'locked',
            'locked_at' => '2026-02-01 00:00:00',
            'locked_by' => $user->id,
        ]);

        $walletTransaction = WalletTransaction::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident->id,
            'financial_period_id' => $period->id,
            'type' => 'payment',
            'direction' => 'credit',
            'amount' => 1000,
            'currency' => 'EGP',
            'reference_type' => 'expense_payment',
            'reference_id' => 1,
            'description' => 'Resident payment',
        ]);

        $walletReversal = WalletTransaction::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident->id,
            'financial_period_id' => $period->id,
            'type' => 'reversal',
            'direction' => 'debit',
            'amount' => 1000,
            'currency' => 'EGP',
            'reversed_at' => '2026-02-05 10:00:00',
            'reversal_of_id' => $walletTransaction->id,
            'description' => 'Reverse wallet payment',
        ]);

        $debitTransaction = DebitTransaction::create([
            'tenant_id' => $tenant->id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident->id,
            'financial_period_id' => $period->id,
            'type' => 'manual_debit',
            'amount' => 1000,
            'currency' => 'EGP',
            'payment_reference_type' => 'expense_payment',
            'payment_reference_id' => 1,
            'description' => 'Manual debit',
        ]);

        $expense = Expense::create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'financial_period_id' => $period->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'title' => 'Elevator repair',
            'description' => 'Emergency maintenance',
            'expense_date' => '2026-01-15',
            'status' => 'approved',
            'total_amount' => 1000,
            'currency' => 'EGP',
            'approved_at' => '2026-01-16 09:00:00',
        ]);

        $split = ExpenseSplit::create([
            'tenant_id' => $tenant->id,
            'expense_id' => $expense->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'financial_period_id' => $period->id,
            'amount' => 1000,
            'currency' => 'EGP',
            'is_confirmed' => true,
            'confirmed_at' => '2026-01-16 10:00:00',
            'confirmed_by' => $user->id,
            'is_paid' => true,
            'is_reversed' => false,
        ]);

        $payment = ExpensePayment::create([
            'tenant_id' => $tenant->id,
            'expense_split_id' => $split->id,
            'wallet_transaction_id' => $walletTransaction->id,
            'debit_transaction_id' => $debitTransaction->id,
            'created_by' => $user->id,
            'amount' => 1000,
            'currency' => 'EGP',
            'paid_at' => '2026-01-17 12:00:00',
        ]);

        $paymentReversal = ExpensePayment::create([
            'tenant_id' => $tenant->id,
            'expense_split_id' => $split->id,
            'wallet_transaction_id' => $walletReversal->id,
            'debit_transaction_id' => $debitTransaction->id,
            'created_by' => $user->id,
            'reversed_by' => $user->id,
            'amount' => 1000,
            'currency' => 'EGP',
            'paid_at' => '2026-01-17 12:30:00',
            'reversed_at' => '2026-01-17 13:00:00',
            'reversal_reason' => 'Duplicate collection',
            'reversal_of_id' => $payment->id,
        ]);

        $this->assertTrue($period->tenant->is($tenant));
        $this->assertTrue($period->lockedBy->is($user));
        $this->assertCount(1, FinancialPeriod::forTenant($tenant)->where('status', 'locked')->get());

        $this->assertTrue($walletTransaction->apartment->is($apartment));
        $this->assertTrue($walletTransaction->resident->is($resident));
        $this->assertTrue($walletReversal->reversalOf->is($walletTransaction));
        $this->assertCount(1, $walletTransaction->reversals);

        $this->assertTrue($debitTransaction->financialPeriod->is($period));
        $this->assertTrue($expense->building->is($building));
        $this->assertTrue($expense->createdBy->is($user));
        $this->assertTrue($expense->approvedBy->is($user));
        $this->assertCount(1, $expense->splits);

        $this->assertTrue($split->expense->is($expense));
        $this->assertTrue($split->apartment->is($apartment));
        $this->assertTrue($split->confirmedBy->is($user));
        $this->assertTrue($split->is_paid);

        $this->assertTrue($payment->walletTransaction->is($walletTransaction));
        $this->assertTrue($payment->debitTransaction->is($debitTransaction));
        $this->assertTrue($paymentReversal->reversalOf->is($payment));
        $this->assertCount(1, $payment->reversals);

        $this->assertCount(2, $tenant->walletTransactions);
        $this->assertCount(1, $tenant->debitTransactions);
        $this->assertCount(1, $tenant->expenses);
        $this->assertCount(1, $tenant->expenseSplits);
        $this->assertCount(2, $tenant->expensePayments);
    }
}
