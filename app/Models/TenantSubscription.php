<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    use HasFactory;

    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_GRACE = 'grace';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'trial_ends_at',
        'grace_ends_at',
        'renews_at',
        'ends_at',
        'cancelled_at',
        'suspended_at',
        'reminder_sent_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_TRIAL,
            self::STATUS_ACTIVE,
            self::STATUS_GRACE,
            self::STATUS_EXPIRED,
            self::STATUS_SUSPENDED,
            self::STATUS_CANCELLED,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function scopeForTenant(Builder $query, int|Tenant $tenant): void
    {
        $query->where('tenant_id', $tenant instanceof Tenant ? $tenant->id : $tenant);
    }

    public function scopeCurrentLifecycle(Builder $query): void
    {
        $query->whereIn('status', [
            self::STATUS_TRIAL,
            self::STATUS_ACTIVE,
            self::STATUS_GRACE,
            self::STATUS_SUSPENDED,
        ]);
    }
}
