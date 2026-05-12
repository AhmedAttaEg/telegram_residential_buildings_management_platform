<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformOwner
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isPlatformOwner()) {
            return ApiResponse::error('Platform owner access is required.', Response::HTTP_FORBIDDEN);
        }

        if ($user->tenant_id !== null) {
            return ApiResponse::error('Tenant-bound users cannot access owner administration.', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
