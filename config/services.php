<?php

declare(strict_types=1);

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 55),
    ],

    'pds' => [
        'access_passcode' => env('APP_ACCESS_PASSCODE', ''),
    ],
];
