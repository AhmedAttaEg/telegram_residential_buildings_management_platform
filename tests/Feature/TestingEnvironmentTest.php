<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NotificationInfrastructureSmokeTestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TestingEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_environment_uses_testing_safe_defaults(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('array', config('session.driver'));

        $expectedConnection = env('TEST_DATABASE_FALLBACK') === 'sqlite'
            ? 'sqlite'
            : 'mysql';

        $this->assertSame($expectedConnection, config('database.default'));
    }

    public function test_notification_fake_helper_can_intercept_notifications(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        $this->fakeNotifications();

        Notification::send($user, new NotificationInfrastructureSmokeTestNotification('Fake notification'));

        Notification::assertSentTo(
            [$user],
            NotificationInfrastructureSmokeTestNotification::class,
        );

        $this->assertDatabaseCount('notifications', 0);
    }
}
