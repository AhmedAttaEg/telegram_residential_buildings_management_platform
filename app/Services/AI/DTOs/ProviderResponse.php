<?php

namespace App\Services\AI\DTOs;

final readonly class ProviderResponse
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $provider,
        public string $model,
        public array $items = [],
        public array $metadata = [],
    ) {
    }
}
