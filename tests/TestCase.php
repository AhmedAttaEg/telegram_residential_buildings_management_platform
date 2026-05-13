<?php

namespace Tests;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function fakeQueue(): void
    {
        Queue::fake();
    }

    protected function fakeNotifications(): void
    {
        Notification::fake();
    }
}
