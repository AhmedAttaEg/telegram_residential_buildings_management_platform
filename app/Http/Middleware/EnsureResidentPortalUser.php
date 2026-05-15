<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use App\Support\WebDashboardResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResidentPortalUser
{
    public function __construct(
        private readonly WebDashboardResolver $dashboardResolver,
    ) {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->dashboardResolver->canAccessResidentPortal($user)) {
            $message = 'Resident portal access is required.';

            if (ApiResponse::isApiRequest($request)) {
                return ApiResponse::error($message, Response::HTTP_FORBIDDEN);
            }

            abort(Response::HTTP_FORBIDDEN, $message);
        }

        return $next($request);
    }
}
