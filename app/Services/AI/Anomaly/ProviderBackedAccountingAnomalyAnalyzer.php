<?php

namespace App\Services\AI\Anomaly;

use App\Services\AI\Anomaly\DTOs\AccountingAnomalyInput;
use App\Services\AI\Anomaly\DTOs\AnomalyAlert;
use App\Services\AI\Anomaly\DTOs\AnomalyAnalysisResult;
use App\Services\AI\Anomaly\DTOs\AnomalyFinding;
use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\Contracts\AccountingAnomalyAnalyzer;
use App\Services\AI\DTOs\PromptRequest;

class ProviderBackedAccountingAnomalyAnalyzer implements AccountingAnomalyAnalyzer
{
    public function __construct(
        private readonly AIProvider $provider,
    ) {
    }

    public function analyze(AccountingAnomalyInput $input): AnomalyAnalysisResult
    {
        $response = $this->provider->analyze(new PromptRequest(
            task: 'accounting.anomaly_detection',
            input: [
                'tenant_id' => $input->tenantId,
                'currency' => $input->currency,
                'entries' => $input->entries,
            ],
            context: $input->context,
            options: [
                'alert_schema' => [
                    'severity',
                    'message',
                    'recommended_action',
                    'findings',
                ],
            ],
        ));

        $alerts = [];

        foreach ($response->items as $item) {
            $findings = [];

            foreach ((array) ($item['findings'] ?? []) as $finding) {
                if (! is_array($finding)) {
                    continue;
                }

                $findings[] = new AnomalyFinding(
                    code: (string) ($finding['code'] ?? 'unknown'),
                    summary: (string) ($finding['summary'] ?? 'An accounting anomaly was detected.'),
                    severity: (string) ($finding['severity'] ?? ($item['severity'] ?? 'medium')),
                    evidence: (array) ($finding['evidence'] ?? []),
                );
            }

            $alerts[] = new AnomalyAlert(
                source: (string) ($item['source'] ?? 'ai.accounting_anomaly_detection'),
                severity: (string) ($item['severity'] ?? 'medium'),
                message: (string) ($item['message'] ?? 'Potential accounting anomaly detected.'),
                recommendedAction: (string) ($item['recommended_action'] ?? 'Review the flagged accounting records.'),
                findings: $findings,
                metadata: [
                    'provider' => $response->provider,
                    'model' => $response->model,
                ] + (array) ($item['metadata'] ?? []),
            );
        }

        if ($alerts === []) {
            $alerts[] = new AnomalyAlert(
                source: 'ai.accounting_anomaly_detection',
                severity: 'info',
                message: 'Accounting anomaly analysis completed without detected anomalies.',
                recommendedAction: 'No action is required.',
                metadata: [
                    'provider' => $response->provider,
                    'model' => $response->model,
                ],
            );
        }

        return new AnomalyAnalysisResult(
            tenantId: $input->tenantId,
            alerts: $alerts,
            metadata: [
                'provider' => $response->provider,
                'model' => $response->model,
            ] + $response->metadata,
        );
    }
}
