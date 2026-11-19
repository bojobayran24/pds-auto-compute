<?php

declare(strict_types=1);

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (! is_file($autoloadPath)) {
    require __DIR__ . '/../bootstrap/standalone.php';
    return;
}

// Register the Composer autoloader...
require $autoloadPath;

// Bootstrap Laravel and handle the request...
(require_once __DIR__ . '/../bootstrap/app.php')
    ->handleRequest(Request::capture());
