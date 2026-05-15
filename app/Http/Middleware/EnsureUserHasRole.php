<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\ApiResponse;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole($role)) {
            $message = "Role [{$role}] is required.";

            if (ApiResponse::isApiRequest($request)) {
                return ApiResponse::error($message, Response::HTTP_FORBIDDEN);
            }

            abort(Response::HTTP_FORBIDDEN, $message);
        }

        return $next($request);
    }
}
