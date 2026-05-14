<?php

namespace App\Services\AI\Anomaly\DTOs;

final readonly class AccountingAnomalyInput
{
    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $tenantId,
        public string $currency,
        public array $entries,
        public array $context = [],
    ) {
    }
}
