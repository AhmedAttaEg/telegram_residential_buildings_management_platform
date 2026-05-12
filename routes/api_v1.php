<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MobileAuthController;
use App\Http\Controllers\Api\V1\Owner\TenantController;
use App\Http\Controllers\Api\V1\Resident\ResidentPortalController;
use App\Models\Tenant;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return ApiResponse::success([
        'status' => 'ok',
        'version' => config('api.version'),
    ]);
});

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-auth');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('mobile/auth')->group(function (): void {
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:api-auth');

    Route::middleware(['throttle:api', 'auth:sanctum'])->group(function (): void {
        Route::post('/refresh', [MobileAuthController::class, 'refresh']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::post('/logout-all', [MobileAuthController::class, 'logoutAll']);
    });
});

Route::middleware(['throttle:api', 'auth:sanctum'])->group(function (): void {
    Route::prefix('owner')
        ->middleware('owner')
        ->group(function (): void {
            Route::get('/dashboard', function (Request $request) {
                return ApiResponse::success([
                    'role' => 'platform_owner',
                    'user' => $request->user()?->email,
                ]);
            });

            Route::apiResource('tenants', TenantController::class);
            Route::patch('tenants/{tenant:slug}/status', [TenantController::class, 'updateStatus']);
        });

    Route::get('/accounting/dashboard', function (Request $request) {
        return ApiResponse::success([
            'permission' => 'accounting.access',
            'user' => $request->user()?->email,
        ]);
    })->middleware('permission:accounting.access');
});

Route::prefix('t/{tenant:slug}')
    ->middleware(['throttle:api', 'auth:sanctum', 'tenant'])
    ->group(function (): void {
        Route::get('/health', function (Request $request, TenantContext $tenantContext) {
            /** @var Tenant $requestTenant */
            $requestTenant = $request->attributes->get('tenant');

            return ApiResponse::success([
                'status' => 'ok',
                'tenant' => $requestTenant->slug,
                'context_tenant' => $tenantContext->get()?->slug,
                'request_tenant' => $requestTenant->slug,
            ]);
        });

        Route::get('/maintenance', function (TenantContext $tenantContext) {
            return ApiResponse::success([
                'feature' => 'maintenance',
                'tenant' => $tenantContext->get()?->slug,
            ]);
        })->middleware(['tenant.feature:maintenance', 'permission:maintenance.access']);

        Route::get('/ai', function (TenantContext $tenantContext) {
            return ApiResponse::success([
                'feature' => 'ai_features',
                'tenant' => $tenantContext->get()?->slug,
            ]);
        })->middleware('tenant.feature:ai_features');

        Route::prefix('resident/apartments/{apartment}')
            ->middleware(['tenant.feature:resident_app', 'permission:resident.access', 'resident.apartment'])
            ->group(function (): void {
                Route::get('/wallet/summary', [ResidentPortalController::class, 'walletSummary']);
                Route::get('/wallet/history', [ResidentPortalController::class, 'walletHistory']);
                Route::get('/debit/summary', [ResidentPortalController::class, 'debitSummary']);
                Route::get('/debit/unpaid-splits', [ResidentPortalController::class, 'unpaidSplits']);
            });
    });
