<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvironmentConfigurationTest extends TestCase
{
    public function test_documented_environment_variables_are_present_in_the_local_environment_file(): void
    {
        $this->artisan('app:env-check')
            ->expectsOutput('Environment variables are complete.')
            ->assertExitCode(0);
    }

    public function test_tenant_bootstrap_defaults_are_available(): void
    {
        $this->assertSame('ar', config('tenant.defaults.locale'));
        $this->assertSame('en', config('tenant.defaults.fallback_locale'));
        $this->assertSame('Africa/Cairo', config('tenant.defaults.timezone'));
        $this->assertSame('EGP', config('tenant.defaults.currency'));
        $this->assertTrue(config('tenant.features.maintenance'));
        $this->assertTrue(config('tenant.features.resident_app'));
        $this->assertFalse(config('tenant.features.enterprise_accounting'));
    }
}
