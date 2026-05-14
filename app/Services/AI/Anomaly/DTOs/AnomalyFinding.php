<?php

namespace App\Services\AI\Anomaly\DTOs;

final readonly class AnomalyFinding
{
    /**
     * @param  array<string, mixed>  $evidence
     */
    public function __construct(
        public string $code,
        public string $summary,
        public string $severity,
        public array $evidence = [],
    ) {
    }
}
