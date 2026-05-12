<?php

namespace App\Services;

use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    public function generate(Tenant $tenant, ?FinancialPeriod $period = null): array
    {
        $query = JournalEntryLine::query()
            ->select([
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
                DB::raw('SUM(journal_entry_lines.debit_amount) as debit_total'),
                DB::raw('SUM(journal_entry_lines.credit_amount) as credit_total'),
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_entry_lines.ledger_account_id')
            ->where('journal_entry_lines.tenant_id', $tenant->id)
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->groupBy('ledger_accounts.id', 'ledger_accounts.code', 'ledger_accounts.name', 'ledger_accounts.type')
            ->orderBy('ledger_accounts.code');

        if ($period !== null) {
            $query->where('journal_entries.financial_period_id', $period->id);
        }

        $rows = $query->get();

        $accounts = $rows->map(function (object $row): array {
            $debitTotal = round((float) $row->debit_total, 2);
            $creditTotal = round((float) $row->credit_total, 2);

            return [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'debit_total' => $debitTotal,
                'credit_total' => $creditTotal,
                'balance' => $this->calculateBalance($row->type, $debitTotal, $creditTotal),
            ];
        });

        $groups = [];

        foreach (LedgerAccount::types() as $type) {
            $groupAccounts = $accounts->where('type', $type)->values();

            if ($groupAccounts->isEmpty()) {
                continue;
            }

            $groups[] = [
                'type' => $type,
                'accounts' => $groupAccounts->all(),
                'total_balance' => round((float) $groupAccounts->sum('balance'), 2),
            ];
        }

        $totalDebits = round((float) $accounts->sum('debit_total'), 2);
        $totalCredits = round((float) $accounts->sum('credit_total'), 2);

        return [
            'tenant_id' => $tenant->id,
            'financial_period_id' => $period?->id,
            'generated_at' => now()->toIso8601String(),
            'groups' => $groups,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => $totalDebits === $totalCredits,
        ];
    }

    public function exportCsv(Tenant $tenant, ?FinancialPeriod $period = null): string
    {
        $report = $this->generate($tenant, $period);
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, ['Account Type', 'Account Code', 'Account Name', 'Debit Total', 'Credit Total', 'Balance']);

        foreach ($report['groups'] as $group) {
            foreach ($group['accounts'] as $account) {
                fputcsv($stream, [
                    $group['type'],
                    $account['code'],
                    $account['name'],
                    number_format((float) $account['debit_total'], 2, '.', ''),
                    number_format((float) $account['credit_total'], 2, '.', ''),
                    number_format((float) $account['balance'], 2, '.', ''),
                ]);
            }
        }

        fputcsv($stream, []);
        fputcsv($stream, [
            'Totals',
            '',
            '',
            number_format((float) $report['total_debits'], 2, '.', ''),
            number_format((float) $report['total_credits'], 2, '.', ''),
            $report['is_balanced'] ? 'balanced' : 'unbalanced',
        ]);

        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    private function calculateBalance(string $type, float $debitTotal, float $creditTotal): float
    {
        if (in_array($type, [LedgerAccount::TYPE_ASSET, LedgerAccount::TYPE_EXPENSE], true)) {
            return round($debitTotal - $creditTotal, 2);
        }

        return round($creditTotal - $debitTotal, 2);
    }
}
