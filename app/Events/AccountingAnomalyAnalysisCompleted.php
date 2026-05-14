<?php

namespace App\Events;

use App\Services\AI\Anomaly\DTOs\AnomalyAnalysisResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountingAnomalyAnalysisCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AnomalyAnalysisResult $result,
    ) {
    }
}
