<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')
            ->assertExitCode(0);
    }

    public function test_fresh_migrations_boot_successfully(): void
    {
        $this->artisan('migrate:fresh')
            ->assertExitCode(0);
    }

    public function test_key_tables_exist_after_migration(): void
    {
        foreach ([
            'tenants',
            'users',
            'roles',
            'permissions',
            'role_user',
            'permission_role',
            'financial_periods',
            'wallet_transactions',
            'debit_transactions',
            'expenses',
            'expense_splits',
            'expense_payments',
            'ledger_accounts',
            'journal_entries',
            'journal_entry_lines',
            'tenant_subscriptions',
            'jobs',
            'notifications',
            'audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
        }
    }

    public function test_critical_indexes_exist_for_accounting_and_tenant_queries(): void
    {
        $this->assertTrue(Schema::hasIndex('wallet_transactions', ['tenant_id', 'apartment_id']));
        $this->assertTrue(Schema::hasIndex('wallet_transactions', ['tenant_id', 'apartment_id', 'id']));
        $this->assertTrue(Schema::hasIndex('wallet_transactions', ['tenant_id', 'financial_period_id']));
        $this->assertTrue(Schema::hasIndex('wallet_transactions', ['tenant_id', 'type']));
        $this->assertTrue(Schema::hasIndex('wallet_transactions', ['apartment_id', 'reversed_at']));
        $this->assertTrue(Schema::hasIndex('wallet_transactions', ['reversal_of_id']));

        $this->assertTrue(Schema::hasIndex('debit_transactions', ['tenant_id', 'apartment_id']));
        $this->assertTrue(Schema::hasIndex('debit_transactions', ['tenant_id', 'apartment_id', 'id']));
        $this->assertTrue(Schema::hasIndex('debit_transactions', ['tenant_id', 'financial_period_id']));
        $this->assertTrue(Schema::hasIndex('debit_transactions', ['tenant_id', 'type']));
        $this->assertTrue(Schema::hasIndex('debit_transactions', ['reversal_of_id']));

        $this->assertTrue(Schema::hasIndex('expense_payments', ['tenant_id', 'expense_split_id']));
        $this->assertTrue(Schema::hasIndex('expense_payments', ['tenant_id', 'wallet_transaction_id']));
        $this->assertTrue(Schema::hasIndex('expense_payments', ['reversal_of_id']));

        $this->assertTrue(Schema::hasIndex('expense_splits', ['apartment_id', 'is_paid']));
        $this->assertTrue(Schema::hasIndex('expense_splits', ['apartment_id', 'is_reversed']));
        $this->assertTrue(Schema::hasIndex('expense_splits', ['tenant_id', 'building_id']));
        $this->assertTrue(Schema::hasIndex('expense_splits', ['tenant_id', 'financial_period_id']));
        $this->assertTrue(Schema::hasIndex('expense_splits', ['tenant_id', 'apartment_id', 'is_confirmed', 'is_paid', 'is_reversed', 'id']));

        $this->assertTrue(Schema::hasIndex('journal_entries', ['tenant_id', 'status']));
        $this->assertTrue(Schema::hasIndex('journal_entries', ['tenant_id', 'financial_period_id']));
        $this->assertTrue(Schema::hasIndex('journal_entries', ['tenant_id', 'entry_date']));
        $this->assertTrue(Schema::hasIndex('journal_entries', ['tenant_id', 'status', 'financial_period_id']));

        $this->assertTrue(Schema::hasIndex('journal_entry_lines', ['tenant_id', 'journal_entry_id']));
        $this->assertTrue(Schema::hasIndex('journal_entry_lines', ['tenant_id', 'ledger_account_id']));

        $this->assertTrue(Schema::hasIndex('users', ['tenant_id', 'status']));
        $this->assertTrue(Schema::hasIndex('role_user', ['user_id', 'role_id']));
        $this->assertTrue(Schema::hasIndex('permission_role', ['role_id', 'permission_id']));
        $this->assertTrue(Schema::hasIndex('notifications', ['notifiable_type', 'type', 'notifiable_id']));

        $this->assertTrue(Schema::hasIndex('tenant_subscriptions', ['tenant_id', 'status']));
        $this->assertTrue(Schema::hasIndex('tenant_subscriptions', ['tenant_id', 'renews_at']));
        $this->assertTrue(Schema::hasIndex('tenant_subscriptions', ['tenant_id', 'ends_at']));
        $this->assertTrue(Schema::hasIndex('tenant_subscriptions', ['status', 'renews_at']));
        $this->assertTrue(Schema::hasIndex('tenant_subscriptions', ['status', 'ends_at']));

        $this->assertTrue(Schema::hasIndex('tenants', ['status', 'subscription_status', 'trial_ends_at', 'reminder_sent_at']));
        $this->assertTrue(Schema::hasIndex('tenants', ['status', 'subscription_status', 'subscription_ends_at', 'reminder_sent_at']));
        $this->assertTrue(Schema::hasIndex('tenants', ['status', 'subscription_status', 'grace_ends_at']));
        $this->assertTrue(Schema::hasIndex('tenants', ['status', 'subscription_status', 'suspended_at']));

        $this->assertTrue(Schema::hasIndex('audit_logs', ['tenant_id', 'event']));
    }

    public function test_critical_foreign_keys_exist_for_accounting_and_platform_integrity(): void
    {
        $this->assertForeignKey('wallet_transactions', ['tenant_id'], 'tenants', ['id'], 'cascade');
        $this->assertForeignKey('wallet_transactions', ['apartment_id'], 'apartments', ['id'], 'cascade');
        $this->assertForeignKey('wallet_transactions', ['resident_id'], 'residents', ['id'], 'set null');
        $this->assertForeignKey('wallet_transactions', ['financial_period_id'], 'financial_periods', ['id'], 'set null');
        $this->assertForeignKey('wallet_transactions', ['reversal_of_id'], 'wallet_transactions', ['id'], 'set null');

        $this->assertForeignKey('debit_transactions', ['tenant_id'], 'tenants', ['id'], 'cascade');
        $this->assertForeignKey('debit_transactions', ['apartment_id'], 'apartments', ['id'], 'cascade');
        $this->assertForeignKey('debit_transactions', ['resident_id'], 'residents', ['id'], 'set null');
        $this->assertForeignKey('debit_transactions', ['financial_period_id'], 'financial_periods', ['id'], 'set null');
        $this->assertForeignKey('debit_transactions', ['reversal_of_id'], 'debit_transactions', ['id'], 'set null');

        $this->assertForeignKey('expense_payments', ['tenant_id'], 'tenants', ['id'], 'cascade');
        $this->assertForeignKey('expense_payments', ['expense_split_id'], 'expense_splits', ['id'], 'cascade');
        $this->assertForeignKey('expense_payments', ['wallet_transaction_id'], 'wallet_transactions', ['id'], 'cascade');
        $this->assertForeignKey('expense_payments', ['debit_transaction_id'], 'debit_transactions', ['id'], 'set null');
        $this->assertForeignKey('expense_payments', ['reversal_of_id'], 'expense_payments', ['id'], 'set null');

        $this->assertForeignKey('journal_entries', ['tenant_id'], 'tenants', ['id'], 'cascade');
        $this->assertForeignKey('journal_entries', ['financial_period_id'], 'financial_periods', ['id'], 'set null');
        $this->assertForeignKey('journal_entry_lines', ['journal_entry_id'], 'journal_entries', ['id'], 'cascade');
        $this->assertForeignKey('journal_entry_lines', ['ledger_account_id'], 'ledger_accounts', ['id'], 'cascade');

        $this->assertForeignKey('tenant_subscriptions', ['tenant_id'], 'tenants', ['id'], 'cascade');
        $this->assertForeignKey('tenant_subscriptions', ['subscription_plan_id'], 'subscription_plans', ['id'], 'cascade');
        $this->assertForeignKey('audit_logs', ['tenant_id'], 'tenants', ['id'], 'set null');
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $foreignColumns
     */
    private function assertForeignKey(
        string $table,
        array $columns,
        string $foreignTable,
        array $foreignColumns,
        string $onDelete,
    ): void {
        $match = collect(Schema::getForeignKeys($table))->first(function (array $foreignKey) use ($columns, $foreignTable, $foreignColumns, $onDelete): bool {
            return $foreignKey['columns'] === $columns
                && $foreignKey['foreign_table'] === $foreignTable
                && $foreignKey['foreign_columns'] === $foreignColumns
                && strtolower((string) $foreignKey['on_delete']) === $onDelete;
        });

        $this->assertNotNull(
            $match,
            sprintf(
                'Expected foreign key on [%s](%s) -> [%s](%s) with on delete [%s].',
                $table,
                implode(', ', $columns),
                $foreignTable,
                implode(', ', $foreignColumns),
                $onDelete,
            ),
        );
    }
}
