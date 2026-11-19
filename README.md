# PDS Auto-Compute — CS Form No. 212 (Revised 2025)

A minimalist, ultra-clean, privacy-first **Laravel 11** web application ready for **Vercel** serverless deployment that extracts **Work Experience (Section V / Item 28)** and **Learning & Development / Trainings (Section VII / Item 30)** from a Philippine Civil Service Commission Personal Data Sheet (**CS Form No. 212, Revised 2025**) PDF or image using **Gemini Structured Outputs**, lets evaluators selectively check/uncheck items, and renders a live, print-ready evaluation sheet on **one single uncluttered screen**—with **zero permanent database or disk storage**.

---

## Key Architecture & Privacy Guarantees

1. **Zero Database & Zero Disk Persistence**:
   - No database migrations, no `users` tables, no ORM models.
   - Uploaded PDFs/images are converted to `base64` strictly in memory (`GeminiPDSService`), sent to the Gemini REST API with a strict JSON Schema (`responseMimeType: "application/json"`), and discarded immediately after the HTTP response.
   - Sensitive PII sections (TIN, GSIS, Pag-IBIG, PhilHealth, PhilSys/PSN, residential addresses, family background, legal answers) are never extracted or logged.
2. **Safe for 3+ Concurrent Users**:
   - Uses encrypted cookie sessions (`SESSION_DRIVER=cookie`) for the shared passcode gate (`VerifySharedPasscode`).
   - Extracted rows and checkbox selections live exclusively in each user's isolated browser tab (`sessionStorage` + Alpine.js state), ensuring zero cross-user data leakage.
3. **Resilient Single-Screen UX**:
   - Cancelable Gemini scan with live elapsed timer, non-blocking error banner with one-click **"+ Add Row Manually"** shortcuts, **"Load Sample PDS"** test dataset button, and `@media print` support that prints only the right-hand Evaluation Preview Sheet.

---

## Local Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Create environment file and generate encryption key
cp .env.example .env
php artisan key:generate

# 3. Configure your Gemini API key and optional shared passcode in .env
# GEMINI_API_KEY=your_gemini_api_key_here
# APP_ACCESS_PASSCODE=psa2025

# 4. Start local development server
php artisan serve
```

Visit `http://localhost:8000`.

---

## Deploying to Vercel

1. Push this repository to GitHub / GitLab / Bitbucket or use the Vercel CLI (`vercel`).
2. In your **Vercel Project Settings → Environment Variables**, configure:
   - `APP_KEY`: Output of `php artisan key:generate --show` (e.g. `base64:...`)
   - `APP_ACCESS_PASSCODE`: Shared passcode for your HR evaluators (leave empty to disable the lock screen)
   - `GEMINI_API_KEY`: Your Google Gemini API Key
   - `GEMINI_MODEL`: `gemini-2.5-flash`
   - `SESSION_DRIVER`: `cookie`
   - `SESSION_SECURE_COOKIE`: `true`
3. Deploy with:
   ```bash
   vercel --prod
   ```
