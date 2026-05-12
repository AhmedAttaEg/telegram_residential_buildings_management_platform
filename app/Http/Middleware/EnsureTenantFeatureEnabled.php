<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeatureEnabled
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = $this->tenantContext->get();

        if ($tenant === null) {
            return ApiResponse::error('Tenant context is missing.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (! $tenant->hasFeature($feature)) {
            return ApiResponse::error("Tenant feature [{$feature}] is disabled.", Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
