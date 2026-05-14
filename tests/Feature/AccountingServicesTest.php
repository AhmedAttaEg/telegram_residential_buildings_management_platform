<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\ExpenseSplit;
use App\Models\FinancialPeriod;
use App\Models\Resident;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DebitService;
use App\Services\ExpensePaymentService;
use App\Services\WalletService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AccountingServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (glob(storage_path('logs/audit*.log')) ?: [] as $logFile) {
            File::delete($logFile);
        }
    }

    public function test_wallet_service_derives_balance_from_ledger_transactions_and_reversals(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);

        $walletService->deposit($apartment, 500, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Initial deposit',
        ]);

        $deduction = $walletService->deduct($apartment, 125, [
            'resident' => $resident,
            'financial_period' => $period,
            'type' => 'deduction',
            'description' => 'Charge',
        ]);

        $this->assertSame(375.0, $walletService->getBalance($apartment));

        $walletService->reverse($deduction, 'Undo charge');

        $this->assertSame(500.0, $walletService->getBalance($apartment));
        $this->assertCount(3, $tenant->walletTransactions()->get());
    }

    public function test_debit_service_derives_balance_from_unpaid_splits_and_manual_debits(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $debitService = app(DebitService::class);

        ExpenseSplit::query()->create([
            'tenant_id' => $tenant->id,
            'expense_id' => Expense::query()->create([
                'tenant_id' => $tenant->id,
                'building_id' => $building->id,
                'financial_period_id' => $period->id,
                'created_by' => $user->id,
                'title' => 'Water bill',
                'expense_date' => '2026-01-10',
                'status' => 'approved',
                'total_amount' => 200,
                'currency' => 'EGP',
            ])->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'financial_period_id' => $period->id,
            'amount' => 200,
            'currency' => 'EGP',
            'is_confirmed' => true,
            'is_paid' => false,
            'is_reversed' => false,
        ]);

        $manualDebit = $debitService->createManualDebit($apartment, 50, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Manual charge',
        ]);

        $this->assertSame(250.0, $debitService->getBalance($apartment));

        $debitService->recordPayment($apartment, 75, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Debit payment',
        ]);

        $this->assertSame(175.0, $debitService->getBalance($apartment));

        $debitService->reverse($manualDebit, 'Undo manual charge');

        $this->assertSame(125.0, $debitService->getBalance($apartment));
    }

    public function test_expense_payment_service_pays_split_creates_ledgers_and_prevents_duplicates(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $paymentService = app(ExpensePaymentService::class);

        $walletService->deposit($apartment, 1000, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Funding wallet',
        ]);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 300);

        $payment = $paymentService->paySplit($split, $user, $resident);

        $this->assertInstanceOf(ExpensePayment::class, $payment);
        $this->assertTrue($split->fresh()->is_paid);
        $this->assertSame(700.0, $walletService->getBalance($apartment));
        $this->assertSame(-300.0, app(DebitService::class)->getBalance($apartment));

        $this->expectException(DomainException::class);

        $paymentService->paySplit($split->fresh(), $user, $resident);
    }

    public function test_expense_payment_service_reverses_payments_and_reopens_splits(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $debitService = app(DebitService::class);
        $paymentService = app(ExpensePaymentService::class);

        $walletService->deposit($apartment, 1000, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Funding wallet',
        ]);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 250);
        $payment = $paymentService->paySplit($split, $user, $resident);

        $reversal = $paymentService->reversePayment($payment, $user, 'Incorrect posting');

        $this->assertTrue($payment->fresh()->reversed_at !== null);
        $this->assertSame($payment->id, $reversal->reversal_of_id);
        $this->assertFalse($split->fresh()->is_paid);
        $this->assertSame(1000.0, $walletService->getBalance($apartment));
        $this->assertSame(250.0, $debitService->getBalance($apartment));

        $this->assertDatabaseHas('wallet_transactions', [
            'reversal_of_id' => $payment->wallet_transaction_id,
            'type' => 'reversal',
        ]);
        $this->assertDatabaseHas('debit_transactions', [
            'reversal_of_id' => $payment->debit_transaction_id,
            'type' => 'reversal',
        ]);
    }

    public function test_expense_payment_service_logs_reversal_audit_context(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $paymentService = app(ExpensePaymentService::class);

        $walletService->deposit($apartment, 1000, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Funding wallet',
        ]);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 250);
        $payment = $paymentService->paySplit($split, $user, $resident);

        $reversal = $paymentService->reversePayment($payment, $user, 'Incorrect posting');
        $contents = $this->readAuditLog();

        $this->assertStringContainsString('expense_payment_reversed', $contents);
        $this->assertStringContainsString((string) $tenant->id, $contents);
        $this->assertStringContainsString((string) $user->id, $contents);
        $this->assertStringContainsString((string) $payment->id, $contents);
        $this->assertStringContainsString((string) $reversal->id, $contents);
        $this->assertStringContainsString((string) $split->id, $contents);
        $this->assertStringContainsString('Incorrect posting', $contents);
    }

    public function test_expense_payment_service_rejects_duplicate_reversals_without_partial_writes(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $paymentService = app(ExpensePaymentService::class);

        $walletService->deposit($apartment, 1000, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Funding wallet',
        ]);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 250);
        $payment = $paymentService->paySplit($split, $user, $resident);

        $paymentService->reversePayment($payment, $user, 'Incorrect posting');

        try {
            $paymentService->reversePayment($payment->fresh(), $user, 'Second attempt');
            $this->fail('Expected duplicate reversal exception.');
        } catch (DomainException $exception) {
            $this->assertSame('Expense payment has already been reversed.', $exception->getMessage());
        }

        $this->assertSame(2, ExpensePayment::query()->count());
        $this->assertSame(3, $tenant->walletTransactions()->count());
        $this->assertSame(2, $tenant->debitTransactions()->count());
    }

    public function test_accounting_services_block_writes_for_locked_periods_and_insufficient_wallets(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $paymentService = app(ExpensePaymentService::class);

        $lockedPeriod = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Locked Period',
            'period_type' => 'monthly',
            'starts_at' => '2026-02-01',
            'ends_at' => '2026-02-28',
            'status' => 'locked',
            'locked_at' => now(),
            'locked_by' => $user->id,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Financial period is locked.');

        $walletService->deposit($apartment, 100, [
            'resident' => $resident,
            'financial_period' => $lockedPeriod,
            'description' => 'Blocked deposit',
        ]);
    }

    public function test_expense_payment_service_rejects_insufficient_balance_without_partial_writes(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $paymentService = app(ExpensePaymentService::class);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 400);

        try {
            $paymentService->paySplit($split, $user, $resident);
            $this->fail('Expected insufficient wallet balance exception.');
        } catch (DomainException $exception) {
            $this->assertSame('Insufficient wallet balance.', $exception->getMessage());
        }

        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertDatabaseCount('debit_transactions', 0);
        $this->assertFalse($split->fresh()->is_paid);
    }

    public function test_wallet_and_debit_services_reject_cross_tenant_resident_and_period_inputs_without_partial_writes(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $otherTenant = Tenant::factory()->create();
        $foreignResident = Resident::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);
        $foreignPeriod = FinancialPeriod::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Period',
            'period_type' => 'monthly',
            'starts_at' => '2026-02-01',
            'ends_at' => '2026-02-28',
            'status' => 'open',
        ]);

        $walletService = app(WalletService::class);
        $debitService = app(DebitService::class);

        try {
            $walletService->deposit($apartment, 100, [
                'resident' => $foreignResident,
                'financial_period' => $period,
            ]);
            $this->fail('Expected wallet resident tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Resident tenant mismatch.', $exception->getMessage());
        }

        try {
            $walletService->deposit($apartment, 100, [
                'resident' => $resident,
                'financial_period' => $foreignPeriod,
            ]);
            $this->fail('Expected wallet financial period tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Financial period tenant mismatch.', $exception->getMessage());
        }

        try {
            $debitService->createManualDebit($apartment, 50, [
                'resident' => $foreignResident,
                'financial_period' => $period,
            ]);
            $this->fail('Expected debit resident tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Resident tenant mismatch.', $exception->getMessage());
        }

        try {
            $debitService->createManualDebit($apartment, 50, [
                'resident' => $resident,
                'financial_period' => $foreignPeriod,
            ]);
            $this->fail('Expected debit financial period tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Financial period tenant mismatch.', $exception->getMessage());
        }

        $this->assertSame(0, $tenant->walletTransactions()->count());
        $this->assertSame(0, $tenant->debitTransactions()->count());
    }

    public function test_expense_payment_service_rejects_cross_tenant_actor_without_partial_writes(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $paymentService = app(ExpensePaymentService::class);
        $foreignActor = User::factory()->forTenant(Tenant::factory()->create())->create();

        $walletService->deposit($apartment, 500, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Funding wallet',
        ]);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 250);

        try {
            $paymentService->paySplit($split, $foreignActor, $resident);
            $this->fail('Expected actor tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Actor tenant mismatch.', $exception->getMessage());
        }

        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertSame(1, $tenant->walletTransactions()->count());
        $this->assertSame(0, $tenant->debitTransactions()->count());
        $this->assertFalse($split->fresh()->is_paid);
    }

    public function test_payment_reversal_service_rejects_cross_tenant_actor_and_locked_period_without_partial_writes(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $paymentService = app(ExpensePaymentService::class);
        $foreignActor = User::factory()->forTenant(Tenant::factory()->create())->create();

        $walletService->deposit($apartment, 1000, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Funding wallet',
        ]);

        $split = $this->createConfirmedSplit($tenant, $user, $building, $apartment, $period, 250);
        $payment = $paymentService->paySplit($split, $user, $resident);

        try {
            $paymentService->reversePayment($payment, $foreignActor, 'Foreign actor');
            $this->fail('Expected actor tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Actor tenant mismatch.', $exception->getMessage());
        }

        $this->assertSame(750.0, $walletService->getBalance($apartment));
        $this->assertSame(-250.0, app(DebitService::class)->getBalance($apartment));
        $this->assertSame(1, ExpensePayment::query()->count());

        $period->forceFill([
            'status' => 'locked',
            'locked_at' => now(),
            'locked_by' => $user->id,
        ])->save();

        try {
            $paymentService->reversePayment($payment->fresh(), $user, 'Locked period');
            $this->fail('Expected locked period exception.');
        } catch (DomainException $exception) {
            $this->assertSame('Financial period is locked.', $exception->getMessage());
        }

        $this->assertTrue($payment->fresh()->reversed_at === null);
        $this->assertTrue($split->fresh()->is_paid);
        $this->assertSame(2, $tenant->walletTransactions()->count());
        $this->assertSame(1, $tenant->debitTransactions()->count());
    }

    public function test_wallet_and_debit_balances_remain_exact_through_rounding_and_reversal_sequences(): void
    {
        [$tenant, $user, $building, $apartment, $resident, $period] = $this->createAccountingFixture();
        $walletService = app(WalletService::class);
        $debitService = app(DebitService::class);

        $walletService->deposit($apartment, 100.55, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Seed funding',
        ]);
        $walletService->deduct($apartment, 0.55, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Fractional charge',
        ]);
        $walletCreditAdjustment = $walletService->deposit($apartment, 0.10, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Rounding adjustment',
        ]);

        $this->assertSame(100.10, $walletService->getBalance($apartment));

        $debitService->createManualDebit($apartment, 100.55, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Exact debit',
        ]);
        $debitPayment = $debitService->recordPayment($apartment, 0.55, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Fractional payment',
        ]);

        $this->assertSame(100.0, $debitService->getBalance($apartment));

        $walletService->reverse($walletCreditAdjustment, 'Undo rounding adjustment');
        $debitService->reverse($debitPayment, 'Undo fractional payment');

        $this->assertSame(100.0, $walletService->getBalance($apartment));
        $this->assertSame(100.55, $debitService->getBalance($apartment));
    }

    /**
     * @return array{Tenant, User, Building, Apartment, Resident, FinancialPeriod}
     */
    private function createAccountingFixture(): array
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
        $period = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Open Period',
            'period_type' => 'monthly',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'status' => 'open',
        ]);

        return [$tenant, $user, $building, $apartment, $resident, $period];
    }

    private function createConfirmedSplit(
        Tenant $tenant,
        User $user,
        Building $building,
        Apartment $apartment,
        FinancialPeriod $period,
        float $amount,
    ): ExpenseSplit {
        $expense = Expense::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'financial_period_id' => $period->id,
            'created_by' => $user->id,
            'title' => 'Service charge',
            'expense_date' => '2026-01-12',
            'status' => 'approved',
            'total_amount' => $amount,
            'currency' => 'EGP',
        ]);

        return ExpenseSplit::query()->create([
            'tenant_id' => $tenant->id,
            'expense_id' => $expense->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'financial_period_id' => $period->id,
            'amount' => $amount,
            'currency' => 'EGP',
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
            'is_paid' => false,
            'is_reversed' => false,
        ]);
    }

    private function readAuditLog(): string
    {
        $files = glob(storage_path('logs/audit*.log')) ?: [];

        $this->assertNotEmpty($files, 'No audit log file was created.');

        return (string) file_get_contents($files[0]);
    }
}
