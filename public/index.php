<?php

declare(strict_types=1);

define('LARAVEL_START', microtime(true));

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (! is_file($autoloadPath) || ! class_exists(\Illuminate\Foundation\Application::class)) {
    require __DIR__ . '/../bootstrap/standalone.php';
    return;
}

require $autoloadPath;

if (! class_exists(\Illuminate\Foundation\Application::class)) {
    require __DIR__ . '/../bootstrap/standalone.php';
    return;
}

(require_once __DIR__ . '/../bootstrap/app.php')
    ->handleRequest(\Illuminate\Http\Request::capture());
