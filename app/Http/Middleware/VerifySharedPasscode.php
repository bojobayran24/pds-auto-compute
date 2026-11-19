<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySharedPasscode
{
    /**
     * Protect the single-screen PDS workspace and /api/scan-pds endpoint
     * using an encrypted cookie-based session flag (SESSION_DRIVER=cookie).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredPasscode = trim((string) config('services.pds.access_passcode', config('app.access_passcode', '')));

        // If APP_ACCESS_PASSCODE is not set in .env, allow direct access
        if ($configuredPasscode === '') {
            return $next($request);
        }

        $isUnlocked = $request->session()->get('pds_unlocked') === true;

        if (! $isUnlocked) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Session locked. Please unlock the application with the shared access passcode.',
                ], 401);
            }

            return response()->view('pds', [
                'isLocked' => true,
                'passcodeEnabled' => true,
            ]);
        }

        return $next($request);
    }
}
