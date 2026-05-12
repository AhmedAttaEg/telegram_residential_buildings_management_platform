<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('api.throttle.api.per_minute', 60))->by((string) $key);
        });

        RateLimiter::for('api-auth', function (Request $request): Limit {
            $email = (string) $request->input('email', 'guest');

            return Limit::perMinute((int) config('api.throttle.auth.per_minute', 5))
                ->by($email.'|'.$request->ip());
        });
    }
}
