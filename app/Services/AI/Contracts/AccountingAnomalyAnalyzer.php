<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\Anomaly\DTOs\AccountingAnomalyInput;
use App\Services\AI\Anomaly\DTOs\AnomalyAnalysisResult;

interface AccountingAnomalyAnalyzer
{
    public function analyze(AccountingAnomalyInput $input): AnomalyAnalysisResult;
}
