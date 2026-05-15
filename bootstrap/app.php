<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Support\Facades\Log;
use App\Support\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'owner' => \App\Http\Middleware\EnsurePlatformOwner::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'resident.apartment' => \App\Http\Middleware\EnsureResidentHasApartmentAccess::class,
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'tenant.feature' => \App\Http\Middleware\EnsureTenantFeatureEnabled::class,
            'tenant.admin' => \App\Http\Middleware\EnsureTenantAdminUser::class,
            'resident.portal' => \App\Http\Middleware\EnsureResidentPortalUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                'The given data was invalid.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('Resource not found.', Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('Route not found.', Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('Too many requests.', Response::HTTP_TOO_MANY_REQUESTS);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() !== '' ? $exception->getMessage() : Response::$statusTexts[$exception->getStatusCode()],
                $exception->getStatusCode(),
            );
        });

        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! ApiResponse::isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('Server error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $exceptions->report(function (\Throwable $throwable): void {
            /** @var Request $request */
            $request = request();

            if (! ApiResponse::isApiRequest($request)) {
                return;
            }

            Log::channel('api')->error('API request exception', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);
        });
    })->create();
