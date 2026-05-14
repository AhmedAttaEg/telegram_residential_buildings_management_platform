<?php

namespace App\Services\AI\Anomaly\DTOs;

final readonly class AnomalyAnalysisResult
{
    /**
     * @param  array<int, AnomalyAlert>  $alerts
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $tenantId,
        public array $alerts,
        public array $metadata = [],
    ) {
    }
}
