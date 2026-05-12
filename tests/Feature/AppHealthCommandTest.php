<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppHealthCommandTest extends TestCase
{
    public function test_health_command_succeeds_when_application_and_database_are_available(): void
    {
        $this->artisan('app:health')
            ->expectsOutput('Health report')
            ->expectsOutput('Application: OK')
            ->expectsOutput('Database: OK (sqlite)')
            ->assertExitCode(0);
    }

    public function test_health_command_fails_when_database_is_unreachable(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => database_path('missing/health.sqlite'),
        ]);

        $this->artisan('app:health')
            ->expectsOutput('Health report')
            ->expectsOutput('Application: OK')
            ->expectsOutput('Database: FAIL (sqlite)')
            ->assertExitCode(1);
    }
}
