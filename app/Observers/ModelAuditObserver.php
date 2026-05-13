<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class ModelAuditObserver
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function created(Model $model): void
    {
        $this->auditService->recordModelEvent($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->auditService->recordModelEvent($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->auditService->recordModelEvent($model, 'deleted');
    }
}
