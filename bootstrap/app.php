<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.auth' => \App\Http\Middleware\EnsureApiAuthenticated::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) { $exceptions->render(function (NotFoundHttpException $e, $request) {
        if ($request->is('api/*')) {
            \Log::warning('API endpoint not found', [
                'path' => $request->path(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Endpoint not found'
            ], 404);
        }
    });

    // Tangani autentikasi gagal untuk API (opsional, sesuai middleware api.auth)
    $exceptions->render(function (AuthenticationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
    });

    // Tangani semua exception lain untuk API
    $exceptions->render(function (Throwable $e, $request) {
        if ($request->is('api/*')) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            $message = $e->getMessage() ?: 'Internal server error';

            \Log::error('API error occurred', [
                'path' => $request->path(),
                'method' => $request->method(),
                'exception' => get_class($e),
                'message' => $message,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $message
            ], $statusCode ?: 500);
        }
    });
})->create();