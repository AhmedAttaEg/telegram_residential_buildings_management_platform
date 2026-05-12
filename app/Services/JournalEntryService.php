<?php

namespace App\Services;

use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Tenant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $attributes
     */
    public function createDraftEntry(
        Tenant $tenant,
        User $actor,
        FinancialPeriod $period,
        array $lines,
        array $attributes = [],
    ): JournalEntry {
        $this->assertActorCanOperate($tenant, $actor);
        $this->assertPeriodBelongsToTenant($tenant, $period);

        $validatedLines = $this->validateLines($tenant, $lines);
        $entryDate = Carbon::parse($attributes['entry_date'] ?? $period->starts_at ?? now())->toDateString();

        return DB::transaction(function () use ($tenant, $actor, $period, $validatedLines, $attributes, $entryDate): JournalEntry {
            $entry = JournalEntry::query()->create([
                'tenant_id' => $tenant->id,
                'financial_period_id' => $period->id,
                'entry_number' => $this->generateEntryNumber($tenant, $entryDate),
                'status' => JournalEntry::STATUS_DRAFT,
                'entry_date' => $entryDate,
                'description' => $attributes['description'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($validatedLines as $line) {
                $entry->lines()->create([
                    'tenant_id' => $tenant->id,
                    'ledger_account_id' => $line['account']->id,
                    'description' => $line['description'],
                    'debit_amount' => $line['debit_amount'],
                    'credit_amount' => $line['credit_amount'],
                ]);
            }

            return $entry->load(['lines.ledgerAccount', 'financialPeriod', 'createdBy']);
        });
    }

    public function postEntry(JournalEntry $entry, User $actor): JournalEntry
    {
        $entry->loadMissing([
            'tenant',
            'financialPeriod',
            'lines.ledgerAccount',
        ]);

        if ($entry->status === JournalEntry::STATUS_POSTED) {
            throw new DomainException('Journal entry has already been posted.');
        }

        $this->assertActorCanOperate($entry->tenant, $actor);
        $this->walletService->assertPeriodWritable($entry->financialPeriod);
        $this->validateLoadedLines($entry->tenant, $entry->lines->all());

        $entry->forceFill([
            'status' => JournalEntry::STATUS_POSTED,
            'posted_at' => now(),
        ])->save();

        return $entry->refresh()->load(['lines.ledgerAccount', 'financialPeriod', 'createdBy']);
    }

    private function assertActorCanOperate(Tenant $tenant, User $actor): void
    {
        if ($actor->tenant_id !== null && $actor->tenant_id !== $tenant->id) {
            throw new DomainException('Actor tenant mismatch.');
        }
    }

    private function assertPeriodBelongsToTenant(Tenant $tenant, FinancialPeriod $period): void
    {
        if ($period->tenant_id !== $tenant->id) {
            throw new DomainException('Financial period tenant mismatch.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{account: LedgerAccount, description: ?string, debit_amount: float, credit_amount: float}>
     */
    private function validateLines(Tenant $tenant, array $lines): array
    {
        if (count($lines) < 2) {
            throw new DomainException('Journal entry must contain at least two lines.');
        }

        $validated = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($lines as $line) {
            $accountId = $line['ledger_account_id'] ?? null;
            $account = LedgerAccount::query()->find($accountId);

            if (! $account instanceof LedgerAccount) {
                throw new DomainException('Journal entry line references an invalid ledger account.');
            }

            if ($account->tenant_id !== $tenant->id) {
                throw new DomainException('Journal entry line account tenant mismatch.');
            }

            $debitAmount = round((float) ($line['debit_amount'] ?? 0), 2);
            $creditAmount = round((float) ($line['credit_amount'] ?? 0), 2);

            $this->assertLineAmounts($debitAmount, $creditAmount);

            $debitTotal += $debitAmount;
            $creditTotal += $creditAmount;

            $validated[] = [
                'account' => $account,
                'description' => $line['description'] ?? null,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
            ];
        }

        if (round($debitTotal, 2) !== round($creditTotal, 2)) {
            throw new DomainException('Journal entry is not balanced.');
        }

        return $validated;
    }

    /**
     * @param  array<int, \App\Models\JournalEntryLine>  $lines
     */
    private function validateLoadedLines(Tenant $tenant, array $lines): void
    {
        if (count($lines) < 2) {
            throw new DomainException('Journal entry must contain at least two lines.');
        }

        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($lines as $line) {
            if ($line->tenant_id !== $tenant->id || $line->ledgerAccount?->tenant_id !== $tenant->id) {
                throw new DomainException('Journal entry line account tenant mismatch.');
            }

            $debitAmount = round((float) $line->debit_amount, 2);
            $creditAmount = round((float) $line->credit_amount, 2);

            $this->assertLineAmounts($debitAmount, $creditAmount);

            $debitTotal += $debitAmount;
            $creditTotal += $creditAmount;
        }

        if (round($debitTotal, 2) !== round($creditTotal, 2)) {
            throw new DomainException('Journal entry is not balanced.');
        }
    }

    private function assertLineAmounts(float $debitAmount, float $creditAmount): void
    {
        if ($debitAmount > 0 && $creditAmount > 0) {
            throw new DomainException('Journal entry line cannot contain both debit and credit amounts.');
        }

        if ($debitAmount <= 0 && $creditAmount <= 0) {
            throw new DomainException('Journal entry line must contain either a debit or credit amount.');
        }
    }

    private function generateEntryNumber(Tenant $tenant, string $entryDate): string
    {
        $prefix = 'JE-'.Carbon::parse($entryDate)->format('Ym').'-';
        $lastEntryNumber = JournalEntry::query()
            ->forTenant($tenant)
            ->where('entry_number', 'like', $prefix.'%')
            ->orderByDesc('entry_number')
            ->value('entry_number');

        $nextSequence = $lastEntryNumber === null
            ? 1
            : ((int) substr($lastEntryNumber, -6)) + 1;

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }
}
