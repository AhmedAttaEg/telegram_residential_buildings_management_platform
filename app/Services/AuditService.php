<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * @var list<string>
     */
    private array $excludedAttributes = [
        'created_at',
        'updated_at',
        'remember_token',
        'password',
    ];

    public function recordModelEvent(Model $model, string $action): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        [$oldValues, $newValues] = match ($action) {
            'created' => [null, $this->currentTrackedValues($model)],
            'updated' => $this->updatedValues($model),
            'deleted' => [$this->originalTrackedValues($model), null],
            default => [null, null],
        };

        if ($action === 'updated' && $oldValues === [] && $newValues === []) {
            return;
        }

        AuditLog::query()->create([
            'tenant_id' => $this->tenantIdFor($model, $action),
            'event' => $this->eventName($model, $action),
            'actor_type' => $this->actor()?->getMorphClass(),
            'actor_id' => $this->actor()?->getKey(),
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'source' => $this->source(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function recordCustomEvent(
        Model $subject,
        string $event,
        ?array $oldValues,
        ?array $newValues,
        array $metadata = [],
    ): void {
        AuditLog::query()->create([
            'tenant_id' => $this->tenantIdFor($subject),
            'event' => $event,
            'actor_type' => $this->actor()?->getMorphClass(),
            'actor_id' => $this->actor()?->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);
    }

    public function source(): string
    {
        return app()->runningInConsole() ? 'system' : 'http';
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function updatedValues(Model $model): array
    {
        $changes = collect($model->getChanges())
            ->except($this->excludedAttributes)
            ->keys()
            ->all();

        $trackedKeys = array_values(array_intersect($this->trackedKeys($model), $changes));

        $oldValues = [];
        $newValues = [];

        foreach ($trackedKeys as $key) {
            $oldValues[$key] = $model->getOriginal($key);
            $newValues[$key] = $model->getAttribute($key);
        }

        return [$oldValues, $newValues];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentTrackedValues(Model $model): array
    {
        return $this->filterTrackedValues($model->getAttributes(), $this->trackedKeys($model));
    }

    /**
     * @return array<string, mixed>
     */
    private function originalTrackedValues(Model $model): array
    {
        return $this->filterTrackedValues($model->getOriginal(), $this->trackedKeys($model));
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function filterTrackedValues(array $values, array $keys): array
    {
        $filtered = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $filtered[$key] = $values[$key];
        }

        return $filtered;
    }

    /**
     * @return list<string>
     */
    private function trackedKeys(Model $model): array
    {
        $fillable = $model->getFillable();
        $keys = $fillable === [] ? array_keys($model->getAttributes()) : $fillable;

        return array_values(array_diff($keys, $this->excludedAttributes));
    }

    private function eventName(Model $model, string $action): string
    {
        return str($model::class)
            ->afterLast('\\')
            ->snake()
            ->append('_'.$action)
            ->toString();
    }

    private function tenantIdFor(Model $model, ?string $action = null): ?int
    {
        if ($model instanceof Tenant) {
            if ($action === 'deleted' || ! $model->exists) {
                return null;
            }

            return (int) $model->getKey();
        }

        $tenantId = $model->getAttribute('tenant_id');

        return $tenantId === null ? null : (int) $tenantId;
    }

    private function actor(): ?Model
    {
        $actor = auth()->user();

        return $actor instanceof Model ? $actor : null;
    }
}
