# Security, Data Privacy & AI Confidentiality Rules (RA 10173)

This document defines the mandatory security, confidentiality, and data-handling rules for the **CS Form No. 212 (PDS) Auto-Compute Workspace** in alignment with the **Philippine Data Privacy Act of 2012 (Republic Act No. 10173)**.

---

## 1. Zero Permanent Storage & Automatic Memory Purge
- **No Database Storage**: This application has **zero database tables** for applicant records, Personal Data Sheets, work histories, or training lists.
- **No Server Disk Persistence**: Uploaded PDFs and page images are read directly from transient request memory (`php://input` / temporary upload stream) and explicitly purged (`unset()`) immediately after extraction completes.
- **No Sensitive Payload Logging**: Application logs, error handlers, and debug traces MUST NEVER log raw PDS text, base64 images, applicant names, or extracted JSON payloads.
- **No HTTP/CDN Caching**: All scan responses and workspace pages MUST send `Cache-Control: no-store, no-cache, must-revalidate, private`, `Pragma: no-cache`, and `Referrer-Policy: no-referrer`.

## 2. Session-Only Browser Isolation
- **Volatile Tab Memory Only**: Extracted rows exist strictly inside the evaluator's active browser tab (`sessionStorage`) — never in `localStorage`, cookies, or shared cloud storage.
- **Automatic Wipe Triggers**: All loaded PDS data, previews, and converted text are immediately erased from browser memory when the user:
  1. Drops or selects a new PDS file (default auto-reset mode),
  2. Clicks **Clear All**,
  3. Clicks **Lock**, or
  4. Closes the browser tab.

## 3. Data Minimization (RA 10173 Principle)
The system and AI prompt MUST strictly enforce **Data Minimization**:
- **Allowed Fields Only**:
  - Applicant Name (for HR verification header)
  - PDS Date Accomplished (Page 4, used solely as the `PRESENT` cut-off date)
  - Section V / Item 28: Work Experience
  - Section VII / Item 30: Learning & Development / Trainings
- **Strictly Prohibited / Ignored Fields**:
  - Government IDs (TIN, GSIS, Pag-IBIG, PhilHealth, PhilSys / PSN, SSS)
  - Residential & Permanent Addresses, ZIP Codes, Telephone / Mobile Numbers, Email Addresses
  - Birthdate, Place of Birth, Citizenship, Civil Status, Physical Attributes
  - Family Background, Character References, and Page 4 Legal Questionnaire Answers
- **Client-Side Page Filtering**: Page 1 personal details (below the applicant name header) and Page 4 legal Q&A are stripped client-side before transmission.

## 4. AI / Gemini Statelessness & Non-Leakage Policy
- **Stateless API Calls**: Every Gemini API call is a single, isolated, stateless `generateContent` request with **no chat history, no memory persistence, and no public indexing or sharing**.
- **Strict Prompt Guardrail**: The AI system instruction explicitly forbids echoing, retaining, or outputting any government IDs, contact details, or unrelated personal information.
- **Important Deployment Note for Government / HR Production Use**:
  - **100% On-Device Option**: Selecting **`Local OCR / PDF Only (No API)`** on digital PDFs parses all text locally inside the user's browser (`pdf.js`) with **zero external transmission**.
  - **Paid API Tier Recommended for AI Mode**: When using **Hybrid** or **Gemini Vision** mode with real employee PDS documents, administrators SHOULD use a **Google Cloud Billing-enabled (Paid Tier) Gemini API key or Vertex AI**, under which Google contractually enforces **zero training on API inputs/outputs** (unlike the Free Tier, where Google's standard terms may permit product-improvement review).

## 5. Access Control
- **Shared Passcode Gate**: The workspace and `/api/scan-pds` endpoint are protected behind a timing-safe (`hash_equals`) shared passcode gate stored in an encrypted, `HttpOnly`, `SameSite=Lax` session cookie with rate-limiting (`throttle:10,1`).
- **Authorized Personnel Only**: Only authorized HR / Recruitment / Selection evaluators may access the workspace and print evaluation sheets.
