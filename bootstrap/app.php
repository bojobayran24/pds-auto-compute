<?php

declare(strict_types=1);

use App\Http\Middleware\VerifySharedPasscode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Vercel edge proxy headers so client IP rate-limiting and HTTPS cookies work properly
        $middleware->trustProxies(at: '*');

        // Register the shared passcode verification middleware alias
        $middleware->alias([
            'passcode' => VerifySharedPasscode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never leak raw payloads or sensitive request data in exception logs
        $exceptions->dontFlash([
            'file',
            'pds_file',
            'passcode',
        ]);
    })
    ->create();

// Automatically switch to ephemeral /tmp/storage when running on Vercel Serverless
$serverlessStorage = $_ENV['VERCEL_SERVERLESS_STORAGE']
    ?? $_SERVER['VERCEL_SERVERLESS_STORAGE']
    ?? getenv('VERCEL_SERVERLESS_STORAGE');

if (! empty($serverlessStorage) && is_string($serverlessStorage)) {
    $app->useStoragePath($serverlessStorage);
}

return $app;
