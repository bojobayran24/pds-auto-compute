<?php

declare(strict_types=1);

/**
 * Zero-Dependency Local Fallback Runtime (Used only if `vendor/autoload.php` is not yet installed).
 *
 * When `vendor/autoload.php` is present (after `composer install` or on Vercel),
 * `public/index.php` boots the standard Laravel 11 application in `bootstrap/app.php`.
 * When running directly in XAMPP before `composer install`, this fallback executes the
 * exact same Passcode Gate ("psarsps 2027"), encrypted cookie session, Gemini 2.5 Flash
 * Structured Output extraction, and Blade view (`resources/views/pds.blade.php`).
 */

$basePath = dirname(__DIR__);

// 1. Parse .env file into environment
$envFile = $basePath . '/.env';
$env = [];
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $trimmed, 2);
        $k = trim($k);
        $v = trim($v);
        if (
            (str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))
        ) {
            $v = substr($v, 1, -1);
        }
        $env[$k] = $v;
    }
}

$getEnv = static function (string $key, string $default = '') use ($env): string {
    if (isset($env[$key])) {
        return $env[$key];
    }
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return is_string($val) ? $val : $default;
};

date_default_timezone_set($getEnv('APP_TIMEZONE', 'Asia/Manila'));

$appKey = $getEnv('APP_KEY', 'base64:qQ24gG+3FbWjse8TNQoPtkxmIanauXMzdkvzdvWIhk4=');
$rawKey = str_starts_with($appKey, 'base64:') ? (base64_decode(substr($appKey, 7)) ?: $appKey) : $appKey;
$configuredPasscode = trim($getEnv('APP_ACCESS_PASSCODE', ''));
$geminiApiKey = trim($getEnv('GEMINI_API_KEY', ''));
$geminiModel = trim($getEnv('GEMINI_MODEL', 'gemini-2.5-flash'));
$geminiTimeout = (int) $getEnv('GEMINI_TIMEOUT', '55');

// 2. Encrypted Cookie Session Helper (SESSION_DRIVER=cookie)
$cookieName = 'pds_auto_compute_session';

$encryptCookie = static function (array $data) use ($rawKey): string {
    $iv = random_bytes(16);
    $json = json_encode($data);
    $cipher = openssl_encrypt((string) $json, 'AES-256-CBC', $rawKey, OPENSSL_RAW_DATA, $iv);
    $payload = base64_encode($iv . $cipher);
    $mac = hash_hmac('sha256', $payload, $rawKey);
    return $payload . '.' . $mac;
};

$decryptCookie = static function (?string $cookieValue) use ($rawKey): array {
    if (! is_string($cookieValue) || ! str_contains($cookieValue, '.')) {
        return [];
    }
    [$payload, $mac] = explode('.', $cookieValue, 2);
    $expectedMac = hash_hmac('sha256', $payload, $rawKey);
    if (! hash_equals($expectedMac, $mac)) {
        return [];
    }
    $raw = base64_decode($payload, true);
    if ($raw === false || strlen($raw) <= 16) {
        return [];
    }
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $rawKey, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) {
        return [];
    }
    $decoded = json_decode($plain, true);
    return is_array($decoded) ? $decoded : [];
};

$sessionData = $decryptCookie($_COOKIE[$cookieName] ?? null);
if (empty($sessionData['csrf_token'])) {
    $sessionData['csrf_token'] = bin2hex(random_bytes(16));
}

