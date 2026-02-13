<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckTokenExpiry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => CheckRole::class,
            'token-expiry' => CheckTokenExpiry::class
        ]);
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        });
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);
        });
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token tidak valid/telah kedaluwarsa.'
            ], 401);
        });
        $exceptions->render(function (AuthorizationException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        });
        $exceptions->render(function (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        });
    })->create();

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
    $app->useStoragePath('/tmp/storage');
}

return $app;
