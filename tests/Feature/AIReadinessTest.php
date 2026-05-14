<?php

namespace Tests\Feature;

use App\Events\AccountingAnomalyAnalysisCompleted;
use App\Jobs\AnalyzeAccountingAnomaliesJob;
use App\Services\AI\Anomaly\DTOs\AccountingAnomalyInput;
use App\Services\AI\Anomaly\DTOs\AnomalyAnalysisResult;
use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\Contracts\AccountingAnomalyAnalyzer;
use App\Services\AI\Contracts\AnomalyAnalysisDispatcher;
use App\Services\AI\DTOs\PromptRequest;
use App\Services\AI\DTOs\ProviderResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AIReadinessTest extends TestCase
{
    public function test_ai_contracts_resolve_through_the_container(): void
    {
        $this->assertInstanceOf(AIProvider::class, app(AIProvider::class));
        $this->assertInstanceOf(AccountingAnomalyAnalyzer::class, app(AccountingAnomalyAnalyzer::class));
        $this->assertInstanceOf(AnomalyAnalysisDispatcher::class, app(AnomalyAnalysisDispatcher::class));
    }

    public function test_ai_configuration_defaults_are_available(): void
    {
        $this->assertFalse(config('ai.enabled'));
        $this->assertSame('null', config('ai.default_provider'));
        $this->assertSame('null-model', config('ai.providers.null.model'));
        $this->assertSame('default', config('ai.queues.anomaly_analysis'));
    }

    public function test_accounting_anomaly_input_is_queue_serialization_safe(): void
    {
        $input = new AccountingAnomalyInput(
            tenantId: 15,
            currency: 'EGP',
            entries: [
                ['journal_entry_id' => 99, 'amount' => 1750.5],
            ],
            context: ['period' => '2026-01'],
        );

        $restored = unserialize(serialize($input));

        $this->assertInstanceOf(AccountingAnomalyInput::class, $restored);
        $this->assertSame(15, $restored->tenantId);
        $this->assertSame('EGP', $restored->currency);
        $this->assertSame(99, $restored->entries[0]['journal_entry_id']);
        $this->assertSame('2026-01', $restored->context['period']);
    }

    public function test_dispatcher_pushes_anomaly_analysis_job_to_the_configured_queue(): void
    {
        Queue::fake();

        config([
            'ai.queues.anomaly_analysis' => 'ai-analysis',
        ]);

        $input = new AccountingAnomalyInput(
            tenantId: 22,
            currency: 'EGP',
            entries: [
                ['journal_entry_id' => 1, 'amount' => 5000],
            ],
        );

        app(AnomalyAnalysisDispatcher::class)->dispatch($input);

        Queue::assertPushed(AnalyzeAccountingAnomaliesJob::class, function (AnalyzeAccountingAnomaliesJob $job): bool {
            return $job->queue === 'ai-analysis'
                && $job->input->tenantId === 22
                && $job->input->entries[0]['journal_entry_id'] === 1;
        });
    }

    public function test_anomaly_job_uses_provider_backed_analyzer_and_emits_completed_event(): void
    {
        Event::fake([AccountingAnomalyAnalysisCompleted::class]);

        app()->bind(AIProvider::class, FakeAIProvider::class);

        $job = new AnalyzeAccountingAnomaliesJob(new AccountingAnomalyInput(
            tenantId: 7,
            currency: 'EGP',
            entries: [
                ['journal_entry_id' => 1201, 'amount' => 10000, 'type' => 'expense'],
            ],
            context: ['period' => '2026-02'],
        ));

        $result = $job->handle(app(AccountingAnomalyAnalyzer::class));

        $this->assertInstanceOf(AnomalyAnalysisResult::class, $result);
        $this->assertSame(7, $result->tenantId);
        $this->assertCount(1, $result->alerts);
        $this->assertSame('high', $result->alerts[0]->severity);
        $this->assertSame('Review the duplicate supplier invoice immediately.', $result->alerts[0]->recommendedAction);
        $this->assertSame('duplicate_invoice', $result->alerts[0]->findings[0]->code);

        Event::assertDispatched(AccountingAnomalyAnalysisCompleted::class, function (AccountingAnomalyAnalysisCompleted $event): bool {
            return $event->result->tenantId === 7
                && $event->result->alerts[0]->severity === 'high';
        });
    }
}

class FakeAIProvider implements AIProvider
{
    public function analyze(PromptRequest $prompt): ProviderResponse
    {
        return new ProviderResponse(
            provider: 'fake',
            model: 'fake-model',
            items: [
                [
                    'source' => 'ai.accounting_anomaly_detection',
                    'severity' => 'high',
                    'message' => 'Potential duplicate supplier invoice detected.',
                    'recommended_action' => 'Review the duplicate supplier invoice immediately.',
                    'findings' => [
                        [
                            'code' => 'duplicate_invoice',
                            'summary' => 'The same supplier invoice appears to have been posted twice.',
                            'severity' => 'high',
                            'evidence' => [
                                'journal_entry_id' => $prompt->input['entries'][0]['journal_entry_id'] ?? null,
                            ],
                        ],
                    ],
                ],
            ],
            metadata: [
                'task' => $prompt->task,
            ],
        );
    }
}