$saveCookieSession = static function (array $data) use ($cookieName, $encryptCookie): void {
    setcookie($cookieName, $encryptCookie($data), [
        'expires' => time() + 7200,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
};

// Determine base URL & route path
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$baseDir = rtrim(str_replace(['/public/index.php', '/api/index.php', '/index.php'], '', $scriptName), '/');
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$routePath = $baseDir !== '' && str_starts_with($requestUri, $baseDir)
    ? substr($requestUri, strlen($baseDir))
    : $requestUri;
$routePath = '/' . ltrim((string) $routePath, '/');
if (str_starts_with($routePath, '/public/')) {
    $routePath = substr($routePath, 7);
}
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$routes = [
    'pds.index' => ($baseDir ?: '') . '/',
    'pds.unlock' => ($baseDir ?: '') . '/passcode/unlock',
    'pds.lock' => ($baseDir ?: '') . '/passcode/lock',
    'pds.scan' => ($baseDir ?: '') . '/api/scan-pds',
];

$sendJson = static function (array $payload, int $status = 200) use (&$sessionData, $saveCookieSession): never {
    $saveCookieSession($sessionData);
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

// 3. Handle POST /passcode/unlock (Rate-limited: 10 attempts/minute per IP)
if ($method === 'POST' && $routePath === '/passcode/unlock') {
    $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
    $rateFile = sys_get_temp_dir() . '/pds_rate_' . substr($ipHash, 0, 16) . '.json';
    $now = time();
    $attempts = [];
    if (is_readable($rateFile)) {
        $rawRate = json_decode((string) file_get_contents($rateFile), true);
        if (is_array($rawRate)) {
            $attempts = array_values(array_filter($rawRate, static fn ($t) => is_int($t) && ($now - $t) < 60));
        }
    }
    if (count($attempts) >= 10) {
        $sendJson([
            'ok' => false,
            'message' => 'Too many passcode attempts. Please wait 1 minute before trying again.',
        ], 429);
    }

    $rawBody = (string) file_get_contents('php://input');
    $jsonBody = json_decode($rawBody, true);
    $inputPasscode = (string) ($jsonBody['passcode'] ?? $_POST['passcode'] ?? '');

    if ($configuredPasscode !== '' && ($inputPasscode === '' || ! hash_equals($configuredPasscode, $inputPasscode))) {
        $attempts[] = $now;
        @file_put_contents($rateFile, json_encode($attempts));
        $sendJson([
            'ok' => false,
            'message' => 'Invalid shared passcode. Please try again.',
        ], 422);
    }

    @unlink($rateFile);
    $sessionData['pds_unlocked'] = true;
    $sendJson(['ok' => true]);
}

// 4. Handle POST /passcode/lock
if ($method === 'POST' && $routePath === '/passcode/lock') {
    unset($sessionData['pds_unlocked']);
    $sessionData['csrf_token'] = bin2hex(random_bytes(16));
    $sendJson(['ok' => true]);
}

// 5. VerifySharedPasscode Gate
$isUnlocked = $configuredPasscode === '' || ! empty($sessionData['pds_unlocked']);

// 6. Handle POST /api/scan-pds
if ($method === 'POST' && $routePath === '/api/scan-pds') {
    if (! $isUnlocked) {
        $sendJson([
            'ok' => false,
            'message' => 'Session locked. Please unlock the application with the shared access passcode.',
        ], 401);
    }

    // Stage 1 Free Table OCR-to-Text Mode (Zero Gemini API Quota Used)
    $scanModeInput = trim((string) ($_POST['scan_mode'] ?? ''));
    if ($scanModeInput === 'ocr_text_only') {
        $ocrTexts = [];
        $ocrWords2D = [];

        $runOcrSpaceOnFile = static function (string $tmpPath, string $origName, int $pageIdx) use (&$ocrTexts, &$ocrWords2D): void {
            if ($tmpPath === '' || ! is_readable($tmpPath)) {
                return;
            }
            $rawBytes = @file_get_contents($tmpPath);
            if ($rawBytes === false || $rawBytes === '') {
                return;
            }
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) ?: 'jpg';
            $mime = match ($ext) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
                default => 'image/jpeg',
            };
            $base64Img = "data:{$mime};base64," . base64_encode($rawBytes);
            unset($rawBytes);

            $ch = curl_init('https://api.ocr.space/parse/image');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_POSTFIELDS => [
                    'apikey' => 'helloworld',
                    'base64Image' => $base64Img,
                    'isTable' => 'true',
                    'isOverlayRequired' => 'true',
                    'OCREngine' => '2',
                    'scale' => 'true',
                ],
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            if (! is_string($res) || $res === '') {
                return;
            }
            $decodedOcr = json_decode($res, true);
            $parsedResults = $decodedOcr['ParsedResults'] ?? [];
            if (! is_array($parsedResults)) {
                return;
            }
            foreach ($parsedResults as $pIdx => $pr) {
                $txt = trim((string) ($pr['ParsedText'] ?? ''));
                $txt = trim((string) preg_replace('/\[Note:.*?\]\s*/is', '', $txt));
                if ($txt !== '') {
                    $ocrTexts[] = "=== OCR PAGE " . ($pageIdx + $pIdx + 1) . " ===\n" . $txt;
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
                                $maxY = $bEdge;
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
        };

        if (! empty($_FILES['page_images']) && is_array($_FILES['page_images'])) {
            $pi = $_FILES['page_images'];
            if (is_array($pi['tmp_name'] ?? null)) {
                foreach ($pi['tmp_name'] as $idx => $tmpName) {
                    $runOcrSpaceOnFile((string) $tmpName, (string) ($pi['name'][$idx] ?? "page_{$idx}.jpg"), (int) $idx);
                }
            } else {
                $runOcrSpaceOnFile((string) ($pi['tmp_name'] ?? ''), (string) ($pi['name'] ?? 'page.jpg'), 0);
            }
        } elseif (! empty($_FILES['pds_file']) && is_array($_FILES['pds_file'])) {
            $f = $_FILES['pds_file'];
            $runOcrSpaceOnFile((string) ($f['tmp_name'] ?? ''), (string) ($f['name'] ?? 'pds.jpg'), 0);
        }

        $sendJson([
            'ok' => count($ocrTexts) > 0,
            'converted_text' => implode("\n", $ocrTexts),
            'items_2d' => $ocrWords2D,
        ]);
    }

    if ($geminiApiKey === '' || $geminiApiKey === 'your_gemini_api_key_here') {
        $sendJson(['ok' => false, 'message' => 'GEMINI_API_KEY is not configured in .env.'], 502);
    }

    $extractedTextInput = trim((string) ($_POST['extracted_text'] ?? ''));
    if ($extractedTextInput === '') {
        $rawBodyInput = (string) file_get_contents('php://input');
        if ($rawBodyInput !== '' && str_starts_with(trim($rawBodyInput), '{')) {
            $decodedBody = json_decode($rawBodyInput, true);
            $extractedTextInput = trim((string) ($decodedBody['extracted_text'] ?? ''));
        }
    }

    $userParts = [];
    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    // Collect any uploaded page_images[] or pds_file
    $collectFilePart = static function (string $tmpPath, string $origName, int $fileSize, int $errCode) use (&$userParts, $allowedExts): void {
        if ($errCode !== UPLOAD_ERR_OK || $tmpPath === '' || $fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
            return;
        }
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }
        if (! in_array($ext, $allowedExts, true)) {
            return;
        }
        $rawBytes = @file_get_contents($tmpPath);
        if ($rawBytes === false || $rawBytes === '') {
            return;
        }
        $mimeType = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        $userParts[] = [
            'inlineData' => [
                'mimeType' => $mimeType,
                'data' => base64_encode($rawBytes),
            ],
        ];
    };

    if (! empty($_FILES['pds_file']) && is_array($_FILES['pds_file'])) {
        $f = $_FILES['pds_file'];
        $collectFilePart(
            (string) ($f['tmp_name'] ?? ''),
            (string) ($f['name'] ?? 'pds.jpg'),
            (int) ($f['size'] ?? 0),
            (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE)
        );
    }

    if (! empty($_FILES['page_images']) && is_array($_FILES['page_images'])) {
        $pi = $_FILES['page_images'];
        if (is_array($pi['tmp_name'] ?? null)) {
            foreach ($pi['tmp_name'] as $idx => $tmpName) {
                $collectFilePart(
                    (string) $tmpName,
                    (string) ($pi['name'][$idx] ?? "page_{$idx}.jpg"),
                    (int) ($pi['size'][$idx] ?? 0),
                    (int) ($pi['error'][$idx] ?? UPLOAD_ERR_NO_FILE)
                );
            }
        } else {
            $collectFilePart(
                (string) ($pi['tmp_name'] ?? ''),
                (string) ($pi['name'] ?? 'page.jpg'),
                (int) ($pi['size'] ?? 0),
                (int) ($pi['error'] ?? UPLOAD_ERR_NO_FILE)
            );
        }
    }

    if ($extractedTextInput !== '') {
        $userParts[] = [
            'text' => "Supplementary text extracted from the PDS pages:\n"
                . "--- BEGIN EXTRACTED PDS TEXT ---\n"
                . mb_substr($extractedTextInput, 0, 45000)
                . "\n--- END EXTRACTED PDS TEXT ---",
        ];
    }

    if ($userParts === []) {
        $sendJson(['ok' => false, 'message' => 'Please select a PDS PDF or image file to scan.'], 422);
    }

    $userParts[] = [
        'text' => 'Inspect ALL provided pages and text carefully. Note that Work Experience (Section V / Item 28) and Learning & Development / Trainings (Section VII / Item 30) are often on DIFFERENT pages (e.g. Page 2 and Page 3). Extract EVERY row from BOTH Section V (Work Experience) and Section VII (Learning and Development / Training Programs) across all pages.',
    ];

    $todayFormatted = date('d/m/Y');

    // Reuse the exact same schema & prompt from GeminiPDSService
    $serviceFile = $basePath . '/app/Services/GeminiPDSService.php';
    $systemPrompt = <<<PROMPT
You are an expert document parser specialized in the Philippine Civil Service Commission Personal Data Sheet (CS Form No. 212, Revised 2025).

STRICT PRIVACY & EXTRACTION RULES:
1. Extract ONLY two tables if present in the uploaded PDF or image:
   - Section V (Item 28): WORK EXPERIENCE
   - Section VII (Item 30): LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS / TRAINING PROGRAMS ATTENDED
2. Extract the applicant's full name ("applicant_name") if visible anywhere in the document (e.g. Section I Surname / First Name / Middle Name or signature block). Do NOT extract government IDs (TIN, GSIS, Pag-IBIG, PhilHealth, PhilSys/PSN), residential addresses, contact numbers, or legal questionnaire answers.
3. Ignore blank placeholder rows or rows labeled "N/A", "NONE", or "(Continue on separate sheet if necessary)".
4. Format all dates strictly as "dd/mm/yyyy" (e.g., "01/07/2026", "28/08/2025").
   - If a work experience "To" date says "PRESENT", "TO DATE", or "CURRENT", output "PRESENT" in uppercase.
5. For each Work Experience item:
   - "position_title", "department_agency_company", "date_from", "date_to", "status_of_appointment", "gov_service" ("Y" or "N"), and "computed_years" (decimal years between date_from and date_to using {$todayFormatted} when date_to is "PRESENT", rounded to 1 decimal place).
6. For each Learning & Development / Training item:
   - "training_title", "date_from", "date_to", "hours" (numeric), "type_of_ld", and "conducted_by".
PROMPT;

    $schema = [
        'type' => 'OBJECT',
        'properties' => [
            'applicant_name' => ['type' => 'STRING'],
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

    $candidateModels = array_values(array_unique(array_filter([
        $geminiModel,
        'gemini-flash-lite-latest',
        'gemini-3-flash-preview',
        'gemini-3.1-flash-lite-preview',
        'gemini-2.5-flash',
    ])));

    $decodedResponse = null;
    $httpCode = 0;

    foreach ($candidateModels as $tryModel) {
        $generationConfig = [
            'temperature' => 0.0,
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
        ];
        if (str_contains($tryModel, '2.5')) {
            $generationConfig['thinkingConfig'] = [
                'thinkingBudget' => 0,
            ];
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $userParts,
                ],
            ],
            'generationConfig' => $generationConfig,
        ];

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($tryModel)
        );

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $geminiTimeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $geminiApiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $responseBody = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        unset($payload);

        if ($curlErrNo === 0 && is_string($responseBody) && $httpCode >= 200 && $httpCode < 300) {
            $decodedResponse = json_decode($responseBody, true);
            break;
        }

        if (is_string($responseBody)) {
            $decodedResponse = json_decode($responseBody, true);
        }

        // Rotate to next model on 429 (quota), 503 (overload), 404 (not found), or timeout
        if ($curlErrNo !== 0 || in_array($httpCode, [429, 503, 404, 0], true)) {
            continue;
        }

        break;
    }

    unset($userParts);

    if (! is_array($decodedResponse) || $httpCode < 200 || $httpCode >= 300) {
        $apiErr = (string) ($decodedResponse['error']['message'] ?? ('HTTP ' . $httpCode));
        $sendJson([
            'ok' => false,
            'message' => 'Gemini API Error (' . $httpCode . '): ' . $apiErr,
        ], 502);
    }

    $jsonText = (string) ($decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');
    $extracted = json_decode($jsonText, true);

    if (! is_array($extracted)) {
        $sendJson([
            'ok' => false,
            'message' => 'Failed to parse structured JSON output from Gemini API.',
        ], 502);
    }

    // Detect whether the document dates are primarily mm/dd/yyyy or dd/mm/yyyy
    $allRawDates = [];
    foreach (($extracted['work_experience'] ?? []) as $r) {
        if (is_array($r)) {
            $allRawDates[] = (string) ($r['date_from'] ?? '');
            $allRawDates[] = (string) ($r['date_to'] ?? '');
        }
    }
    foreach (($extracted['trainings'] ?? []) as $r) {
        if (is_array($r)) {
            $allRawDates[] = (string) ($r['date_from'] ?? '');
            $allRawDates[] = (string) ($r['date_to'] ?? '');
        }
    }

    $firstGt12 = 0;
    $secondGt12 = 0;
    foreach ($allRawDates as $ds) {
        $c = trim(str_replace(['.', '-'], '/', $ds));
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $c, $m)) {
            $p1 = (int) $m[1];
            $p2 = (int) $m[2];
            if ($p1 > 12 && $p2 <= 12) {
                $firstGt12++;
            } elseif ($p2 > 12 && $p1 <= 12) {
                $secondGt12++;
            }
        }
    }
    $preferMdy = ($secondGt12 > 0 && $firstGt12 === 0);

    $parseFlexibleDate = static function (string $dateStr) use ($preferMdy): ?DateTimeImmutable {
        $clean = trim(str_replace(['.', '-'], '/', $dateStr));
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
                } elseif ($preferMdy) {
                    $month = $a;
                    $day = $b;
                } else {
                    $day = $a;
                    $month = $b;
                }
            }

            if (checkdate($month, $day, $year)) {
                return DateTimeImmutable::createFromFormat('!Y-n-j', "{$year}-{$month}-{$day}") ?: null;
            }
        }
        return null;
    };

    $calcYears = static function (string $fromStr, string $toStr, float $fallback = 0.0) use ($parseFlexibleDate): float {
        $start = $parseFlexibleDate($fromStr);
        if (! $start instanceof DateTimeImmutable) {
            return round(max(0.0, $fallback), 1);
        }
        $end = in_array(strtoupper(trim($toStr)), ['PRESENT', 'TO DATE', 'CURRENT', 'ONGOING', 'NOW'], true)
            ? new DateTimeImmutable('today')
            : $parseFlexibleDate($toStr);
        if (! $end instanceof DateTimeImmutable || $end < $start) {
            return 0.0;
        }
        $days = (int) $start->diff($end)->days + 1;
        return round($days / 365.25, 1);
    };

    $rawWorkRows = is_array($extracted['work_experience'] ?? null) ? $extracted['work_experience'] : [];
    $workRows = [];
    foreach ($rawWorkRows as $row) {
        if (! is_array($row)) {
            continue;
        }
        $pos = trim((string) ($row['position_title'] ?? ''));
        $comp = trim((string) ($row['department_agency_company'] ?? ''));
        if ($pos === '' && $comp === '') {
            continue;
        }
        if (in_array(strtoupper($pos), ['N/A', 'NONE', 'NIL'], true)) {
            continue;
        }
        $df = trim((string) ($row['date_from'] ?? ''));
        $dt = trim((string) ($row['date_to'] ?? ''));
        if (in_array(strtoupper($dt), ['PRESENT', 'TO DATE', 'CURRENT', 'ONGOING', 'NOW'], true)) {
            $dt = 'PRESENT';
        }
        $fb = isset($row['computed_years']) && is_numeric($row['computed_years']) ? (float) $row['computed_years'] : 0.0;
        $gov = strtoupper(trim((string) ($row['gov_service'] ?? 'N')));
        $workRows[] = [
            'position_title' => $pos,
            'department_agency_company' => $comp,
            'date_from' => $df,
            'date_to' => $dt,
            'status_of_appointment' => trim((string) ($row['status_of_appointment'] ?? '')),
            'gov_service' => str_starts_with($gov, 'Y') ? 'Y' : 'N',
            'computed_years' => $calcYears($df, $dt, $fb),
            'included' => true,
        ];
    }

    $rawTrainingRows = is_array($extracted['trainings'] ?? null) ? $extracted['trainings'] : [];
    $trainingRows = [];
    foreach ($rawTrainingRows as $row) {
        if (! is_array($row)) {
            continue;
        }
        $title = trim((string) ($row['training_title'] ?? ''));
        if ($title === '' || in_array(strtoupper($title), ['N/A', 'NONE', 'NIL'], true)) {
            continue;
        }
        $hrs = isset($row['hours']) && is_numeric($row['hours']) ? round(max(0.0, (float) $row['hours']), 1) : 0;
        if (floor((float) $hrs) == (float) $hrs) {
            $hrs = (int) $hrs;
        }
        $trainingRows[] = [
            'training_title' => $title,
            'date_from' => trim((string) ($row['date_from'] ?? '')),
            'date_to' => trim((string) ($row['date_to'] ?? '')),
            'hours' => $hrs,
            'type_of_ld' => trim((string) ($row['type_of_ld'] ?? 'Technical')),
            'conducted_by' => trim((string) ($row['conducted_by'] ?? '')),
            'included' => true,
        ];
    }

    $applicantName = trim((string) ($extracted['applicant_name'] ?? ''));

    $sendJson([
        'ok' => true,
        'message' => (count($workRows) === 0 && count($trainingRows) === 0)
            ? 'Scan finished, but no Work Experience (Section V) or Training (Section VII) rows were detected on this page.'
            : sprintf('Extracted %d training(s) and %d work experience item(s).', count($trainingRows), count($workRows)),
        'data' => [
            'applicant_name' => $applicantName,
            'work_experience' => $workRows,
            'trainings' => $trainingRows,
        ],
    ]);
}

