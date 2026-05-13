<?php

namespace App\Providers;

use App\Models\DebitTransaction;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\ExpenseSplit;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use App\Models\PersonalAccessToken;
use App\Models\Pivots\PermissionRole;
use App\Models\Pivots\RoleUser;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\WalletTransaction;
use App\Observers\ModelAuditObserver;
use App\Observers\PermissionRoleObserver;
use App\Observers\RoleUserObserver;
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

        foreach ([
            DebitTransaction::class,
            Expense::class,
            ExpensePayment::class,
            ExpenseSplit::class,
            JournalEntry::class,
            JournalEntryLine::class,
            LedgerAccount::class,
            Tenant::class,
            TenantSubscription::class,
            WalletTransaction::class,
        ] as $auditedModel) {
            $auditedModel::observe(ModelAuditObserver::class);
        }

        PermissionRole::observe(PermissionRoleObserver::class);
        RoleUser::observe(RoleUserObserver::class);

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
