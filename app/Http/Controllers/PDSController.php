<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GeminiPDSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PDSController
{
    /**
     * Render the single-screen PDS Auto-Compute workspace.
     */
    public function index(): View
    {
        $configuredPasscode = trim((string) config('services.pds.access_passcode', config('app.access_passcode', '')));

        return view('pds', [
            'isLocked' => false,
            'passcodeEnabled' => $configuredPasscode !== '',
        ]);
    }

    /**
     * Verify shared passcode and unlock the encrypted cookie session.
     * Protected by throttle:10,1 rate-limiting middleware.
     */
    public function unlock(Request $request): JsonResponse|RedirectResponse
    {
        $configuredPasscode = trim((string) config('services.pds.access_passcode', config('app.access_passcode', '')));

        if ($configuredPasscode === '') {
            $request->session()->put('pds_unlocked', true);

            return $request->expectsJson()
                ? response()->json(['ok' => true])
                : redirect()->route('pds.index');
        }

        $inputPasscode = (string) $request->input('passcode', '');

        if ($inputPasscode === '' || ! hash_equals($configuredPasscode, $inputPasscode)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Invalid shared passcode. Please try again.',
                ], 422);
            }

            return back()->withErrors(['passcode' => 'Invalid shared passcode. Please try again.']);
        }

        $request->session()->put('pds_unlocked', true);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('pds.index');
    }

    /**
     * Lock the current browser's cookie session immediately.
     */
    public function lock(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget('pds_unlocked');
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('pds.index');
    }

    /**
     * Scan an uploaded CS Form No. 212 (Revised 2025) PDF, multiple rendered page images,
     * and/or client-extracted OCR/PDF text in memory via Gemini API.
     */
    public function scan(Request $request, GeminiPDSService $geminiService): JsonResponse
    {
        $extractedText = trim((string) $request->input('extracted_text', ''));
        $files = [];

        if ($request->hasFile('pds_file')) {
            $single = $request->file('pds_file');
            if ($single instanceof UploadedFile) {
                $files[] = $single;
            }
        }

        if ($request->hasFile('page_images')) {
            $multi = $request->file('page_images');
            if (is_array($multi)) {
                foreach ($multi as $img) {
                    if ($img instanceof UploadedFile) {
                        $files[] = $img;
                    }
                }
            } elseif ($multi instanceof UploadedFile) {
                $files[] = $multi;
            }
        }

        if ($extractedText === '' && $files === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Please select a PDS PDF or image file to scan.',
            ], 422);
        }

        if (trim((string) $request->input('scan_mode', '')) === 'ocr_text_only') {
            $ocrResult = $geminiService->convertImagesToTextViaTableOcr($files);

            return response()->json([
                'ok' => $ocrResult['converted_text'] !== '',
                'converted_text' => $ocrResult['converted_text'],
                'items_2d' => $ocrResult['items_2d'],
            ]);
        }

        try {
            $extracted = $geminiService->extractFromPayload($extractedText, $files);

            $workCount = count($extracted['work_experience']);
            $trainingCount = count($extracted['trainings']);

            return response()->json([
                'ok' => true,
                'message' => ($workCount === 0 && $trainingCount === 0)
                    ? 'Scan finished, but no Work Experience (Section V) or Training (Section VII) rows were detected.'
                    : sprintf('Extracted %d training(s) and %d work experience item(s).', $trainingCount, $workCount),
                'data' => $extracted,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'message' => 'An unexpected error occurred while scanning the document. You can add rows manually below.',
            ], 500);
        }
    }
}
