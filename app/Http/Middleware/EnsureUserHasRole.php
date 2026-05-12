<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole($role)) {
            return new JsonResponse([
                'message' => "Role [{$role}] is required.",
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
