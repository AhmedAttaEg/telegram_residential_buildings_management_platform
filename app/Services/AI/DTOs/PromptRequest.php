<?php

namespace App\Services\AI\DTOs;

final readonly class PromptRequest
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $task,
        public array $input,
        public array $context = [],
        public array $options = [],
    ) {
    }
}
