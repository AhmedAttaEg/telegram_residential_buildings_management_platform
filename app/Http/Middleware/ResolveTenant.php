<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (is_string($tenant)) {
            $tenant = Tenant::query()->where('slug', $tenant)->first();
        }

        if (! $tenant instanceof Tenant) {
            return ApiResponse::error('Tenant not found.', Response::HTTP_NOT_FOUND);
        }

        if ($tenant->isSuspended()) {
            return ApiResponse::error('Tenant is suspended.', Response::HTTP_FORBIDDEN);
        }

        if (! $tenant->hasActiveSubscription()) {
            return ApiResponse::error('Tenant subscription is inactive.', Response::HTTP_FORBIDDEN);
        }

        $user = Auth::user();

        if ($user !== null && $user->tenant_id !== null && $user->tenant_id !== $tenant->id) {
            return ApiResponse::error('Cross-tenant access is not allowed.', Response::HTTP_FORBIDDEN);
        }

        $this->tenantContext->set($tenant);
        $request->attributes->set('tenant', $tenant);
        app()->instance(Tenant::class, $tenant);

        return $next($request);
    }
}
