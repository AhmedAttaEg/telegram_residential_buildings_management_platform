<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_queue_worker_runs_once_without_configuration_errors(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => null,
            'queue.failed.database' => config('database.default'),
        ]);

        $this->artisan('queue:work', ['--once' => true])
            ->assertExitCode(0);
    }
}
