<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiPDSService
{
    /**
     * Stage 1 Free Table OCR-to-Text Engine (Zero Gemini API Quota Used).
     * Converts uploaded page images/PDFs into tab-separated table text and 2D word coordinates.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array{converted_text: string, items_2d: array<int, array<string, mixed>>}
     */
    public function convertImagesToTextViaTableOcr(array $files): array
    {
        $ocrTexts = [];
        $ocrWords2D = [];

        foreach ($files as $pageIdx => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $realPath = $file->getRealPath();
            if ($realPath === false || ! is_readable($realPath)) {
                continue;
            }
            $rawBytes = file_get_contents($realPath);
            if ($rawBytes === false || $rawBytes === '') {
                continue;
            }
            $mime = $this->resolveMimeType($file);
            $base64Img = "data:{$mime};base64," . base64_encode($rawBytes);
            unset($rawBytes);

            try {
                $res = Http::timeout(25)
                    ->connectTimeout(8)
                    ->asForm()
                    ->post('https://api.ocr.space/parse/image', [
                        'apikey' => 'helloworld',
                        'base64Image' => $base64Img,
                        'isTable' => 'true',
                        'isOverlayRequired' => 'true',
                        'OCREngine' => '2',
                        'scale' => 'true',
                    ]);
            } catch (Throwable) {
                continue;
            }

            if (! $res->successful()) {
                continue;
            }

            $parsedResults = $res->json('ParsedResults', []);
            if (! is_array($parsedResults)) {
                continue;
            }

            foreach ($parsedResults as $pIdx => $pr) {
                $txt = trim((string) ($pr['ParsedText'] ?? ''));
                $txt = trim((string) preg_replace('/\[Note:.*?\]\s*/is', '', $txt));
                if ($txt !== '') {
                    $ocrTexts[] = '=== OCR PAGE ' . ($pageIdx + $pIdx + 1) . " ===\n" . $txt;
                }
                $lines = $pr['TextOverlay']['Lines'] ?? [];
                if (is_array($lines)) {
                    $maxX = 1000;
                    $maxY = 1000;
                    foreach ($lines as $ln) {
                        foreach (($ln['Words'] ?? []) as $wd) {
                            $rEdge = (int) (($wd['Left'] ?? 0) + ($wd['Width'] ?? 0));
                            $bEdge = (int) (($wd['Top'] ?? 0) + ($wd['Height'] ?? 0));
                            if ($rEdge > $maxX) {
                                $maxX = $rEdge;
                            }
                            if ($bEdge > $maxY) {
                                $bEdge = $bEdge;
                            }
                        }
                    }
                    foreach ($lines as $ln) {
                        foreach (($ln['Words'] ?? []) as $wd) {
                            $wText = trim((string) ($wd['WordText'] ?? ''));
                            if ($wText === '') {
                                continue;
                            }
                            $left = (float) ($wd['Left'] ?? 0);
                            $top = (float) ($wd['Top'] ?? 0);
                            $width = (float) ($wd['Width'] ?? (strlen($wText) * 8));
                            $ocrWords2D[] = [
                                'str' => $wText,
                                'x' => $left,
                                'y' => ($maxY + 50) - $top,
                                'width' => max(4.0, $width),
                                'pageWidth' => $maxX + 40,
                                'pageNum' => 200 + $pageIdx + $pIdx,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'converted_text' => implode("\n", $ocrTexts),
            'items_2d' => $ocrWords2D,
        ];
    }

    /**
     * Multi-page & multi-modal extraction: combines any client-extracted PDF/OCR text
     * with one or more uploaded page images or PDF files in a single low-latency Gemini request.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array{
     *     work_experience: array<int, array<string, mixed>>,
     *     trainings: array<int, array<string, mixed>>
     * }
     */
    public function extractFromPayload(string $extractedText = '', array $files = []): array
    {
        $parts = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $realPath = $file->getRealPath();
            if ($realPath === false || ! is_readable($realPath)) {
                continue;
            }
            $rawBytes = file_get_contents($realPath);
            if ($rawBytes === false || $rawBytes === '') {
                continue;
            }
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $this->resolveMimeType($file),
                    'data' => base64_encode($rawBytes),
                ],
            ];
            unset($rawBytes);
        }

        $cleanText = trim($extractedText);
        if ($cleanText !== '') {
            $parts[] = [
                'text' => "Supplementary text extracted from the PDS pages:\n"
                    . "--- BEGIN EXTRACTED PDS TEXT ---\n"
                    . mb_substr($cleanText, 0, 45000)
                    . "\n--- END EXTRACTED PDS TEXT ---",
            ];
        }

        if ($parts === []) {
            throw new RuntimeException('No valid PDS page image, PDF, or text was received for scanning.');
        }

        $parts[] = [
            'text' => 'Inspect ALL provided pages and text carefully. Note that Work Experience (Section V / Item 28) and Learning & Development / Trainings (Section VII / Item 30) are often on DIFFERENT pages (e.g. Page 2 and Page 3). Extract EVERY row from BOTH Section V (Work Experience) and Section VII (Learning and Development / Training Programs) across all pages.',
        ];

        return $this->callGeminiStructured($parts);
    }

    /**
     * Backwards-compatible helper for a single UploadedFile.
     *
     * @return array{
     *     work_experience: array<int, array<string, mixed>>,
     *     trainings: array<int, array<string, mixed>>
     * }
     */
    public function extractFromUploadedFile(UploadedFile $file): array
    {
        return $this->extractFromPayload('', [$file]);
    }

    /**
     * Backwards-compatible helper for text-only extraction.
     *
     * @return array{
     *     work_experience: array<int, array<string, mixed>>,
     *     trainings: array<int, array<string, mixed>>
     * }
     */
    public function extractFromText(string $extractedText): array
    {
        return $this->extractFromPayload($extractedText, []);
    }

    /**
     * Execute a low-latency Gemini Structured Output request with thinkingBudget = 0.
     *
     * @param  array<int, array<string, mixed>>  $userParts
     * @return array{
     *     work_experience: array<int, array<string, mixed>>,
     *     trainings: array<int, array<string, mixed>>
     * }
     */
    private function callGeminiStructured(array $userParts): array
    {
        $apiKey = trim((string) config('services.gemini.api_key', ''));
        $primaryModel = trim((string) config('services.gemini.model', 'gemini-flash-lite-latest'));
        $timeout = (int) config('services.gemini.timeout', 55);

        if ($apiKey === '' || $apiKey === 'your_gemini_api_key_here') {
            throw new RuntimeException(
                'GEMINI_API_KEY is not configured. Use Local OCR / PDF mode or configure a valid Gemini API key.'
            );
        }

        $todayFormatted = Carbon::now()->format('d/m/Y');

        // Automatic Multi-Model Rotation on HTTP 429 (Quota/Rate Limit) or 503
        $candidateModels = array_values(array_unique(array_filter([
            $primaryModel,
            'gemini-flash-lite-latest',
            'gemini-3-flash-preview',
            'gemini-3.1-flash-lite-preview',
            'gemini-2.5-flash',
        ])));

        $response = null;
        $lastStatus = 0;
        $lastApiMessage = '';

        foreach ($candidateModels as $model) {
            $endpoint = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                rawurlencode($model)
            );

            $generationConfig = [
                'temperature' => 0.0,
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->getStructuredOutputSchema(),
            ];

            if (str_contains($model, '2.5')) {
                $generationConfig['thinkingConfig'] = [
                    'thinkingBudget' => 0,
                ];
            }

            $payload = [
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => $this->buildSystemPrompt($todayFormatted),
                        ],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => $userParts,
                    ],
                ],
                'generationConfig' => $generationConfig,
            ];

            try {
                $res = Http::timeout($timeout)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpoint, $payload);
            } catch (ConnectionException) {
                Log::warning('Gemini API request timed out during PDS scan.', ['model' => $model]);
                continue;
            } catch (Throwable) {
                Log::error('Gemini API network error during PDS scan.', ['model' => $model]);
                continue;
            } finally {
                unset($payload);
            }

            if ($res->successful()) {
                $response = $res;
                break;
            }

            $lastStatus = $res->status();
            $lastApiMessage = (string) data_get($res->json(), 'error.message', 'Unexpected API status ' . $lastStatus);

            // If rate-limited (429), overloaded (503), or model not found (404), rotate to next model immediately
            if (in_array($lastStatus, [429, 503, 404], true)) {
                continue;
            }

            break;
        }

        unset($userParts);

        if ($response === null || ! $response->successful()) {
            if ($lastStatus === 429) {
                throw new RuntimeException('All Gemini model quotas reached (HTTP 429). Auto-switching to Local OCR / PDF parser.');
            }
            throw new RuntimeException('Gemini API Error (' . ($lastStatus ?: 'Network') . '): ' . ($lastApiMessage ?: 'Service unavailable'));
        }

        $rawJsonText = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        if (trim($rawJsonText) === '') {
            throw new RuntimeException(
                'Gemini returned an empty result. Try "Local OCR / PDF Only" mode or add rows manually.'
            );
        }

        $decoded = json_decode($rawJsonText, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Failed to parse structured JSON output from Gemini API.');
        }

        return [
            'applicant_name' => trim((string) ($decoded['applicant_name'] ?? '')),
            'date_of_birth' => $this->normalizeIsoDate((string) ($decoded['date_of_birth'] ?? '')),
            'date_accomplished' => $this->normalizeIsoDate((string) ($decoded['date_accomplished'] ?? '')),
            'work_experience' => $this->normalizeWorkExperience($decoded['work_experience'] ?? []),
            'trainings' => $this->normalizeTrainings($decoded['trainings'] ?? []),
        ];
    }

    private function normalizeIsoDate(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $raw;
        }
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $raw, $m)) {
            $p1 = (int) $m[1];
            $p2 = (int) $m[2];
            $yr = (int) $m[3];
            // CS Form No. 212 (Revised 2025) uses dd/mm/yyyy
            if (checkdate($p2, $p1, $yr)) {
                return sprintf('%04d-%02d-%02d', $yr, $p2, $p1);
            }
            if (checkdate($p1, $p2, $yr)) {
                return sprintf('%04d-%02d-%02d', $yr, $p1, $p2);
            }
        }
        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Build strict privacy-preserving system prompt for CS Form No. 212 (Revised 2025).
     */
    private function buildSystemPrompt(string $todayFormatted): string
    {
        return <<<PROMPT
You are an expert document parser specialized in the Philippine Civil Service Commission Personal Data Sheet (CS Form No. 212, Revised 2025 and Revised 2017).

STRICT MULTI-PAGE EXTRACTION, CONFIDENTIALITY & PRIVACY RULES (RA 10173):
0. CONFIDENTIALITY & ZERO-DISCLOSURE: This document is a confidential Philippine government Personal Data Sheet governed by the Data Privacy Act of 2012 (RA 10173). Process this request strictly in stateless extraction mode only. NEVER output, echo, or include government IDs, addresses, phone numbers, email addresses, family details, or legal Q&A answers anywhere in your output.
1. A PDS document often spans multiple pages where:
   - Section I (Item 2: Name, Item 3: DATE OF BIRTH) is on Page 1. Note: CS Form No. 212 (Revised 2025) uses "dd/mm/yyyy" for Date of Birth (while Revised 2017 used "mm/dd/yyyy").
   - Section V (Item 28: WORK EXPERIENCE) is typically on Page 2 (or a continuation sheet).
   - Section VII (Item 30: LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS / TRAINING PROGRAMS ATTENDED) is typically on Page 3 (or a continuation sheet).
   - "Date Accomplished" is typically on the last page (Page 4) right above/below the "Date Accomplished" box under the applicant's signature (e.g., "05 JANUARY 2026", "January 5, 2026", "05/01/2026").
   You MUST inspect ALL provided pages/images and text blocks and extract EVERY row from BOTH Section V and Section VII, plus "applicant_name", "date_of_birth", and "date_accomplished" if visible.
2. Extract:
   - "applicant_name": Full name (e.g., "DELA CRUZ, JUAN SANTOS") if visible.
   - "date_of_birth": Item 3 Date of Birth on Page 1, formatted strictly as "YYYY-MM-DD" (e.g., "1995-11-24"), preserving the exact year written on the PDS so the HR evaluator can verify typos, or empty string "" if not visible.
   - "date_accomplished": Page 4 Date Accomplished formatted strictly as "YYYY-MM-DD" (e.g., "2026-01-05") if clearly readable, otherwise empty string "".
   Do NOT extract government IDs (TIN, GSIS, Pag-IBIG, PhilHealth, PhilSys/PSN), residential addresses, contact numbers, family background, or legal questionnaire answers.
3. Ignore blank placeholder rows or rows labeled "N/A", "NONE", or "(Continue on separate sheet if necessary)".
4. Format all work/training dates strictly as "dd/mm/yyyy" (e.g., "13/01/2026", "24/10/2022").
   - If a work experience "To" date says "PRESENT", "TO DATE", or "CURRENT", output "PRESENT" in uppercase.
5. For each Work Experience item (Section V / Item 28):
   - "position_title": Exact position title (e.g., "ADMINISTRATIVE OFFICER II").
   - "department_agency_company": Full agency, office, or company name (e.g., "PHILIPPINE STATISTICS AUTHORITY").
   - "date_from": Start date in "dd/mm/yyyy".
   - "date_to": End date in "dd/mm/yyyy" or "PRESENT".
   - "status_of_appointment": Status of appointment (e.g., "PERMANENT", "COSW", "INTERNSHIP", "PROJECT-BASED", "CONTRACTUAL", "CASUAL", "JOB ORDER").
   - "gov_service": "Y" if Government Service is Yes/Y, otherwise "N".
   - "computed_years": Decimal years between date_from and date_to (using date_accomplished if found, or {$todayFormatted} when date_to is "PRESENT"), rounded to 1 decimal place.
6. For each Learning & Development / Training item (Section VII / Item 30):
   - "training_title": Full title of learning and development intervention or training program (e.g., "PUBLIC SERVICE EXCELLENCE FORUM", "RA 9184 GOVERNMENT PROCUREMENT REFORM ACT").
   - "date_from": Inclusive start date in "dd/mm/yyyy".
   - "date_to": Inclusive end date in "dd/mm/yyyy".
   - "hours": Number of hours as a numeric value (e.g., 16, 24, 8, 32). Strip trailing ".0" when whole number.
   - "type_of_ld": Type of L&D (e.g., "TECHNICAL", "Managerial", "Supervisory", "Foundation").
   - "conducted_by": Conducted or sponsored by organization/agency name (e.g., "CSC", "PSA", "PSRTI").
PROMPT;
    }

    /**
     * Strict Gemini JSON Schema for response_mime_type: "application/json".
     *
     * @return array<string, mixed>
     */
    private function getStructuredOutputSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'applicant_name' => [
                    'type' => 'STRING',
                    'description' => 'Full name of the PDS owner/applicant if visible in the document, otherwise empty string',
                ],
                'date_of_birth' => [
                    'type' => 'STRING',
                    'description' => 'Item 3 Date of Birth on Page 1 formatted as YYYY-MM-DD (e.g. 1995-11-24; CS Form 212 Revised 2025 uses dd/mm/yyyy), or empty string if not visible',
                ],
                'date_accomplished' => [
                    'type' => 'STRING',
                    'description' => 'PDS Date Accomplished on Page 4 formatted as YYYY-MM-DD (e.g. 2026-01-05), or empty string if not visible or unreadable',
                ],
                'work_experience' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'position_title' => ['type' => 'STRING'],
                            'department_agency_company' => ['type' => 'STRING'],
                            'date_from' => ['type' => 'STRING'],
                            'date_to' => ['type' => 'STRING'],
                            'status_of_appointment' => ['type' => 'STRING'],
                            'gov_service' => ['type' => 'STRING', 'enum' => ['Y', 'N']],
                            'computed_years' => ['type' => 'NUMBER'],
                        ],
                        'required' => [
                            'position_title',
                            'department_agency_company',
                            'date_from',
                            'date_to',
                            'status_of_appointment',
                            'gov_service',
                            'computed_years',
                        ],
                    ],
                ],
                'trainings' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'training_title' => ['type' => 'STRING'],
                            'date_from' => ['type' => 'STRING'],
                            'date_to' => ['type' => 'STRING'],
                            'hours' => ['type' => 'NUMBER'],
                            'type_of_ld' => ['type' => 'STRING'],
                            'conducted_by' => ['type' => 'STRING'],
                        ],
                        'required' => [
                            'training_title',
                            'date_from',
                            'date_to',
                            'hours',
                            'type_of_ld',
                            'conducted_by',
                        ],
                    ],
                ],
            ],
            'required' => ['applicant_name', 'work_experience', 'trainings'],
        ];
    }

    /**
     * Normalize and compute accurate decimal years for Work Experience rows using Carbon.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWorkExperience(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $positionTitle = trim((string) ($row['position_title'] ?? ''));
            $company = trim((string) ($row['department_agency_company'] ?? ''));

            if ($positionTitle === '' && $company === '') {
                continue;
            }

            if (in_array(strtoupper($positionTitle), ['N/A', 'NONE', 'NIL'], true)) {
                continue;
            }

            $dateFromRaw = trim((string) ($row['date_from'] ?? ''));
            $dateToRaw = trim((string) ($row['date_to'] ?? ''));

            $dateFrom = $this->normalizeDateString($dateFromRaw, false);
            $dateTo = $this->normalizeDateString($dateToRaw, true);

            $fallbackYears = isset($row['computed_years']) && is_numeric($row['computed_years'])
                ? round(max(0.0, (float) $row['computed_years']), 1)
                : 0.0;

            $computedYears = $this->calculateDecimalYears($dateFrom, $dateTo, $fallbackYears);

            $govService = strtoupper(trim((string) ($row['gov_service'] ?? 'N')));
            $govService = str_starts_with($govService, 'Y') ? 'Y' : 'N';

            $normalized[] = [
                'position_title' => $positionTitle,
                'department_agency_company' => $company,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status_of_appointment' => trim((string) ($row['status_of_appointment'] ?? '')),
                'gov_service' => $govService,
                'computed_years' => $computedYears,
                'included' => true,
            ];
        }

        return $normalized;
    }

    /**
     * Normalize Training / L&D rows.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTrainings(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['training_title'] ?? ''));
            if ($title === '' || in_array(strtoupper($title), ['N/A', 'NONE', 'NIL'], true)) {
                continue;
            }

            $dateFrom = $this->normalizeDateString(trim((string) ($row['date_from'] ?? '')), false);
            $dateTo = $this->normalizeDateString(trim((string) ($row['date_to'] ?? '')), false);

            $hours = isset($row['hours']) && is_numeric($row['hours'])
                ? round(max(0.0, (float) $row['hours']), 1)
                : 0;

            if (floor((float) $hours) == (float) $hours) {
                $hours = (int) $hours;
            }

            $normalized[] = [
                'training_title' => $title,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'hours' => $hours,
                'type_of_ld' => trim((string) ($row['type_of_ld'] ?? 'Technical')),
                'conducted_by' => trim((string) ($row['conducted_by'] ?? '')),
                'included' => true,
            ];
        }

        return $normalized;
    }

    public function normalizeDateString(string $value, bool $allowPresent = true): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $upper = strtoupper($clean);
        if ($allowPresent && in_array($upper, ['PRESENT', 'TO DATE', 'CURRENT', 'ONGOING', 'NOW'], true)) {
            return 'PRESENT';
        }

        $parsed = $this->parseCarbonDate($clean);

        return $parsed instanceof Carbon ? $parsed->format('d/m/Y') : $clean;
    }

    public function calculateDecimalYears(string $dateFrom, string $dateTo, float $fallback = 0.0): float
    {
        $start = $this->parseCarbonDate($dateFrom);
        if (! $start instanceof Carbon) {
            return round(max(0.0, $fallback), 1);
        }

        $end = strtoupper(trim($dateTo)) === 'PRESENT'
            ? Carbon::now()->startOfDay()
            : $this->parseCarbonDate($dateTo);

        if (! $end instanceof Carbon) {
            return round(max(0.0, $fallback), 1);
        }

        if ($end->lessThan($start)) {
            return 0.0;
        }

        $inclusiveDays = $start->diffInDays($end) + 1;

        return round($inclusiveDays / 365.25, 1);
    }

    private function parseCarbonDate(string $dateString): ?Carbon
    {
        $clean = trim(str_replace(['.', '-'], '/', $dateString));
        if ($clean === '') {
            return null;
        }

        if (preg_match('/^(\d{1,4})\/(\d{1,2})\/(\d{1,4})$/', $clean, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $c = (int) $m[3];

            if (strlen($m[1]) === 4) {
                $year = $a;
                $month = $b;
                $day = $c;
            } else {
                $year = $c < 100 ? ($c > 50 ? 1900 + $c : 2000 + $c) : $c;
                if ($a > 12 && $b <= 12) {
                    $day = $a;
                    $month = $b;
                } elseif ($b > 12 && $a <= 12) {
                    $month = $a;
                    $day = $b;
                } else {
                    $day = $a;
                    $month = $b;
                }
            }

            if (checkdate($month, $day, $year)) {
                return Carbon::createMidnightDate($year, $month, $day);
            }
        }

        return null;
    }

    private function resolveMimeType(UploadedFile $file): string
    {
        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        $ext = strtolower($file->getClientOriginalExtension());

        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'application/pdf';
        }

        if (in_array($mime, ['image/png', 'image/webp', 'image/jpeg'], true)) {
            return $mime;
        }

        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
