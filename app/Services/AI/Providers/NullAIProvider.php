<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\DTOs\PromptRequest;
use App\Services\AI\DTOs\ProviderResponse;

class NullAIProvider implements AIProvider
{
    public function analyze(PromptRequest $prompt): ProviderResponse
    {
        return new ProviderResponse(
            provider: 'null',
            model: (string) config('ai.providers.null.model', 'null-model'),
            items: [],
            metadata: [
                'enabled' => (bool) config('ai.enabled', false),
                'task' => $prompt->task,
                'reason' => 'No external AI provider is configured.',
            ],
        );
    }
}
