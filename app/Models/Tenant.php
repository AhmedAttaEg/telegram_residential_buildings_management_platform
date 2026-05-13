<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'subscription_status',
        'subscription_plan',
        'trial_ends_at',
        'grace_ends_at',
        'subscription_ends_at',
        'suspended_at',
        'suspension_reason',
        'reminder_sent_at',
        'feature_flags',
        'brand_name',
        'logo_path',
        'primary_color',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feature_flags' => 'array',
            'trial_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function financialPeriods(): HasMany
    {
        return $this->hasMany(FinancialPeriod::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function debitTransactions(): HasMany
    {
        return $this->hasMany(DebitTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseSplits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function expensePayments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    public function ledgerAccounts(): HasMany
    {
        return $this->hasMany(LedgerAccount::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription(): HasMany
    {
        return $this->hasMany(TenantSubscription::class)
            ->whereIn('status', [
                TenantSubscription::STATUS_TRIAL,
                TenantSubscription::STATUS_ACTIVE,
                TenantSubscription::STATUS_GRACE,
                TenantSubscription::STATUS_SUSPENDED,
            ])
            ->latest('starts_at');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeSuspended(Builder $query): void
    {
        $query->where('status', 'suspended');
    }

    public function scopeSubscriptionActive(Builder $query): void
    {
        $now = now();

        $query->where('status', 'active')
            ->whereIn('subscription_status', ['trial', 'active', 'grace'])
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '>=', $now);
            });
    }

    public function scopeDueTrialExpirationReminders(Builder $query, Carbon $startsAt, Carbon $endsAt): void
    {
        $query->where('status', 'active')
            ->where('subscription_status', 'trial')
            ->whereNull('reminder_sent_at')
            ->whereBetween('trial_ends_at', [$startsAt, $endsAt]);
    }

    public function scopeDueActiveExpirationReminders(Builder $query, Carbon $startsAt, Carbon $endsAt): void
    {
        $query->where('status', 'active')
            ->where('subscription_status', 'active')
            ->whereNull('reminder_sent_at')
            ->whereBetween('subscription_ends_at', [$startsAt, $endsAt]);
    }

    public function scopeDueGraceNotifications(Builder $query): void
    {
        $query->where('status', 'active')
            ->where('subscription_status', 'grace')
            ->whereNotNull('grace_ends_at');
    }

    public function scopeDueSuspensionNotifications(Builder $query): void
    {
        $query->where('status', 'suspended')
            ->where('subscription_status', 'suspended')
            ->whereNotNull('suspended_at');
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended' || $this->suspended_at !== null;
    }

    public function hasActiveSubscription(?Carbon $now = null): bool
    {
        $now ??= now();

        if ($this->isSuspended()) {
            return false;
        }

        return match ($this->subscription_status) {
            'trial' => $this->trial_ends_at === null || $this->trial_ends_at->gte($now),
            'grace' => $this->grace_ends_at !== null && $this->grace_ends_at->gte($now),
            'active' => $this->subscription_ends_at === null || $this->subscription_ends_at->gte($now),
            default => false,
        };
    }

    public function isAccessible(?Carbon $now = null): bool
    {
        return $this->status === 'active' && $this->hasActiveSubscription($now);
    }

    public function featureFlags(): array
    {
        $defaults = config('tenant.features', []);
        $flags = $this->feature_flags ?? [];

        return array_replace($defaults, $flags);
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) ($this->featureFlags()[$feature] ?? false);
    }
}
