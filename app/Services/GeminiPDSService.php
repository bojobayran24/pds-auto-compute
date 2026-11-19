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
        $model = trim((string) config('services.gemini.model', 'gemini-2.5-flash'));
        $timeout = (int) config('services.gemini.timeout', 55);

        if ($apiKey === '' || $apiKey === 'your_gemini_api_key_here') {
            throw new RuntimeException(
                'GEMINI_API_KEY is not configured. Use Local OCR / PDF mode or configure a valid Gemini API key.'
            );
        }

        $todayFormatted = Carbon::now()->format('d/m/Y');

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

        unset($userParts);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);
        } catch (ConnectionException) {
            Log::warning('Gemini API request timed out during PDS scan.');
            throw new RuntimeException(
                'Gemini API timed out. Switch to "Local OCR / PDF Only" mode or upload a cropped image.'
            );
        } catch (Throwable) {
            Log::error('Gemini API network error during PDS scan.');
            throw new RuntimeException(
                'Could not reach Gemini API. Switch to "Local OCR / PDF Only" mode or add rows manually.'
            );
        } finally {
            unset($payload);
        }

        if (! $response->successful()) {
            $status = $response->status();
            $apiMessage = (string) data_get($response->json(), 'error.message', 'Unexpected API status ' . $status);
            Log::warning('Gemini API returned non-200 status.', ['status' => $status]);

            if ($status === 429) {
                throw new RuntimeException('Gemini API rate limit reached (HTTP 429). Use "Local OCR / PDF Only" mode instead.');
            }

            throw new RuntimeException('Gemini API Error (' . $status . '): ' . $apiMessage);
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
            'work_experience' => $this->normalizeWorkExperience($decoded['work_experience'] ?? []),
            'trainings' => $this->normalizeTrainings($decoded['trainings'] ?? []),
        ];
    }

    /**
     * Build strict privacy-preserving system prompt for CS Form No. 212 (Revised 2025).
     */
    private function buildSystemPrompt(string $todayFormatted): string
    {
        return <<<PROMPT
You are an expert document parser specialized in the Philippine Civil Service Commission Personal Data Sheet (CS Form No. 212, Revised 2025 and Revised 2017).

STRICT MULTI-PAGE EXTRACTION & PRIVACY RULES:
1. A PDS document often spans multiple pages where:
   - Section V (Item 28: WORK EXPERIENCE) is typically on Page 2 (or a continuation sheet).
   - Section VII (Item 30: LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS / TRAINING PROGRAMS ATTENDED) is typically on Page 3 (or a continuation sheet).
   You MUST inspect ALL provided pages/images and text blocks and extract EVERY row from BOTH Section V and Section VII.
2. NEVER extract Personal Information (Section I), Family Background (Section II), Educational Background (Section III), Civil Service Eligibility (Section IV), Voluntary Work (Section VI), Other Information (Section VIII), government IDs (TIN, GSIS, Pag-IBIG, PhilHealth, PhilSys/PSN), residential addresses, contact numbers, or legal questionnaire answers.
3. Ignore blank placeholder rows or rows labeled "N/A", "NONE", or "(Continue on separate sheet if necessary)".
4. Format all dates strictly as "dd/mm/yyyy" (e.g., "13/01/2026", "24/10/2022").
   - If a work experience "To" date says "PRESENT", "TO DATE", or "CURRENT", output "PRESENT" in uppercase.
5. For each Work Experience item (Section V / Item 28):
   - "position_title": Exact position title (e.g., "ADMINISTRATIVE OFFICER II").
   - "department_agency_company": Full agency, office, or company name (e.g., "PHILIPPINE STATISTICS AUTHORITY").
   - "date_from": Start date in "dd/mm/yyyy".
   - "date_to": End date in "dd/mm/yyyy" or "PRESENT".
   - "status_of_appointment": Status of appointment (e.g., "PERMANENT", "COSW", "INTERNSHIP", "PROJECT-BASED", "CONTRACTUAL", "CASUAL", "JOB ORDER").
   - "gov_service": "Y" if Government Service is Yes/Y, otherwise "N".
   - "computed_years": Decimal years between date_from and date_to (using {$todayFormatted} when date_to is "PRESENT"), rounded to 1 decimal place.
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
            'required' => ['work_experience', 'trainings'],
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

        $formats = [
            'd/m/Y',
            'j/n/Y',
            'm/d/Y',
            'n/j/Y',
            'Y/m/d',
            'd/m/y',
        ];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat('!' . $format, $clean);
                if ($dt instanceof Carbon && $dt->format($format) === $clean) {
                    return $dt;
                }
            } catch (Throwable) {
                // Continue trying next format
            }
        }

        try {
            return Carbon::parse($dateString)->startOfDay();
        } catch (Throwable) {
            return null;
        }
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
