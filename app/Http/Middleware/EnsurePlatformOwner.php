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
            return $this->forbidden($request, 'Platform owner access is required.');
        }

        if ($user->tenant_id !== null) {
            return $this->forbidden($request, 'Tenant-bound users cannot access owner administration.');
        }

        return $next($request);
    }

    private function forbidden(Request $request, string $message): Response
    {
        if (ApiResponse::isApiRequest($request)) {
            return ApiResponse::error($message, Response::HTTP_FORBIDDEN);
        }

        abort(Response::HTTP_FORBIDDEN, $message);
    }
}
