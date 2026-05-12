<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationConfigurationTest extends TestCase
{
    public function test_arabic_and_english_translations_are_loaded(): void
    {
        App::setLocale('ar');
        $this->assertSame('تقرير السلامة', __('messages.health_report'));
        $this->assertSame('منصة إدارة المباني السكنية', __('messages.platform_name'));

        App::setLocale('en');
        $this->assertSame('Health report', __('messages.health_report'));
        $this->assertSame('Residential Buildings Management Platform', __('messages.platform_name'));
    }

    public function test_application_locale_defaults_are_arabic_with_english_fallback(): void
    {
        $this->assertSame('ar', config('app.locale'));
        $this->assertSame('en', config('app.fallback_locale'));
    }
}
