<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WorkOrder extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'assigned_to',
        'created_by',
        'work_order_number',
        'title',
        'description',
        'status',
        'scheduled_for',
        'started_at',
        'completed_at',
        'due_at',
        'sla_target_at',
        'sla_breached_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_at' => 'datetime',
            'sla_target_at' => 'datetime',
            'sla_breached_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $workOrder): void {
            if ($workOrder->status === null) {
                $workOrder->status = self::STATUS_OPEN;
            }

            if ($workOrder->work_order_number === null || $workOrder->work_order_number === '') {
                $entryMonth = Carbon::now()->format('Ym');
                $prefix = 'WO-'.$entryMonth.'-';
                $lastNumber = self::query()
                    ->where('tenant_id', $workOrder->tenant_id)
                    ->where('work_order_number', 'like', $prefix.'%')
                    ->orderByDesc('work_order_number')
                    ->value('work_order_number');

                $nextSequence = $lastNumber === null
                    ? 1
                    : ((int) substr($lastNumber, -6)) + 1;

                $workOrder->work_order_number = $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeForTenant(Builder $query, int|Tenant $tenant): void
    {
        $query->where('tenant_id', $tenant instanceof Tenant ? $tenant->id : $tenant);
    }
}
