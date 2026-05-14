<?php

namespace App\Services\AI\Anomaly;

use App\Jobs\AnalyzeAccountingAnomaliesJob;
use App\Services\AI\Anomaly\DTOs\AccountingAnomalyInput;
use App\Services\AI\Contracts\AnomalyAnalysisDispatcher;

class QueueAnomalyAnalysisDispatcher implements AnomalyAnalysisDispatcher
{
    public function dispatch(AccountingAnomalyInput $input): void
    {
        AnalyzeAccountingAnomaliesJob::dispatch($input)
            ->onQueue((string) config('ai.queues.anomaly_analysis', 'default'));
    }
}
