<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Channels\TelegramLogChannel;
use App\Notifications\NotificationInfrastructureSmokeTestNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        foreach (glob(storage_path('logs/telegram*.log')) ?: [] as $logFile) {
            File::delete($logFile);
        }
    }

    public function test_notifications_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasColumns('notifications', [
            'id',
            'type',
            'notifiable_type',
            'notifiable_id',
            'data',
            'read_at',
        ]));
    }

    public function test_notification_smoke_test_command_dispatches_queued_notification(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => null,
            'queue.failed.database' => config('database.default'),
        ]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        $this->artisan('app:notification-smoke-test', ['user' => $user->id])
            ->expectsOutput('Notification smoke test dispatched.')
            ->assertExitCode(0);

        $payloads = \DB::table('jobs')->pluck('payload');

        $this->assertGreaterThanOrEqual(1, $payloads->count());
    }

    public function test_send_now_persists_database_notification_and_logs_telegram_channel(): void
    {
        config([
            'services.telegram.notifications.default_chat_id' => 'test-chat',
            'mail.default' => 'array',
        ]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        Notification::sendNow(
            [$user],
            new NotificationInfrastructureSmokeTestNotification('Immediate smoke test'),
            ['database', TelegramLogChannel::class],
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => NotificationInfrastructureSmokeTestNotification::class,
        ]);

        $contents = $this->readTelegramLog();

        $this->assertStringContainsString('Telegram notification dispatched', $contents);
        $this->assertStringContainsString('notification_smoke_test', $contents);
        $this->assertStringContainsString('test-chat', $contents);
    }

    private function readTelegramLog(): string
    {
        $files = glob(storage_path('logs/telegram*.log')) ?: [];

        $this->assertNotEmpty($files, 'No Telegram log file was created.');

        return (string) file_get_contents($files[0]);
    }
}
