<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

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

        // Return JSON responses for API requests.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson()
        );

        // Rate limit exceeded.
        $exceptions->render(function (
            ThrottleRequestsException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {

                $response = ApiResponse::error(
                    'Too many requests. Please try again later.',
                    429
                );

                foreach ($e->getHeaders() as $key => $value) {
                    $response->headers->set($key, $value);
                }

                return $response;
            }
        });

        // Invalid request data.
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'The submitted data is invalid.',
                    422,
                    $e->errors()
                );
            }
        });

        // Authentication required.
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'Please authenticate to continue.',
                    401
                );
            }
        });

        // Access denied.
        $exceptions->render(function (
            AccessDeniedHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'You are not allowed to perform this action.',
                    403
                );
            }
        });

        // Requested resource or endpoint not found.
        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'The requested resource was not found.',
                    404
                );
            }
        });

        // Unsupported HTTP method.
        $exceptions->render(function (
            MethodNotAllowedHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'This HTTP method is not allowed for this endpoint.',
                    405
                );
            }
        });

        // Unexpected server errors.
        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) {
            if (
                $request->is('api/*') &&
                !config('app.debug') &&
                !($e instanceof HttpResponseException)
            ) {
                return ApiResponse::error(
                    'Something went wrong. Please try again later.',
                    500
                );
            }
        });

    })->create();
