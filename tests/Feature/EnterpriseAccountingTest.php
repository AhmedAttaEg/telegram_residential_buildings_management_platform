<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Tenant;
use App\Models\User;
use App\Services\JournalEntryService;
use App\Services\TrialBalanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnterpriseAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_accounting_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('ledger_accounts'));
        $this->assertTrue(Schema::hasColumns('ledger_accounts', [
            'tenant_id',
            'parent_id',
            'code',
            'name',
            'type',
            'is_active',
        ]));

        $this->assertTrue(Schema::hasTable('journal_entries'));
        $this->assertTrue(Schema::hasColumns('journal_entries', [
            'tenant_id',
            'financial_period_id',
            'entry_number',
            'status',
            'entry_date',
            'posted_at',
            'created_by',
        ]));

        $this->assertTrue(Schema::hasTable('journal_entry_lines'));
        $this->assertTrue(Schema::hasColumns('journal_entry_lines', [
            'tenant_id',
            'journal_entry_id',
            'ledger_account_id',
            'debit_amount',
            'credit_amount',
        ]));
    }

    public function test_chart_of_accounts_supports_tenant_hierarchy_and_relationships(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();

        $parent = LedgerAccount::query()->create([
            'tenant_id' => $tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => LedgerAccount::TYPE_ASSET,
        ]);

        $child = LedgerAccount::query()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
            'code' => '1100',
            'name' => 'Cash',
            'type' => LedgerAccount::TYPE_ASSET,
        ]);

        $entry = JournalEntry::query()->create([
            'tenant_id' => $tenant->id,
            'financial_period_id' => $period->id,
            'entry_number' => 'JE-202601-000001',
            'status' => JournalEntry::STATUS_DRAFT,
            'entry_date' => '2026-01-10',
            'created_by' => $user->id,
        ]);

        $line = $entry->lines()->create([
            'tenant_id' => $tenant->id,
            'ledger_account_id' => $child->id,
            'debit_amount' => 100,
            'credit_amount' => 0,
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertCount(1, $parent->children);
        $this->assertTrue($line->ledgerAccount->is($child));
        $this->assertCount(2, $tenant->ledgerAccounts);
        $this->assertCount(1, $tenant->journalEntries);
        $this->assertCount(1, $tenant->journalEntryLines);
        $this->assertTrue($period->journalEntries->first()->is($entry));
        $this->assertTrue($user->journalEntries->first()->is($entry));
    }

    public function test_journal_entry_service_creates_balanced_draft_entries_and_posts_them(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 500,
            ],
            [
                'ledger_account_id' => $revenue->id,
                'credit_amount' => 500,
            ],
        ], [
            'description' => 'Resident collection',
            'entry_date' => '2026-01-12',
        ]);

        $this->assertSame(JournalEntry::STATUS_DRAFT, $entry->status);
        $this->assertSame('JE-202601-000001', $entry->entry_number);
        $this->assertCount(2, $entry->lines);

        $posted = $service->postEntry($entry, $user);

        $this->assertSame(JournalEntry::STATUS_POSTED, $posted->status);
        $this->assertNotNull($posted->posted_at);
    }

    public function test_journal_entry_service_rejects_unbalanced_entries(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);

        $service = app(JournalEntryService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Journal entry is not balanced.');

        $service->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 500,
            ],
            [
                'ledger_account_id' => $revenue->id,
                'credit_amount' => 450,
            ],
        ]);
    }

    public function test_journal_entry_service_rejects_invalid_line_shapes(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);

        $service = app(JournalEntryService::class);

        try {
            $service->createDraftEntry($tenant, $user, $period, [
                [
                    'ledger_account_id' => $cash->id,
                    'debit_amount' => 100,
                    'credit_amount' => 100,
                ],
                [
                    'ledger_account_id' => $revenue->id,
                    'credit_amount' => 200,
                ],
            ]);

            $this->fail('Expected both-sided line validation to fail.');
        } catch (DomainException $exception) {
            $this->assertSame('Journal entry line cannot contain both debit and credit amounts.', $exception->getMessage());
        }

        try {
            $service->createDraftEntry($tenant, $user, $period, [
                [
                    'ledger_account_id' => $cash->id,
                ],
                [
                    'ledger_account_id' => $revenue->id,
                    'credit_amount' => 100,
                ],
            ]);

            $this->fail('Expected empty-sided line validation to fail.');
        } catch (DomainException $exception) {
            $this->assertSame('Journal entry line must contain either a debit or credit amount.', $exception->getMessage());
        }
    }

    public function test_journal_entry_service_rejects_cross_tenant_accounts(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $otherTenant = Tenant::factory()->create();

        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $foreignRevenue = $this->createLedgerAccount($otherTenant, '4100', 'Foreign Revenue', LedgerAccount::TYPE_REVENUE);

        $service = app(JournalEntryService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Journal entry line account tenant mismatch.');

        $service->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 200,
            ],
            [
                'ledger_account_id' => $foreignRevenue->id,
                'credit_amount' => 200,
            ],
        ]);
    }

    public function test_journal_entry_service_rejects_cross_tenant_actor_and_period_without_partial_writes(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $foreignActor = User::factory()->forTenant(Tenant::factory()->create())->create();
        $foreignPeriod = FinancialPeriod::query()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'name' => 'Foreign Period',
            'period_type' => 'monthly',
            'starts_at' => '2026-02-01',
            'ends_at' => '2026-02-28',
            'status' => 'open',
        ]);
        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);
        $service = app(JournalEntryService::class);

        try {
            $service->createDraftEntry($tenant, $foreignActor, $period, [
                [
                    'ledger_account_id' => $cash->id,
                    'debit_amount' => 200,
                ],
                [
                    'ledger_account_id' => $revenue->id,
                    'credit_amount' => 200,
                ],
            ]);
            $this->fail('Expected actor tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Actor tenant mismatch.', $exception->getMessage());
        }

        try {
            $service->createDraftEntry($tenant, $user, $foreignPeriod, [
                [
                    'ledger_account_id' => $cash->id,
                    'debit_amount' => 200,
                ],
                [
                    'ledger_account_id' => $revenue->id,
                    'credit_amount' => 200,
                ],
            ]);
            $this->fail('Expected financial period tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Financial period tenant mismatch.', $exception->getMessage());
        }

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_entry_lines', 0);
    }

    public function test_journal_entry_service_rejects_posting_in_locked_periods(): void
    {
        [$tenant, $user] = $this->createEnterpriseFixture();
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

        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);

        $service = app(JournalEntryService::class);
        $entry = $service->createDraftEntry($tenant, $user, $lockedPeriod, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 200,
            ],
            [
                'ledger_account_id' => $revenue->id,
                'credit_amount' => 200,
            ],
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Financial period is locked.');

        $service->postEntry($entry, $user);
    }

    public function test_journal_entry_service_rejects_cross_tenant_posting_without_mutating_entry_state(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $foreignActor = User::factory()->forTenant(Tenant::factory()->create())->create();
        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);
        $service = app(JournalEntryService::class);

        $entry = $service->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 200,
            ],
            [
                'ledger_account_id' => $revenue->id,
                'credit_amount' => 200,
            ],
        ]);

        try {
            $service->postEntry($entry, $foreignActor);
            $this->fail('Expected actor tenant mismatch.');
        } catch (DomainException $exception) {
            $this->assertSame('Actor tenant mismatch.', $exception->getMessage());
        }

        $entry = $entry->fresh();

        $this->assertSame(JournalEntry::STATUS_DRAFT, $entry->status);
        $this->assertNull($entry->posted_at);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_entry_lines', 2);
    }

    public function test_trial_balance_service_calculates_groups_summaries_and_csv_export(): void
    {
        [$tenant, $user, $period] = $this->createEnterpriseFixture();
        $cash = $this->createLedgerAccount($tenant, '1100', 'Cash', LedgerAccount::TYPE_ASSET);
        $payable = $this->createLedgerAccount($tenant, '2100', 'Accounts Payable', LedgerAccount::TYPE_LIABILITY);
        $revenue = $this->createLedgerAccount($tenant, '4100', 'Service Revenue', LedgerAccount::TYPE_REVENUE);
        $expense = $this->createLedgerAccount($tenant, '5100', 'Maintenance Expense', LedgerAccount::TYPE_EXPENSE);

        $journalService = app(JournalEntryService::class);
        $trialBalanceService = app(TrialBalanceService::class);

        $collectionEntry = $journalService->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 500,
            ],
            [
                'ledger_account_id' => $revenue->id,
                'credit_amount' => 500,
            ],
        ], [
            'description' => 'Resident collection',
            'entry_date' => '2026-01-10',
        ]);

        $expenseEntry = $journalService->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $expense->id,
                'debit_amount' => 300,
            ],
            [
                'ledger_account_id' => $payable->id,
                'credit_amount' => 300,
            ],
        ], [
            'description' => 'Expense accrual',
            'entry_date' => '2026-01-11',
        ]);

        $journalService->createDraftEntry($tenant, $user, $period, [
            [
                'ledger_account_id' => $cash->id,
                'debit_amount' => 999,
            ],
            [
                'ledger_account_id' => $revenue->id,
                'credit_amount' => 999,
            ],
        ], [
            'description' => 'Draft only',
            'entry_date' => '2026-01-12',
        ]);

        $journalService->postEntry($collectionEntry, $user);
        $journalService->postEntry($expenseEntry, $user);

        $report = $trialBalanceService->generate($tenant, $period);
        $csv = $trialBalanceService->exportCsv($tenant, $period);

        $this->assertTrue($report['is_balanced']);
        $this->assertSame(800.0, $report['total_debits']);
        $this->assertSame(800.0, $report['total_credits']);
        $this->assertCount(4, $report['groups']);
        $this->assertSame('asset', $report['groups'][0]['type']);
        $this->assertSame('1100', $report['groups'][0]['accounts'][0]['code']);
        $this->assertSame(500.0, $report['groups'][0]['accounts'][0]['balance']);
        $this->assertSame(300.0, $report['groups'][1]['accounts'][0]['balance']);
        $this->assertSame(500.0, $report['groups'][2]['accounts'][0]['balance']);
        $this->assertSame(300.0, $report['groups'][3]['accounts'][0]['balance']);
        $this->assertStringContainsString('"Account Type","Account Code","Account Name","Debit Total","Credit Total",Balance', $csv);
        $this->assertStringContainsString('asset,1100,Cash,500.00,0.00,500.00', $csv);
        $this->assertStringContainsString('revenue,4100,"Service Revenue",0.00,500.00,500.00', $csv);
        $this->assertStringNotContainsString('999.00', $csv);
    }

    /**
     * @return array{Tenant, User, FinancialPeriod}
     */
    private function createEnterpriseFixture(): array
    {
        $tenant = Tenant::factory()->create([
            'feature_flags' => [
                'enterprise_accounting' => true,
            ],
        ]);
        $user = User::factory()->forTenant($tenant)->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $period = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'January 2026',
            'period_type' => 'monthly',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'status' => 'open',
        ]);

        $this->assertNotNull($building);

        return [$tenant, $user, $period];
    }

    private function createLedgerAccount(Tenant $tenant, string $code, string $name, string $type): LedgerAccount
    {
        return LedgerAccount::query()->create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
        ]);
    }
}
