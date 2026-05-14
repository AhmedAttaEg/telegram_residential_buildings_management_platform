<?php

namespace App\Jobs;

use App\Events\AccountingAnomalyAnalysisCompleted;
use App\Services\AI\Anomaly\DTOs\AccountingAnomalyInput;
use App\Services\AI\Anomaly\DTOs\AnomalyAnalysisResult;
use App\Services\AI\Contracts\AccountingAnomalyAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeAccountingAnomaliesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AccountingAnomalyInput $input,
    ) {
        $this->onQueue((string) config('ai.queues.anomaly_analysis', 'default'));
    }

    public function handle(AccountingAnomalyAnalyzer $analyzer): AnomalyAnalysisResult
    {
        $result = $analyzer->analyze($this->input);

        event(new AccountingAnomalyAnalysisCompleted($result));

        return $result;
    }
}
