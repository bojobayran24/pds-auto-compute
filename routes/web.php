<?php

declare(strict_types=1);

use App\Http\Controllers\PDSController;
use App\Http\Middleware\VerifySharedPasscode;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Single-Screen PDS Auto-Compute Routes (Zero DB • Cookie Session Only)
|--------------------------------------------------------------------------
*/

// Passcode Gate Unlock & Lock Endpoints (Rate-limited: 10 attempts per minute)
Route::post('/passcode/unlock', [PDSController::class, 'unlock'])
    ->middleware('throttle:10,1')
    ->name('pds.unlock');

Route::post('/passcode/lock', [PDSController::class, 'lock'])
    ->name('pds.lock');

// Passcode-Protected Single-Screen Workspace & Gemini API Scanner
Route::middleware([VerifySharedPasscode::class])->group(function (): void {
    Route::get('/', [PDSController::class, 'index'])
        ->name('pds.index');

    Route::post('/api/scan-pds', [PDSController::class, 'scan'])
        ->middleware('throttle:20,1')
        ->name('pds.scan');
});
