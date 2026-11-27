<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->redirectGuestsTo('/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {


        $isApi = static fn (Request $request): bool => $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $isApi($request) || $request->expectsJson()
        );

        // 429 - rate limit exceeded (keeps the Retry-After headers).
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $response = ApiResponse::error(
                'Too many requests. Please try again later.',
                429,
                null,
                'too_many_requests'
            );

            foreach ($e->getHeaders() as $key => $value) {
                $response->headers->set($key, $value);
            }

            return $response;
        });

        // 422 - validation failed.
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'The given data was invalid.',
                422,
                $e->errors(),
                'validation_failed'
            );
        });

        // 401 - missing or invalid token.
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Unauthenticated.', 401, null, 'unauthenticated');
        });

        // 403 - policy / gate
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'You are not allowed to access this resource.',
                403,
                null,
                'forbidden'
            );
        });

        // 404 - unknown route, or route-model binding
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $previous = $e->getPrevious();

            $message = $previous instanceof ModelNotFoundException
                ? Str::headline(class_basename($previous->getModel())) . ' not found.'
                : 'Endpoint not found.';

            return ApiResponse::error($message, 404, null, 'not_found');
        });

        // 405 - wrong HTTP verb.
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error('Method not allowed.', 405, null, 'method_not_allowed');
        });

        // Any other HTTP error (400, 413, ...).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() !== '' ? $e->getMessage() : 'Request failed.',
                $e->getStatusCode(),
                null,
                'http_error'
            );
        });

        // 500

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request) || config('app.debug') || $e instanceof HttpResponseException) {
                return null;
            }

            return ApiResponse::error('Server error. Please try again later.', 500, null, 'server_error');
        });
    })->create();