// 7. Render Single-Screen Blade Template (resources/views/pds.blade.php)
$saveCookieSession($sessionData);
$isLocked = ! $isUnlocked;
$passcodeEnabled = $configuredPasscode !== '';
$csrfToken = $sessionData['csrf_token'];

$bladeContent = (string) file_get_contents($basePath . '/resources/views/pds.blade.php');

// Compile the few Blade directives in pds.blade.php for standalone execution
$compiledHtml = str_replace(
    [
        '{{ csrf_token() }}',
        "{{ route('pds.unlock') }}",
        "{{ route('pds.lock') }}",
        "{{ route('pds.scan') }}",
        "{{ !empty(\$passcodeEnabled) ? 'true' : 'false' }}",
    ],
    [
        htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'),
        $routes['pds.unlock'],
        $routes['pds.lock'],
        $routes['pds.scan'],
        $passcodeEnabled ? 'true' : 'false',
    ],
    $bladeContent
);

if (preg_match('/@if\(!empty\(\$isLocked\)\)(.*?)@else(.*?)@endif/s', $compiledHtml, $matches)) {
    $selectedBlock = $isLocked ? $matches[1] : $matches[2];
    $compiledHtml = preg_replace_callback('/@if\(!empty\(\$isLocked\)\).*?@endif/s', static fn () => $selectedBlock, $compiledHtml, 1) ?? $compiledHtml;
}

header('Content-Type: text/html; charset=utf-8');
echo $compiledHtml;
