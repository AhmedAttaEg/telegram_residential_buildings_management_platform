<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\PromptRequest;
use App\Services\AI\DTOs\ProviderResponse;

interface AIProvider
{
    public function analyze(PromptRequest $prompt): ProviderResponse;
}
