<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class OperationsConfigurationTest extends TestCase
{
    public function test_hostinger_environment_template_is_complete_against_the_example_contract(): void
    {
        $this->artisan('app:env-check', [
            '--file' => '.env.hostinger.example',
        ])
            ->expectsOutput('Environment variables are complete.')
            ->assertExitCode(0);
    }

    public function test_scheduler_registers_operations_commands(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->map(fn (object $event): string => $event->command)
            ->filter()
            ->values();

        $this->assertTrue($events->contains(fn (string $command): bool => str_contains($command, 'queue:prune-failed --hours=168')));
        $this->assertTrue($events->contains(fn (string $command): bool => str_contains($command, 'subscriptions:send-reminders')));
        $this->assertTrue($events->contains(fn (string $command): bool => str_contains($command, 'backups:run')));
        $this->assertTrue($events->contains(fn (string $command): bool => str_contains($command, 'backups:cleanup')));
    }

    public function test_shared_hosting_defaults_match_the_documented_queue_worker_settings(): void
    {
        $this->assertSame('database', config('operations.shared_hosting.queue_worker.connection'));
        $this->assertSame(55, config('operations.shared_hosting.queue_worker.max_time'));
        $this->assertSame(3, config('operations.shared_hosting.queue_worker.sleep'));
        $this->assertSame(1, config('operations.shared_hosting.queue_worker.tries'));
        $this->assertSame('02:00', config('operations.backups.schedule.run_at'));
        $this->assertSame('02:30', config('operations.backups.schedule.cleanup_at'));
    }
}
