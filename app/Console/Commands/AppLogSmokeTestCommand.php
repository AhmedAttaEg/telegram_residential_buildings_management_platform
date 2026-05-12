<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AppLogSmokeTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:log-smoke-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write representative entries to the configured application log channels.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::channel('daily')->info('Application smoke test', [
            'component' => 'bootstrap',
            'password' => 'top-secret',
        ]);

        Log::channel('accounting')->info('Accounting smoke test', [
            'ledger' => 'general',
            'token' => 'accounting-token',
        ]);

        Log::channel('audit')->info('Audit smoke test', [
            'actor' => 'system',
            'authorization' => 'Bearer super-secret',
        ]);

        Log::channel('api')->warning('API smoke test', [
            'endpoint' => '/api/health',
            'client_secret' => 'api-secret',
        ]);

        $this->info('Log smoke test completed.');

        return self::SUCCESS;
    }
}
