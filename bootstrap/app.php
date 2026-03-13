<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

    })
    ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (Throwable $e, $request) {
         // Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => collect($e->errors())->flatten()->map(function ($msg) {
            $normalized = strtolower(str_replace([' ', '.', ','], '_', $msg));
            return trim(preg_replace('/_+/', '_', $normalized), '_');
        })->values(),
                'status'  => 422,
            ], 422);
        }

        // Unauthenticated
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status' => 401
            ], 401);
        }

        // Not found (routes or models)
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found.',
                'status' => 404,
            ], 404);
        }

        // Too many requests
        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
                'status' => 429,
            ], 429);
        }

        // Other Http exceptions
        $status = $e instanceof HttpExceptionInterface
            ? $e->getStatusCode()
            : 500;

        return response()->json([
            'message' => $e->getMessage() ?: 'Internal server error.',
            'status' => $status,
        ], $status);
    });
})->create();
