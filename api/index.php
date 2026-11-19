<?php

declare(strict_types=1);

/**
 * Vercel Serverless PHP Entrypoint
 *
 * 1. Normalizes SCRIPT_NAME / PHP_SELF so Symfony/Laravel routing treats `/` as root
 *    instead of `/api` on Vercel serverless functions.
 * 2. Prepares ephemeral `/tmp/storage` directories required by Laravel for
 *    compiled Blade views and rate-limiter cache without any permanent disk
 *    or database storage.
 */
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

$ephemeralStorage = '/tmp/storage';

$directories = [
    $ephemeralStorage . '/framework/views',
    $ephemeralStorage . '/framework/cache/data',
    $ephemeralStorage . '/framework/sessions',
    $ephemeralStorage . '/bootstrap/cache',
    $ephemeralStorage . '/logs',
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }
}

$_ENV['VERCEL_SERVERLESS_STORAGE'] = $ephemeralStorage;
$_SERVER['VERCEL_SERVERLESS_STORAGE'] = $ephemeralStorage;
putenv("VERCEL_SERVERLESS_STORAGE={$ephemeralStorage}");

require __DIR__ . '/../public/index.php';
