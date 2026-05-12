<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'building_id',
        'unit_number',
        'occupancy_status',
        'status',
        'floor_number',
        'unit_type',
        'bedrooms',
        'bathrooms',
        'area_value',
        'area_unit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'floor_number' => 'integer',
            'area_value' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function residents(): BelongsToMany
    {
        return $this->belongsToMany(Resident::class, 'apartment_residents')
            ->withPivot([
                'tenant_id',
                'tenancy_type',
                'occupancy_status',
                'ownership_percentage',
                'move_in_at',
                'move_out_at',
                'is_primary_contact',
            ])
            ->withTimestamps();
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function debitTransactions(): HasMany
    {
        return $this->hasMany(DebitTransaction::class);
    }

    public function expenseSplits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeForTenant(Builder $query, int|Tenant $tenant): void
    {
        $query->where('tenant_id', $tenant instanceof Tenant ? $tenant->id : $tenant);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeOccupied(Builder $query): void
    {
        $query->where('occupancy_status', 'occupied');
    }

    public function scopeVacant(Builder $query): void
    {
        $query->where('occupancy_status', 'vacant');
    }

    public function getDisplayLabelAttribute(): string
    {
        $buildingName = $this->relationLoaded('building') ? $this->building?->name : null;

        return trim(collect([$buildingName, 'Unit '.$this->unit_number])->filter()->implode(' - '));
    }
}
