<?php

namespace App\Services\AI\Anomaly\DTOs;

final readonly class AnomalyAlert
{
    /**
     * @param  array<int, AnomalyFinding>  $findings
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $source,
        public string $severity,
        public string $message,
        public string $recommendedAction,
        public array $findings = [],
        public array $metadata = [],
    ) {
    }
}
