<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LoggingConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (glob(storage_path('logs/*.log')) ?: [] as $logFile) {
            File::delete($logFile);
        }
    }

    public function test_log_smoke_test_writes_redacted_entries_to_all_structured_channels(): void
    {
        $this->artisan('app:log-smoke-test')
            ->expectsOutput('Log smoke test completed.')
            ->assertExitCode(0);

        $this->assertLogContains('laravel', '[REDACTED]');
        $this->assertLogContains('accounting', '[REDACTED]');
        $this->assertLogContains('audit', '[REDACTED]');
        $this->assertLogContains('api', '[REDACTED]');

        $this->assertLogDoesNotContain('laravel', 'top-secret');
        $this->assertLogDoesNotContain('accounting', 'accounting-token');
        $this->assertLogDoesNotContain('audit', 'super-secret');
        $this->assertLogDoesNotContain('api', 'api-secret');
    }

    public function test_json_api_exceptions_are_logged_to_the_api_channel(): void
    {
        Route::get('/api/test-error', function () {
            throw new \RuntimeException('API exploded');
        });

        $this->getJson('/api/test-error')->assertStatus(500);

        $this->assertLogContains('api', 'API request exception');
        $this->assertLogContains('api', 'API exploded');
    }

    private function assertLogContains(string $channel, string $expected): void
    {
        $contents = $this->readLogContents($channel);

        $this->assertStringContainsString($expected, $contents);
    }

    private function assertLogDoesNotContain(string $channel, string $expected): void
    {
        $contents = $this->readLogContents($channel);

        $this->assertStringNotContainsString($expected, $contents);
    }

    private function readLogContents(string $channel): string
    {
        $files = glob(storage_path("logs/{$channel}*.log")) ?: [];

        $this->assertNotEmpty($files, "No log files were created for channel [{$channel}].");

        return (string) file_get_contents($files[0]);
    }
}
