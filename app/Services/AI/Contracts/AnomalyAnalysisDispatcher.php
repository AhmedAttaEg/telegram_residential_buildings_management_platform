<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\Anomaly\DTOs\AccountingAnomalyInput;

interface AnomalyAnalysisDispatcher
{
    public function dispatch(AccountingAnomalyInput $input): void;
}
