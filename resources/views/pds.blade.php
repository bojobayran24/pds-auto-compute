<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f4f5f7] antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CS Form 212 Calculator — Work Experience &amp; L&amp;D</title>

    <!-- Fonts: Inter for UI + JetBrains Mono for tabular dates/numbers -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                }
            }
        };
    </script>

    <!-- PDF.js (Fast in-browser PDF coordinate & text extraction + multi-page canvas rendering) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        if (window.pdfjsLib) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>

    <!-- Tesseract.js (Client-side OCR for scanned PDS images & flattened PDFs) -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .tabular-nums { font-variant-numeric: tabular-nums; }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Print styles: Print ONLY the right-hand Evaluation Sheet cleanly on A4 */
        @media print {
            @page {
                size: A4 portrait;
                margin: 14mm;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10.5pt;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .print-layout {
                display: block !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-sheet {
                width: 100% !important;
                max-width: 100% !important;
                max-height: none !important;
                overflow: visible !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                position: static !important;
            }
            .print-table th,
            .print-table td {
                border: 1px solid #000000 !important;
                padding: 5px 8px !important;
            }
            .print-input {
                border: none !important;
                background: transparent !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-full bg-[#f4f5f7] text-slate-900 selection:bg-blue-100 selection:text-blue-950">

@if(!empty($isLocked))
    <!-- ======================================================================= -->
    <!-- PASSCODE GATE (MINIMALIST & INSTITUTIONAL)                              -->
    <!-- ======================================================================= -->
    <main
        x-data="{
            passcode: '',
            showPasscode: false,
            submitting: false,
            errorMessage: '',
            async submitUnlock() {
                if (!this.passcode.trim()) {
                    this.errorMessage = 'Enter the access passcode to continue.';
                    return;
                }
                this.submitting = true;
                this.errorMessage = '';
                try {
                    const response = await fetch('{{ route('pds.unlock') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({ passcode: this.passcode })
                    });
                    const result = await response.json().catch(() => ({}));
                    if (response.ok && result.ok) {
                        window.location.reload();
                    } else {
                        this.errorMessage = result.message || 'Incorrect passcode.';
                        this.passcode = '';
                    }
                } catch (err) {
                    this.errorMessage = 'Unable to verify passcode. Check your connection.';
                } finally {
                    this.submitting = false;
                }
            }
        }"
        class="min-h-screen flex flex-col items-center justify-center px-4"
    >
        <div class="w-full max-w-sm bg-white border border-slate-300 rounded-md shadow-xs p-6">
            <div class="border-b border-slate-200 pb-4 mb-5">
                <div class="text-[11px] font-mono uppercase tracking-wider text-slate-500">
                    CS Form No. 212 (Revised 2025)
                </div>
                <h1 class="text-base font-semibold text-slate-900 mt-0.5">
                    PDS Experience &amp; Training Calculator
                </h1>
            </div>

            <form @submit.prevent="submitUnlock" class="space-y-4">
                <div>
                    <label for="passcode-input" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Access Passcode
                    </label>
                    <div class="relative">
                        <input
                            id="passcode-input"
                            :type="showPasscode ? 'text' : 'password'"
                            x-model="passcode"
                            autofocus
                            autocomplete="current-password"
                            placeholder="Enter passcode"
                            class="w-full rounded border border-slate-300 bg-white px-3 py-2 pr-14 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none"
                        >
                        <button
                            type="button"
                            @click="showPasscode = !showPasscode"
                            class="absolute inset-y-0 right-0 px-3 text-xs text-slate-500 hover:text-slate-800"
                            x-text="showPasscode ? 'Hide' : 'Show'"
                        ></button>
                    </div>
                </div>

                <template x-if="errorMessage">
                    <div class="rounded bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700" x-text="errorMessage"></div>
                </template>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full rounded bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-50 transition"
                >
                    <span x-text="submitting ? 'Unlocking...' : 'Continue'"></span>
                </button>
            </form>

            <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5">
                <div class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Confidential • Data Privacy Act of 2012 (RA 10173)</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-normal">
                    Session-only workspace. Uploaded PDS files are processed in volatile memory and immediately purged — never saved to a database, server disk, or public log.
                </p>
            </div>
        </div>
    </main>
@else
    <!-- ======================================================================= -->
    <!-- MAIN SINGLE-SCREEN WORKBENCH                                            -->
    <!-- ======================================================================= -->
    <div
        x-data="pdsAutoComputeApp({ passcodeEnabled: {{ !empty($passcodeEnabled) ? 'true' : 'false' }} })"
        x-init="init()"
        class="min-h-screen flex flex-col"
    >
        <!-- Top Utility Bar -->
        <header class="no-print sticky top-0 z-30 bg-white border-b border-slate-300">
            <div class="max-w-[1760px] mx-auto px-3 sm:px-5 lg:px-6 h-12 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="font-semibold text-sm text-slate-900 truncate">
                        PDS Auto-Compute
                    </span>
                    <span class="hidden sm:inline-block text-slate-300">|</span>
                    <span class="hidden sm:inline-block text-xs text-slate-500 truncate">
                        CS Form No. 212 (Revised 2025)
                    </span>
                    <button
                        type="button"
                        @click="showPrivacyModal = true"
                        class="hidden md:inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full hover:bg-emerald-100 transition whitespace-nowrap"
                        title="View Data Privacy & Confidentiality Assurance (RA 10173)"
                    >
                        <i data-lucide="shield-check" class="w-3 h-3"></i>
                        <span>Data Privacy (RA 10173)</span>
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="clearAll()"
                        :disabled="!hasAnyData"
                        class="px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-red-50 hover:text-red-700 hover:border-red-200 disabled:opacity-40 disabled:pointer-events-none transition"
                    >
                        Clear All
                    </button>

                    <button
                        type="button"
                        @click="printEvaluationSheet()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-slate-900 border border-slate-900 rounded hover:bg-slate-800 transition"
                    >
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Print Sheet</span>
                    </button>

                    <template x-if="passcodeEnabled">
                        <button
                            type="button"
                            @click="lockWorkspace()"
                            class="px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 border border-transparent hover:border-slate-300 rounded transition"
                            title="Lock session"
                        >
                            Lock
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <!-- Two-Column Split Workspace -->
        <main class="flex-1 max-w-[1760px] w-full mx-auto px-3 sm:px-5 lg:px-6 py-5 print-layout">
            <div class="flex flex-col lg:flex-row items-start gap-5">

                <!-- =========================================================== -->
                <!-- LEFT COLUMN (40% width): SCANNER + CLEAN CHECKLIST PICKER   -->
                <!-- =========================================================== -->
                <div class="no-print w-full lg:w-[40%] xl:w-[39%] space-y-4 shrink-0 min-w-0">

                    <!-- Compact Upload & Multi-Page Scanner Bar -->
                    <section class="bg-white border border-slate-300 rounded-md p-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-slate-200">
                            <div>
                                <h2 class="text-xs font-semibold text-slate-900">
                                    Scan PDS (Multi-Page PDF or Multiple Images)
                                </h2>
                                <p class="text-[11px] text-slate-500">
                                    Automatically scans all pages for Work Experience (Page 2) &amp; Trainings (Page 3).
                                </p>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <select
                                    id="scan-mode"
                                    x-model="scanEngine"
                                    class="text-[11px] font-medium text-slate-800 bg-slate-50 border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                    title="Extraction Engine"
                                >
                                    <option value="hybrid">Hybrid (Multi-Page + AI Turbo)</option>
                                    <option value="local">Local OCR / PDF Only (No API)</option>
                                    <option value="gemini">Gemini Vision (Multi-Page Turbo)</option>
                                </select>

                                <label class="inline-flex items-center gap-1 text-xs text-slate-600 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        x-model="appendOnScan"
                                        class="w-3.5 h-3.5 rounded-xs border-slate-300 text-blue-600 focus:ring-0"
                                    >
                                    <span>Append</span>
                                </label>
                            </div>
                        </div>

                        <!-- Drop Area (Supports single or multiple files) -->
                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                            :class="isDragging ? 'border-blue-600 bg-blue-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100/70'"
                            class="relative border border-dashed rounded px-3.5 py-2.5 transition"
                        >
                            <input
                                x-ref="fileInput"
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                @change="handleFileSelect($event)"
                                :disabled="isScanning"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer disabled:cursor-not-allowed"
                            >

                            <!-- Default Upload Prompt -->
                            <div x-show="!isScanning" class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <i data-lucide="upload" class="w-4 h-4 text-slate-500 shrink-0"></i>
                                    <div class="truncate">
                                        <span class="text-xs font-medium text-slate-800">Drop PDS PDF or Page Images here (or select multiple)</span>
                                        <span class="block text-[11px] text-slate-500" x-text="scanEngine === 'local' ? 'Reads all PDF pages & images locally via PDF.js + Tesseract OCR' : 'Reads all PDF pages (text + page images) so Page 2 & Page 3 are never missed'"></span>
                                    </div>
                                </div>
                                <span class="shrink-0 px-2.5 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded">
                                    Select File(s)
                                </span>
                            </div>

                            <!-- Active Scanning State -->
                            <div x-show="isScanning" x-cloak class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <div class="truncate">
                                        <div class="text-xs font-medium text-slate-900 truncate">
                                            <span x-text="scanStageLabel || 'Scanning'"></span>
                                            <span x-text="scanningFileName" class="font-mono text-slate-600"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-500" x-text="scanSubStatus || 'Scanning all pages for Work Experience & Trainings...'"></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs font-mono tabular-nums text-slate-600" x-text="scanElapsed.toFixed(1) + 's'"></span>
                                    <button
                                        type="button"
                                        @click.stop="cancelScan()"
                                        class="relative z-10 px-2 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-100"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Non-Blocking Notice / Error Fallback + View Converted Text Toggle -->
                        <template x-if="scanNotice.message || convertedRawText">
                            <div class="mt-2.5 space-y-2">
                                <template x-if="scanNotice.message">
                                    <div
                                        :class="scanNotice.type === 'error'
                                            ? 'bg-amber-50 border-amber-300 text-amber-900'
                                            : 'bg-slate-100 border-slate-300 text-slate-800'"
                                        class="rounded border px-3 py-2 text-xs flex items-start justify-between gap-2"
                                    >
                                        <div class="space-y-1.5 flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p x-text="scanNotice.message" class="break-words"></p>
                                                <template x-if="convertedRawText">
                                                    <button
                                                        type="button"
                                                        @click="showConvertedText = !showConvertedText"
                                                        class="shrink-0 px-2 py-0.5 text-[11px] font-medium bg-white border border-slate-300 rounded text-slate-700 hover:bg-slate-50"
                                                        x-text="showConvertedText ? 'Hide Converted Text' : 'View Converted Text'"
                                                    ></button>
                                                </template>
                                            </div>
                                            <template x-if="scanNotice.type === 'error'">
                                                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                                    <button
                                                        type="button"
                                                        @click="addTrainingRow(true)"
                                                        class="px-2 py-0.5 text-[11px] font-medium bg-white border border-slate-300 rounded text-slate-800 hover:bg-slate-50"
                                                    >
                                                        + Add Training
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="addWorkRow(true)"
                                                        class="px-2 py-0.5 text-[11px] font-medium bg-white border border-slate-300 rounded text-slate-800 hover:bg-slate-50"
                                                    >
                                                        + Add Work Experience
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                        <button
                                            type="button"
                                            @click="scanNotice.message = ''"
                                            class="text-slate-400 hover:text-slate-700 text-xs leading-none px-1"
                                            title="Dismiss"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </template>

                                <!-- Collapsible Stage 1 Converted Text Inspector & Re-Parser -->
                                <div x-show="showConvertedText && convertedRawText" x-cloak class="rounded border border-slate-300 bg-slate-50 p-2.5 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-600">
                                            Stage 1: Converted PDF / OCR Text (Editable)
                                        </span>
                                        <button
                                            type="button"
                                            @click="reparseConvertedText()"
                                            class="px-2.5 py-1 text-[11px] font-semibold text-white bg-slate-900 rounded hover:bg-slate-800"
                                        >
                                            Parse Text to Tables →
                                        </button>
                                    </div>
                                    <textarea
                                        x-model="convertedRawText"
                                        rows="6"
                                        class="w-full font-mono text-[11px] text-slate-800 bg-white border border-slate-300 rounded p-2 focus:border-slate-900 focus:outline-none"
                                        placeholder="Converted PDF / OCR text appears here..."
                                    ></textarea>
                                </div>
                            </div>
                        </template>
                    </section>

                    <!-- ======================================================= -->
                    <!-- UNIFIED TABBED PICKER: TRAININGS | WORK EXPERIENCE      -->
                    <!-- ======================================================= -->
                    <section class="bg-white border border-slate-300 rounded-md overflow-hidden">

                        <!-- Top 2-Tab Bar (Trainings | Work Experience) -->
                        <div class="grid grid-cols-2 border-b border-slate-300 bg-slate-100">
                            <button
                                type="button"
                                @click="activeLeftTab = 'trainings'"
                                :class="activeLeftTab === 'trainings'
                                    ? 'bg-white text-slate-900 font-semibold border-b-2 border-b-blue-600'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                                class="px-2 py-2.5 text-xs flex items-center justify-center gap-1.5 border-r border-slate-300 transition whitespace-nowrap"
                            >
                                <span>Trainings</span>
                                <span
                                    :class="activeLeftTab === 'trainings' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-slate-600 border-slate-300'"
                                    class="font-mono text-[10px] tabular-nums px-1.5 py-0.5 rounded border font-semibold whitespace-nowrap"
                                    x-text="selectedTrainings.length + '/' + trainings.length + ' • ' + formattedTotalTrainingHours + ' hrs'"
                                ></span>
                            </button>

                            <button
                                type="button"
                                @click="activeLeftTab = 'work'"
                                :class="activeLeftTab === 'work'
                                    ? 'bg-white text-slate-900 font-semibold border-b-2 border-b-blue-600'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                                class="px-2 py-2.5 text-xs flex items-center justify-center gap-1.5 transition whitespace-nowrap"
                            >
                                <span class="hidden xl:inline">Work Experience</span>
                                <span class="xl:hidden">Work Exp.</span>
                                <span
                                    :class="activeLeftTab === 'work' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-slate-600 border-slate-300'"
                                    class="font-mono text-[10px] tabular-nums px-1.5 py-0.5 rounded border font-semibold whitespace-nowrap"
                                    x-text="selectedWorkExperiences.length + '/' + workExperiences.length + ' • ' + formattedTotalWorkDurationShort"
                                ></span>
                            </button>
                        </div>

                        <!-- Unified Action & Filter Sub-Toolbar for Active Tab -->
                        <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                            <!-- Search / Filter Input -->
                            <div class="flex-1 min-w-0">
                                <template x-if="activeLeftTab === 'trainings'">
                                    <input
                                        type="text"
                                        x-model="trainingSearch"
                                        placeholder="Search trainings..."
                                        class="w-full text-xs bg-white border border-slate-300 rounded px-2.5 py-1 text-slate-800 placeholder-slate-400 focus:border-slate-600 focus:outline-none"
                                    >
                                </template>
                                <template x-if="activeLeftTab === 'work'">
                                    <input
                                        type="text"
                                        x-model="workSearch"
                                        placeholder="Search work experience..."
                                        class="w-full text-xs bg-white border border-slate-300 rounded px-2.5 py-1 text-slate-800 placeholder-slate-400 focus:border-slate-600 focus:outline-none"
                                    >
                                </template>
                            </div>

                            <!-- All | None | + Add Buttons -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <template x-if="activeLeftTab === 'trainings'">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="trainings.length > 0">
                                            <div class="inline-flex rounded border border-slate-300 bg-white overflow-hidden text-[11px] font-medium">
                                                <button
                                                    type="button"
                                                    @click="toggleAllTrainings(true)"
                                                    :class="allTrainingsSelected ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                                    class="px-2 py-1 transition"
                                                >
                                                    All
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="toggleAllTrainings(false)"
                                                    :class="selectedTrainings.length === 0 ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                                    class="px-2 py-1 border-l border-slate-300 transition"
                                                >
                                                    None
                                                </button>
                                            </div>
                                        </template>

                                        <button
                                            type="button"
                                            @click="addTrainingRow(true)"
                                            class="px-2.5 py-1 text-[11px] font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 transition"
                                        >
                                            + Add
                                        </button>
                                    </div>
                                </template>

                                <template x-if="activeLeftTab === 'work'">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="workExperiences.length > 0">
                                            <div class="inline-flex rounded border border-slate-300 bg-white overflow-hidden text-[11px] font-medium">
                                                <button
                                                    type="button"
                                                    @click="toggleAllWork(true)"
                                                    :class="allWorkSelected ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                                    class="px-2 py-1 transition"
                                                >
                                                    All
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="toggleAllWork(false)"
                                                    :class="selectedWorkExperiences.length === 0 ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                                    class="px-2 py-1 border-l border-slate-300 transition"
                                                >
                                                    None
                                                </button>
                                            </div>
                                        </template>

                                        <button
                                            type="button"
                                            @click="addWorkRow(true)"
                                            class="px-2.5 py-1 text-[11px] font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 transition"
                                        >
                                            + Add
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- =================================================== -->
                        <!-- TAB 1 CONTENT: TRAININGS / LEARNING & DEVELOPMENT   -->
                        <!-- =================================================== -->
                        <div x-show="activeLeftTab === 'trainings'">

                            <!-- Empty State -->
                            <template x-if="trainings.length === 0">
                                <div class="px-4 py-8 text-center text-xs text-slate-500 space-y-2">
                                    <div>No training entries loaded yet. Upload a PDS above or <button type="button" @click="addTrainingRow(true)" class="underline text-slate-800 font-medium">add a training row manually</button>.</div>
                                    <template x-if="workExperiences.length > 0">
                                        <div>
                                            <button
                                                type="button"
                                                @click="activeLeftTab = 'work'"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100"
                                            >
                                                Switch to Work Experience (<span x-text="workExperiences.length"></span> loaded) →
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Professional Highlightable Rows -->
                            <template x-if="trainings.length > 0">
                                <div class="divide-y divide-slate-200">
                                    <template x-for="(item, index) in trainings" :key="item.id">
                                        <div x-show="matchesTrainingFilter(item)">

                                            <!-- 1. SELECTION ROW (CLICK OR DRAG TO RIGHT TABLE) -->
                                            <div
                                                x-show="!item.editing"
                                                @pointerdown="onRowPointerDown($event, 'training', item)"
                                                @click="onRowClick(item)"
                                                :class="[
                                                    item.included ? 'bg-blue-50/75 hover:bg-blue-50' : 'bg-white hover:bg-slate-50',
                                                    pointerDrag.active && pointerDrag.id === item.id ? 'opacity-45 bg-slate-100' : ''
                                                ]"
                                                class="px-3 py-2.5 flex items-center justify-between gap-2.5 cursor-grab active:cursor-grabbing select-none transition-colors"
                                                title="Click to toggle or drag to Relevant Training table"
                                            >
                                                <div class="flex items-start gap-2 min-w-0 flex-1">
                                                    <span class="mt-0.5 text-slate-300 hover:text-slate-500 text-xs leading-none select-none shrink-0 font-mono" aria-hidden="true">⋮⋮</span>
                                                    <input
                                                        type="checkbox"
                                                        x-model="item.included"
                                                        @click.stop
                                                        class="mt-0.5 w-4 h-4 rounded-xs border-slate-300 text-blue-600 focus:ring-0 cursor-pointer shrink-0"
                                                    >
                                                    <div class="min-w-0 flex-1">
                                                        <div
                                                            :class="item.included ? 'text-slate-900 font-medium' : 'text-slate-600'"
                                                            class="text-xs leading-snug break-words"
                                                            x-text="item.training_title || 'Untitled Training'"
                                                        ></div>
                                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                                            <span x-show="item.conducted_by" x-text="item.conducted_by"></span>
                                                            <span x-show="item.conducted_by && (item.date_from || item.date_to)" class="text-slate-300">•</span>
                                                            <span class="font-mono tabular-nums" x-text="formatDateRange(item.date_from, item.date_to)"></span>
                                                            <template x-if="item.type_of_ld">
                                                                <span class="text-slate-400">• <span x-text="item.type_of_ld"></span></span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right Side: Direct Hours Input + Edit/Delete -->
                                                <div class="flex items-center gap-1.5 shrink-0" @click.stop>
                                                    <div
                                                        :class="item.included ? 'border-blue-300 bg-white' : 'border-slate-200 bg-slate-50'"
                                                        class="flex items-center border rounded px-1.5 py-0.5 focus-within:border-blue-600"
                                                    >
                                                        <input
                                                            type="number"
                                                            step="0.5"
                                                            min="0"
                                                            x-model.number="item.hours"
                                                            class="w-10 text-right font-mono text-xs font-semibold tabular-nums text-slate-900 bg-transparent focus:outline-none"
                                                            title="Edit Hours"
                                                        >
                                                        <span class="ml-1 text-[10px] font-mono text-slate-500">hrs</span>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="item.editing = true"
                                                        class="px-1.5 py-1 text-[11px] text-slate-500 hover:text-slate-900 hover:bg-white/80 rounded transition"
                                                        title="Edit row details"
                                                    >
                                                        Edit
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click="removeTrainingRow(index)"
                                                        class="p-1 text-slate-400 hover:text-red-600 rounded transition"
                                                        title="Remove row"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- 2. INLINE EDIT PANEL -->
                                            <div
                                                x-show="item.editing"
                                                x-cloak
                                                class="p-3 bg-slate-50 border-y border-slate-200 space-y-2"
                                            >
                                                <div>
                                                    <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Training Title</label>
                                                    <input
                                                        type="text"
                                                        x-model="item.training_title"
                                                        placeholder="Title of Learning & Development / Training"
                                                        class="w-full text-xs font-medium text-slate-900 bg-white border border-slate-300 rounded px-2.5 py-1.5 focus:border-slate-900 focus:outline-none"
                                                    >
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Conducted / Sponsored By</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.conducted_by"
                                                            placeholder="Conducted / Sponsored By"
                                                            class="w-full text-xs text-slate-800 bg-white border border-slate-300 rounded px-2.5 py-1 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Type of L&amp;D</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.type_of_ld"
                                                            placeholder="e.g. TECHNICAL"
                                                            class="w-full text-xs text-slate-800 bg-white border border-slate-300 rounded px-2.5 py-1 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-end justify-between gap-2 pt-1">
                                                    <div class="flex items-center gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">From (dd/mm/yyyy)</label>
                                                            <input
                                                                type="text"
                                                                x-model="item.date_from"
                                                                placeholder="dd/mm/yyyy"
                                                                class="w-28 font-mono text-xs tabular-nums text-slate-800 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">To (dd/mm/yyyy)</label>
                                                            <input
                                                                type="text"
                                                                x-model="item.date_to"
                                                                placeholder="dd/mm/yyyy"
                                                                class="w-28 font-mono text-xs tabular-nums text-slate-800 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Hours</label>
                                                            <input
                                                                type="number"
                                                                step="0.5"
                                                                min="0"
                                                                x-model.number="item.hours"
                                                                class="w-20 font-mono text-xs font-semibold tabular-nums text-slate-900 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                            >
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-1.5">
                                                        <button
                                                            type="button"
                                                            @click="removeTrainingRow(index)"
                                                            class="px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded"
                                                        >
                                                            Delete
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="item.editing = false"
                                                            class="px-3 py-1 text-xs font-medium text-white bg-slate-900 hover:bg-slate-800 rounded"
                                                        >
                                                            Done
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- =================================================== -->
                        <!-- TAB 2 CONTENT: WORK EXPERIENCE                      -->
                        <!-- =================================================== -->
                        <div x-show="activeLeftTab === 'work'" x-cloak>
                            <!-- Empty State -->
                            <template x-if="workExperiences.length === 0">
                                <div class="px-4 py-8 text-center text-xs text-slate-500 space-y-2">
                                    <div>No work experience entries loaded yet. Upload a PDS above or <button type="button" @click="addWorkRow(true)" class="underline text-slate-800 font-medium">add a position manually</button>.</div>
                                    <template x-if="trainings.length > 0">
                                        <div>
                                            <button
                                                type="button"
                                                @click="activeLeftTab = 'trainings'"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100"
                                            >
                                                ← Switch to Trainings (<span x-text="trainings.length"></span> loaded)
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Professional Highlightable Rows -->
                            <template x-if="workExperiences.length > 0">
                                <div class="divide-y divide-slate-200">
                                    <template x-for="(item, index) in workExperiences" :key="item.id">
                                        <div x-show="matchesWorkFilter(item)">

                                            <!-- 1. SELECTION ROW (CLICK OR DRAG TO RIGHT TABLE) -->
                                            <div
                                                x-show="!item.editing"
                                                @pointerdown="onRowPointerDown($event, 'work', item)"
                                                @click="onRowClick(item)"
                                                :class="[
                                                    item.included ? 'bg-blue-50/75 hover:bg-blue-50' : 'bg-white hover:bg-slate-50',
                                                    pointerDrag.active && pointerDrag.id === item.id ? 'opacity-45 bg-slate-100' : ''
                                                ]"
                                                class="px-3 py-2.5 flex items-center justify-between gap-2.5 cursor-grab active:cursor-grabbing select-none transition-colors"
                                                title="Click to toggle or drag to Relevant Work Experience table"
                                            >
                                                <div class="flex items-start gap-2 min-w-0 flex-1">
                                                    <span class="mt-0.5 text-slate-300 hover:text-slate-500 text-xs leading-none select-none shrink-0 font-mono" aria-hidden="true">⋮⋮</span>
                                                    <input
                                                        type="checkbox"
                                                        x-model="item.included"
                                                        @click.stop
                                                        class="mt-0.5 w-4 h-4 rounded-xs border-slate-300 text-blue-600 focus:ring-0 cursor-pointer shrink-0"
                                                    >
                                                    <div class="min-w-0 flex-1">
                                                        <div
                                                            :class="item.included ? 'text-slate-900 font-semibold' : 'text-slate-600 font-medium'"
                                                            class="text-xs leading-snug break-words"
                                                            x-text="item.position_title || 'Untitled Position'"
                                                        ></div>
                                                        <div class="text-[11px] text-slate-600 break-words mt-0.5" x-text="item.department_agency_company || '—'"></div>
                                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                                            <span class="font-mono tabular-nums" x-text="formatDateRange(item.date_from, item.date_to)"></span>
                                                            <template x-if="item.status_of_appointment">
                                                                <span class="text-slate-400">• <span x-text="item.status_of_appointment"></span></span>
                                                            </template>
                                                            <span class="text-slate-400">• <span class="font-mono text-[11px]" x-text="'Gov: ' + (item.gov_service || 'N')"></span></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right Side: Direct Years, Months & Days Inputs + Edit/Delete -->
                                                <div class="flex items-center gap-1 shrink-0" @click.stop>
                                                    <div
                                                        :class="item.included ? 'border-blue-300 bg-white' : 'border-slate-200 bg-slate-50'"
                                                        class="flex items-center border rounded px-1.5 py-0.5 focus-within:border-blue-600"
                                                    >
                                                        <input
                                                            type="number"
                                                            step="1"
                                                            min="0"
                                                            x-model.number="item.duration_years"
                                                            @change="normalizeRowDuration(item)"
                                                            class="w-6 text-right font-mono text-xs font-semibold tabular-nums text-slate-900 bg-transparent focus:outline-none"
                                                            title="Edit Years"
                                                        >
                                                        <span class="ml-0.5 text-[10px] font-mono text-slate-500">y</span>
                                                        <span class="mx-0.5 text-slate-300">|</span>
                                                        <input
                                                            type="number"
                                                            step="1"
                                                            min="0"
                                                            x-model.number="item.duration_months"
                                                            @change="normalizeRowDuration(item)"
                                                            class="w-6 text-right font-mono text-xs font-semibold tabular-nums text-slate-900 bg-transparent focus:outline-none"
                                                            title="Edit Months (0–11)"
                                                        >
                                                        <span class="ml-0.5 text-[10px] font-mono text-slate-500">m</span>
                                                        <span class="mx-0.5 text-slate-300">|</span>
                                                        <input
                                                            type="number"
                                                            step="1"
                                                            min="0"
                                                            x-model.number="item.duration_days"
                                                            @change="normalizeRowDuration(item)"
                                                            class="w-6 text-right font-mono text-xs font-semibold tabular-nums text-slate-900 bg-transparent focus:outline-none"
                                                            title="Edit Days (0–29)"
                                                        >
                                                        <span class="ml-0.5 text-[10px] font-mono text-slate-500">d</span>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="item.editing = true"
                                                        class="px-1.5 py-1 text-[11px] text-slate-500 hover:text-slate-900 hover:bg-white/80 rounded transition"
                                                        title="Edit row details"
                                                    >
                                                        Edit
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click="removeWorkRow(index)"
                                                        class="p-1 text-slate-400 hover:text-red-600 rounded transition"
                                                        title="Remove row"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- 2. INLINE EDIT PANEL -->
                                            <div
                                                x-show="item.editing"
                                                x-cloak
                                                class="p-3 bg-slate-50 border-y border-slate-200 space-y-2"
                                            >
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Position Title</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.position_title"
                                                            placeholder="Position Title"
                                                            class="w-full text-xs font-medium text-slate-900 bg-white border border-slate-300 rounded px-2.5 py-1.5 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Department / Agency / Company</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.department_agency_company"
                                                            placeholder="Department / Agency / Office / Company"
                                                            class="w-full text-xs text-slate-800 bg-white border border-slate-300 rounded px-2.5 py-1.5 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">From (dd/mm/yyyy)</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.date_from"
                                                            @change="recomputeRowYears(item)"
                                                            placeholder="dd/mm/yyyy"
                                                            class="w-full font-mono text-xs tabular-nums text-slate-800 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">To (or PRESENT)</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.date_to"
                                                            @change="recomputeRowYears(item)"
                                                            placeholder="PRESENT"
                                                            class="w-full font-mono text-xs tabular-nums text-slate-800 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Appointment Status</label>
                                                        <input
                                                            type="text"
                                                            x-model="item.status_of_appointment"
                                                            placeholder="e.g. PERMANENT, COSW"
                                                            class="w-full text-xs text-slate-800 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                        >
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold uppercase text-slate-500 mb-0.5">Gov't Service</label>
                                                        <select
                                                            x-model="item.gov_service"
                                                            class="w-full text-xs font-mono text-slate-800 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                        >
                                                            <option value="Y">Y (Yes)</option>
                                                            <option value="N">N (No)</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <div class="flex items-center gap-1">
                                                            <label class="text-[10px] font-semibold uppercase text-slate-500">Years:</label>
                                                            <input
                                                                type="number"
                                                                step="1"
                                                                min="0"
                                                                x-model.number="item.duration_years"
                                                                @change="normalizeRowDuration(item)"
                                                                class="w-14 font-mono text-xs font-semibold tabular-nums text-slate-900 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                            >
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <label class="text-[10px] font-semibold uppercase text-slate-500">Months:</label>
                                                            <input
                                                                type="number"
                                                                step="1"
                                                                min="0"
                                                                x-model.number="item.duration_months"
                                                                @change="normalizeRowDuration(item)"
                                                                class="w-12 font-mono text-xs font-semibold tabular-nums text-slate-900 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                            >
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <label class="text-[10px] font-semibold uppercase text-slate-500">Days:</label>
                                                            <input
                                                                type="number"
                                                                step="1"
                                                                min="0"
                                                                x-model.number="item.duration_days"
                                                                @change="normalizeRowDuration(item)"
                                                                class="w-12 font-mono text-xs font-semibold tabular-nums text-slate-900 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                            >
                                                        </div>
                                                        <button
                                                            type="button"
                                                            @click="recomputeRowYears(item)"
                                                            class="text-[11px] text-slate-600 hover:text-slate-900 underline"
                                                        >
                                                            Recalculate from dates
                                                        </button>
                                                    </div>

                                                    <div class="flex items-center gap-1.5">
                                                        <button
                                                            type="button"
                                                            @click="removeWorkRow(index)"
                                                            class="px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded"
                                                        >
                                                            Delete
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="item.editing = false"
                                                            class="px-3 py-1 text-xs font-medium text-white bg-slate-900 hover:bg-slate-800 rounded"
                                                        >
                                                            Done
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                    </section>
                </div>

                <!-- =========================================================== -->
                <!-- RIGHT COLUMN (60% width): STICKY RESPONSIVE TABLES          -->
                <!-- =========================================================== -->
                <div class="w-full lg:w-[60%] xl:w-[61%] min-w-0 lg:sticky lg:top-[68px] print-sheet">
                    <section class="bg-white border border-slate-300 rounded-md shadow-2xs p-4 sm:p-6 lg:max-h-[calc(100vh-88px)] lg:overflow-y-auto print-sheet space-y-5">

                        <!-- PDS Owner & Uploaded Filename Identification Box -->
                        <div class="rounded border border-slate-300 bg-slate-50/90 p-3 space-y-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500 w-28 shrink-0">
                                    PDS Owner:
                                </label>
                                <input
                                    type="text"
                                    x-model="applicantName"
                                    placeholder="Auto-detected on scan or type applicant name..."
                                    class="print-input flex-1 min-w-0 text-xs font-bold text-slate-900 bg-white border border-slate-300 rounded px-2.5 py-1 focus:border-slate-900 focus:outline-none"
                                >
                            </div>

                            <div class="flex items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500 w-28 shrink-0">
                                        Uploaded File:
                                    </span>
                                    <span
                                        class="font-mono text-xs font-medium text-slate-800 bg-white border border-slate-200 rounded px-2.5 py-1 truncate flex-1 min-w-0"
                                        :title="uploadedFileName || 'No file uploaded yet'"
                                        x-text="uploadedFileName || 'No file uploaded yet'"
                                    ></span>
                                </div>

                                <button
                                    type="button"
                                    @click="openFilePreview()"
                                    :disabled="!hasFilePreview"
                                    class="no-print shrink-0 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 disabled:opacity-40 disabled:pointer-events-none transition"
                                    title="Preview uploaded PDS PDF or Image for verification"
                                >
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Preview PDF / File</span>
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500 w-28 shrink-0">
                                        Date Accomplished:
                                    </label>
                                    <input
                                        type="date"
                                        x-model="dateAccomplished"
                                        @change="recomputeAllPresentWorkRows()"
                                        class="print-input font-mono text-xs font-semibold text-slate-900 bg-white border border-slate-300 rounded px-2.5 py-1 focus:border-slate-900 focus:outline-none"
                                        title="PDS Date Accomplished (Page 4) — used as the exact cut-off date for PRESENT work experience"
                                    >
                                    <span
                                        class="no-print text-[10px] font-medium px-2 py-0.5 rounded border whitespace-nowrap"
                                        :class="dateAccomplished ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        x-text="dateAccomplished ? 'PRESENT cut-off: ' + formatIsoToDMY(dateAccomplished) : 'Set manually if unreadable (defaults to today)'"
                                    ></span>
                                    <button
                                        x-show="dateAccomplished"
                                        x-cloak
                                        type="button"
                                        @click="dateAccomplished = ''; recomputeAllPresentWorkRows()"
                                        class="no-print text-[10px] text-slate-400 hover:text-slate-700 underline"
                                        title="Reset to today's date"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- =================================================== -->
                        <!-- TABLE I: RELEVANT TRAINING (DROP ZONE)              -->
                        <!-- =================================================== -->
                        <div
                            x-ref="trainingDropZone"
                            :class="dragOverTarget === 'training'
                                ? 'border-2 border-dashed border-blue-600 bg-blue-50/70 rounded-lg p-3 -m-3 shadow-sm transition-all'
                                : (pointerDrag.active && pointerDrag.type === 'training'
                                    ? 'border-2 border-dashed border-blue-300 bg-blue-50/25 rounded-lg p-3 -m-3 transition-all'
                                    : 'border-2 border-transparent transition-all')"
                        >
                            <div class="flex items-baseline justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">
                                        Relevant Training
                                    </h3>
                                    <span
                                        x-show="pointerDrag.active && pointerDrag.type === 'training'"
                                        x-cloak
                                        :class="dragOverTarget === 'training' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-100 text-blue-700 border border-blue-200'"
                                        class="no-print px-2 py-0.5 text-[10px] font-semibold rounded transition-all"
                                    >
                                        <span x-text="dragOverTarget === 'training' ? '+ Release to add training' : 'Drop here to include'"></span>
                                    </span>
                                </div>
                                <span class="no-print text-[11px] font-mono text-slate-500 tabular-nums">
                                    <span x-text="selectedTrainings.length"></span> item(s) included
                                </span>
                            </div>

                            <table class="w-full table-fixed border-collapse border border-slate-400 text-xs print-table">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900">
                                        <th class="border border-slate-400 px-1 py-1.5 text-center font-semibold w-[4%]">#</th>
                                        <th class="border border-slate-400 px-2.5 py-1.5 text-left font-semibold w-[30%]">Title of Learning &amp; Development / Training</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-left font-semibold w-[18%]">Conducted / Sponsored By</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold w-[21%]">Inclusive Dates (From – To)</th>
                                        <th class="border border-slate-400 px-1.5 py-1.5 text-center font-semibold w-[11%]">Type</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-right font-semibold w-[8%]">Hours</th>
                                        <th class="no-print border border-slate-400 px-1 py-1.5 text-center font-semibold w-[8%]">Copy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="selectedTrainings.length === 0">
                                        <tr>
                                            <td colspan="7" class="border border-slate-400 px-3 py-5 text-center text-slate-400">
                                                <span x-text="pointerDrag.active && pointerDrag.type === 'training' ? 'Release mouse to add this training here.' : 'No relevant training items selected. Check or drag items from the left.'"></span>
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="(item, idx) in selectedTrainings" :key="item.id">
                                        <tr :class="justDroppedId === item.id ? 'bg-blue-100/80 transition-colors duration-500' : 'transition-colors duration-500'">
                                            <td class="border border-slate-400 px-1 py-1.5 text-center font-mono text-[11px] text-slate-500 tabular-nums" x-text="idx + 1"></td>
                                            <td class="border border-slate-400 px-2.5 py-1.5 font-medium text-slate-900 break-words" x-text="item.training_title || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-slate-700 break-words" x-text="item.conducted_by || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center font-mono text-[11px] text-slate-700 tabular-nums break-words">
                                                <span x-text="formatDateRange(item.date_from, item.date_to)"></span>
                                            </td>
                                            <td class="border border-slate-400 px-1.5 py-1.5 text-center text-[11px] text-slate-700 break-words" x-text="item.type_of_ld || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-right font-mono font-semibold text-slate-900 tabular-nums" x-text="formatHours(item.hours)"></td>
                                            <td class="no-print border border-slate-400 px-1 py-1 text-center align-middle">
                                                <button
                                                    type="button"
                                                    @click="copyTitleToClipboard(item.training_title, item.id)"
                                                    :disabled="!item.training_title"
                                                    :class="copiedRowId === item.id
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                                        : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-100 hover:text-slate-900'"
                                                    class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-semibold border rounded transition disabled:opacity-40 whitespace-nowrap"
                                                    title="Copy training title to clipboard"
                                                >
                                                    <span x-text="copiedRowId === item.id ? 'Copied ✓' : 'Copy'"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-100 font-bold text-slate-900">
                                        <td colspan="5" class="border border-slate-400 px-2.5 py-2 text-right text-xs">
                                            Total no. of hours
                                        </td>
                                        <td class="border border-slate-400 px-2 py-2 text-right font-mono text-xs tabular-nums">
                                            <span x-text="formattedTotalTrainingHours"></span>
                                        </td>
                                        <td class="no-print border border-slate-400 px-1 py-2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- =================================================== -->
                        <!-- TABLE II: RELEVANT WORK EXPERIENCE (DROP ZONE)      -->
                        <!-- =================================================== -->
                        <div
                            x-ref="workDropZone"
                            :class="dragOverTarget === 'work'
                                ? 'border-2 border-dashed border-blue-600 bg-blue-50/70 rounded-lg p-3 -m-3 shadow-sm transition-all'
                                : (pointerDrag.active && pointerDrag.type === 'work'
                                    ? 'border-2 border-dashed border-blue-300 bg-blue-50/25 rounded-lg p-3 -m-3 transition-all'
                                    : 'border-2 border-transparent transition-all')"
                        >
                            <div class="flex items-baseline justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">
                                        Relevant Work Experience
                                    </h3>
                                    <span
                                        x-show="pointerDrag.active && pointerDrag.type === 'work'"
                                        x-cloak
                                        :class="dragOverTarget === 'work' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-100 text-blue-700 border border-blue-200'"
                                        class="no-print px-2 py-0.5 text-[10px] font-semibold rounded transition-all"
                                    >
                                        <span x-text="dragOverTarget === 'work' ? '+ Release to add experience' : 'Drop here to include'"></span>
                                    </span>
                                </div>
                                <span class="no-print text-[11px] font-mono text-slate-500 tabular-nums">
                                    <span x-text="selectedWorkExperiences.length"></span> position(s) included
                                </span>
                            </div>

                            <table class="w-full table-fixed border-collapse border border-slate-400 text-xs print-table">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900">
                                        <th class="border border-slate-400 px-1 py-1.5 text-center font-semibold w-[4%]">#</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-left font-semibold w-[22%]">Position Title</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-left font-semibold w-[20%]">Department / Agency / Company</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold w-[19%]">Inclusive Dates (From – To)</th>
                                        <th class="border border-slate-400 px-1.5 py-1.5 text-center font-semibold w-[13%]">Status / Gov't</th>
                                        <th class="border border-slate-400 px-1 py-1.5 text-center font-semibold w-[7%]">Years</th>
                                        <th class="border border-slate-400 px-1 py-1.5 text-center font-semibold w-[8%]">Months</th>
                                        <th class="border border-slate-400 px-1 py-1.5 text-center font-semibold w-[7%]">Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="selectedWorkExperiences.length === 0">
                                        <tr>
                                            <td colspan="8" class="border border-slate-400 px-3 py-5 text-center text-slate-400">
                                                <span x-text="pointerDrag.active && pointerDrag.type === 'work' ? 'Release mouse to add this position here.' : 'No relevant work experience items selected. Check or drag items from the left.'"></span>
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="(item, idx) in selectedWorkExperiences" :key="item.id">
                                        <tr :class="justDroppedId === item.id ? 'bg-blue-100/80 transition-colors duration-500' : 'transition-colors duration-500'">
                                            <td class="border border-slate-400 px-1 py-1.5 text-center font-mono text-[11px] text-slate-500 tabular-nums" x-text="idx + 1"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 font-medium text-slate-900 break-words" x-text="item.position_title || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-slate-700 break-words" x-text="item.department_agency_company || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center font-mono text-[11px] text-slate-700 tabular-nums break-words">
                                                <span x-text="formatDateRange(item.date_from, item.date_to)"></span>
                                            </td>
                                            <td class="border border-slate-400 px-1.5 py-1.5 text-center text-[11px] text-slate-700 break-words leading-snug">
                                                <span x-text="formatStatusGov(item.status_of_appointment, item.gov_service)"></span>
                                            </td>
                                            <td class="border border-slate-400 px-1 py-1.5 text-center font-mono font-semibold text-slate-900 tabular-nums" x-text="formatInt(item.duration_years)"></td>
                                            <td class="border border-slate-400 px-1 py-1.5 text-center font-mono font-semibold text-slate-900 tabular-nums" x-text="formatInt(item.duration_months)"></td>
                                            <td class="border border-slate-400 px-1 py-1.5 text-center font-mono font-semibold text-slate-900 tabular-nums" x-text="formatInt(item.duration_days)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-100 font-bold text-slate-900">
                                        <td colspan="5" class="border border-slate-400 px-2.5 py-2 text-right text-xs">
                                            Total Relevant Experience
                                        </td>
                                        <td class="border border-slate-400 px-1 py-2 text-center font-mono text-xs tabular-nums">
                                            <span x-text="totalWorkYearsInt"></span>
                                            <span class="block text-[9px] font-sans font-normal text-slate-500 leading-none mt-0.5" x-text="totalWorkYearsInt === 1 ? 'yr' : 'yrs'"></span>
                                        </td>
                                        <td class="border border-slate-400 px-1 py-2 text-center font-mono text-xs tabular-nums">
                                            <span x-text="totalWorkMonthsInt"></span>
                                            <span class="block text-[9px] font-sans font-normal text-slate-500 leading-none mt-0.5" x-text="totalWorkMonthsInt === 1 ? 'mo' : 'mos'"></span>
                                        </td>
                                        <td class="border border-slate-400 px-1 py-2 text-center font-mono text-xs tabular-nums">
                                            <span x-text="totalWorkDaysInt"></span>
                                            <span class="block text-[9px] font-sans font-normal text-slate-500 leading-none mt-0.5" x-text="totalWorkDaysInt === 1 ? 'day' : 'days'"></span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </section>
                </div>

            </div>
        </main>

        <!-- Subtle Institutional Data Privacy & Confidentiality Footer -->
        <footer class="no-print border-t border-slate-200 bg-white/80 py-2.5 px-4">
            <div class="max-w-[1760px] mx-auto flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>
                        <strong class="font-semibold text-slate-700">Confidential HR Workspace</strong> — Aligned with the Philippine Data Privacy Act of 2012 (RA 10173). Zero database storage; session-only memory.
                    </span>
                </div>
                <button
                    type="button"
                    @click="showPrivacyModal = true"
                    class="text-blue-700 hover:text-blue-900 font-medium underline underline-offset-2"
                >
                    Data Privacy &amp; Security Assurance
                </button>
            </div>
        </footer>

        <!-- =============================================================== -->
        <!-- DATA PRIVACY & CONFIDENTIALITY ASSURANCE MODAL (RA 10173)       -->
        <!-- =============================================================== -->
        <div
            x-show="showPrivacyModal"
            x-cloak
            @keydown.escape.window="showPrivacyModal = false"
            class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/55 backdrop-blur-xs p-4"
        >
            <div
                @click.outside="showPrivacyModal = false"
                class="bg-white border border-slate-300 rounded-lg shadow-xl w-full max-w-lg overflow-hidden"
            >
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Data Privacy &amp; Confidentiality Assurance</h3>
                            <p class="text-[11px] text-slate-500">Republic Act No. 10173 (Data Privacy Act of 2012)</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showPrivacyModal = false"
                        class="text-slate-400 hover:text-slate-700 text-sm font-semibold px-1.5"
                    >
                        ✕
                    </button>
                </div>

                <div class="p-5 space-y-3.5 text-xs text-slate-700 leading-relaxed max-h-[70vh] overflow-y-auto">
                    <div class="rounded border border-emerald-200 bg-emerald-50/70 p-3 text-emerald-900">
                        <strong class="font-semibold">Zero Permanent Storage:</strong> This system has <strong>no database</strong> and <strong>never saves</strong> uploaded PDS PDFs, images, or extracted records to any server disk or log file.
                    </div>

                    <ul class="space-y-2.5 list-none">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                            <div>
                                <strong class="text-slate-900">Instant In-Memory Auto-Purge:</strong> Uploaded files are processed strictly in volatile server memory and explicitly cleared (<code class="font-mono text-[11px] bg-slate-100 px-1 rounded">unset</code>) the moment extraction finishes, with <code class="font-mono text-[11px] bg-slate-100 px-1 rounded">Cache-Control: no-store</code> headers.
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                            <div>
                                <strong class="text-slate-900">Session-Only Browser Isolation:</strong> Displayed entries live only inside your active browser tab (<code class="font-mono text-[11px] bg-slate-100 px-1 rounded">sessionStorage</code>) and are permanently wiped when you close the tab, click <strong>Clear All</strong>, click <strong>Lock</strong>, or drop a new PDS.
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                            <div>
                                <strong class="text-slate-900">Strict Data Minimization:</strong> Only Work Experience (Section V), Trainings (Section VII), Applicant Name, and Date Accomplished are extracted. Sensitive PII (TIN, GSIS, Pag-IBIG, PhilHealth, PhilSys/PSN, home addresses, phone numbers, family background, and legal Q&amp;A) are excluded and never stored.
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                            <div>
                                <strong class="text-slate-900">Stateless AI &amp; 100% On-Device Option:</strong> AI extraction requests are stateless (no chat history or public sharing). For <strong>100% on-device</strong> parsing of digital PDFs with zero external transmission, switch the engine dropdown to <strong>Local OCR / PDF Only (No API)</strong>.
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                            <div>
                                <strong class="text-slate-900">Active Web Security Hardening:</strong> Protected by Passcode Brute-Force Lockout (5 attempts / 10 mins), Scan Rate Limiting (20 req/min), 30-minute Idle Auto-Lock &amp; Wipe, Magic-Byte File Signature Verification, Strict CSRF/Same-Origin checks, HTTPS HSTS, Content-Security-Policy (CSP), and Search Engine Blocking (<code class="font-mono text-[11px] bg-slate-100 px-1 rounded">noindex</code>).
                            </div>
                        </li>
                    </ul>

                    <p class="pt-2 border-t border-slate-200 text-[11px] text-slate-500">
                        Authorized HR / Selection Committee use only. Always click <strong>Clear All</strong> or <strong>Lock</strong> when stepping away from a shared workstation.
                    </p>
                </div>

                <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                    <button
                        type="button"
                        @click="showPrivacyModal = false"
                        class="px-4 py-1.5 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded transition"
                    >
                        Understood
                    </button>
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- UPLOADED PDS PDF / IMAGE VERIFICATION PREVIEW MODAL             -->
        <!-- =============================================================== -->
        <div
            x-show="showPreviewModal"
            x-cloak
            @keydown.escape.window="showPreviewModal = false"
            class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-6"
        >
            <div
                @click.outside="showPreviewModal = false"
                class="bg-white border border-slate-300 rounded-lg shadow-xl w-full max-w-6xl h-[90vh] flex flex-col overflow-hidden"
            >
                <!-- Modal Header -->
                <div class="px-4 py-3 bg-slate-100 border-b border-slate-300 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide bg-blue-100 text-blue-800 border border-blue-200 rounded">
                            Document Verification
                        </span>
                        <span class="font-mono text-xs font-semibold text-slate-900 truncate" x-text="uploadedFileName || 'Uploaded PDS'"></span>
                        <template x-if="applicantName">
                            <span class="text-xs text-slate-500 truncate">• <span class="font-semibold text-slate-700" x-text="applicantName"></span></span>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Toggle between Native PDF Frame and Rendered Pages when both exist -->
                        <template x-if="rawPdfObjectUrl && previewPageImages.length > 0">
                            <div class="inline-flex rounded border border-slate-300 bg-white overflow-hidden text-xs font-medium">
                                <button
                                    type="button"
                                    @click="previewViewMode = 'pdf'"
                                    :class="previewViewMode === 'pdf' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'"
                                    class="px-2.5 py-1 transition"
                                >
                                    PDF Viewer
                                </button>
                                <button
                                    type="button"
                                    @click="previewViewMode = 'pages'"
                                    :class="previewViewMode === 'pages' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'"
                                    class="px-2.5 py-1 border-l border-slate-300 transition"
                                >
                                    Page Images (<span x-text="previewPageImages.length"></span>)
                                </button>
                            </div>
                        </template>

                        <!-- Zoom Controls for Image / Page View -->
                        <template x-if="previewViewMode === 'pages' || !rawPdfObjectUrl">
                            <div class="inline-flex items-center rounded border border-slate-300 bg-white text-xs font-medium">
                                <button
                                    type="button"
                                    @click="previewZoom = Math.max(60, previewZoom - 20)"
                                    class="px-2 py-1 text-slate-700 hover:bg-slate-50"
                                    title="Zoom Out"
                                >
                                    −
                                </button>
                                <span class="px-2 py-1 font-mono text-[11px] tabular-nums border-x border-slate-200 text-slate-700" x-text="previewZoom + '%'"></span>
                                <button
                                    type="button"
                                    @click="previewZoom = Math.min(200, previewZoom + 20)"
                                    class="px-2 py-1 text-slate-700 hover:bg-slate-50"
                                    title="Zoom In"
                                >
                                    +
                                </button>
                            </div>
                        </template>

                        <!-- Open in New Tab Button -->
                        <template x-if="rawPdfObjectUrl">
                            <a
                                :href="rawPdfObjectUrl"
                                target="_blank"
                                rel="noopener"
                                class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50"
                            >
                                Open in New Tab ↗
                            </a>
                        </template>

                        <button
                            type="button"
                            @click="showPreviewModal = false"
                            class="px-3 py-1 text-xs font-semibold text-white bg-slate-900 rounded hover:bg-slate-800 transition"
                        >
                            Close ✕
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 bg-slate-800 overflow-auto flex flex-col items-center">
                    <!-- 1. Native Interactive PDF Embed -->
                    <template x-if="rawPdfObjectUrl && previewViewMode === 'pdf'">
                        <iframe
                            :src="rawPdfObjectUrl"
                            class="w-full h-full border-0 bg-slate-800"
                            title="Uploaded PDS PDF Verification Preview"
                        ></iframe>
                    </template>

                    <!-- 2. Rendered Pages / Uploaded Images Gallery -->
                    <template x-if="(!rawPdfObjectUrl || previewViewMode === 'pages') && previewPageImages.length > 0">
                        <div class="w-full p-4 sm:p-6 space-y-6 flex flex-col items-center">
                            <template x-for="(imgSrc, idx) in previewPageImages" :key="idx">
                                <div
                                    :style="'width: ' + previewZoom + '%; max-width: 1400px;'"
                                    class="bg-white rounded shadow-lg overflow-hidden border border-slate-700 transition-all"
                                >
                                    <div class="px-3 py-1.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between text-[11px] font-mono text-slate-600">
                                        <span x-text="'Page / Image ' + (idx + 1) + ' of ' + previewPageImages.length"></span>
                                        <span x-text="uploadedFileName"></span>
                                    </div>
                                    <img
                                        :src="imgSrc"
                                        :alt="'Uploaded PDS Page ' + (idx + 1)"
                                        class="w-full h-auto block"
                                    >
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- IN-APP CONFIRMATION MODAL (FOR CLEAR ALL & LOCK WORKSPACE)      -->
        <!-- =============================================================== -->
        <div
            x-show="confirmDialog.open"
            x-cloak
            @keydown.escape.window="confirmDialog.open = false"
            class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4"
        >
            <div
                @click.outside="confirmDialog.open = false"
                class="bg-white border border-slate-300 rounded-lg shadow-xl w-full max-w-sm overflow-hidden"
            >
                <div class="p-5">
                    <h3 class="text-sm font-bold text-slate-900" x-text="confirmDialog.title"></h3>
                    <p class="mt-1.5 text-xs text-slate-600 leading-relaxed" x-text="confirmDialog.message"></p>
                </div>
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="confirmDialog.open = false"
                        class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-100 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="executeConfirmAction()"
                        :class="confirmDialog.danger ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-slate-900 hover:bg-slate-800 text-white'"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded transition"
                        x-text="confirmDialog.confirmText"
                    ></button>
                </div>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- CRISP ENTERPRISE FLOATING DRAG CARD (ZERO-BLUR POINTER DRAG)    -->
        <!-- =============================================================== -->
        <div
            x-show="pointerDrag.active"
            x-cloak
            :style="`transform: translate3d(${pointerDrag.x + 14}px, ${pointerDrag.y - 18}px, 0);`"
            :class="dragOverTarget === pointerDrag.type
                ? 'border-blue-600 ring-2 ring-blue-500/20'
                : 'border-slate-300'"
            class="no-print fixed top-0 left-0 z-[9999] pointer-events-none bg-white border rounded-md shadow-xl px-3 py-2 w-[300px] max-w-[85vw] transition-colors"
        >
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-1.5 min-w-0">
                    <span class="text-blue-600 text-xs leading-none mt-0.5 font-mono shrink-0">⋮⋮</span>
                    <span class="text-xs font-semibold text-slate-900 leading-snug line-clamp-2 break-words" x-text="pointerDrag.title"></span>
                </div>
                <span
                    class="shrink-0 px-1.5 py-0.5 rounded bg-blue-50 border border-blue-200 text-blue-700 font-mono text-[10px] font-bold tabular-nums"
                    x-text="pointerDrag.badge"
                ></span>
            </div>
            <div class="mt-1 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                <span class="truncate" x-text="pointerDrag.meta"></span>
                <span
                    x-show="dragOverTarget === pointerDrag.type"
                    class="shrink-0 font-semibold text-blue-600"
                >
                    + Drop to add
                </span>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- ALPINE.JS STATE, MULTI-PAGE PDF/OCR ENGINE & GEMINI TURBO SCANNER       -->
    <!-- ======================================================================= -->
    <script>
        function pdsAutoComputeApp(config = {}) {
            const STORAGE_KEY = 'pds_autocompute_session_v1';

            return {
                passcodeEnabled: Boolean(config.passcodeEnabled),
                activeLeftTab: 'trainings', // 'trainings' | 'work'
                applicantName: '',
                targetPosition: '',
                uploadedFileName: '',
                dateAccomplished: '', // ISO YYYY-MM-DD from PDS Page 4 ("Date Accomplished") or manual input
                showPreviewModal: false,
                showPrivacyModal: false,
                previewViewMode: 'pdf', // 'pdf' | 'pages'
                previewZoom: 100,
                rawPdfObjectUrl: '',
                previewPageImages: [],
                convertedRawText: '',
                showConvertedText: false,
                confirmDialog: {
                    open: false,
                    title: '',
                    message: '',
                    confirmText: 'Confirm',
                    danger: true,
                    action: null
                },
                trainings: [],
                workExperiences: [],
                trainingSearch: '',
                workSearch: '',
                scanEngine: 'hybrid', // 'hybrid' | 'local' | 'gemini'
                appendOnScan: false,
                copiedRowId: null,
                copiedTimerId: null,
                pointerDrag: {
                    active: false,
                    type: null,
                    id: null,
                    title: '',
                    meta: '',
                    badge: '',
                    x: 0,
                    y: 0
                },
                suppressNextClick: false,
                dragOverTarget: null, // 'training' | 'work' | null
                justDroppedId: null,
                justDroppedTimerId: null,
                isDragging: false,
                isScanning: false,
                scanningFileName: '',
                scanStageLabel: '',
                scanSubStatus: '',
                scanElapsed: 0,
                scanTimerId: null,
                abortController: null,
                scanCancelled: false,
                scanNotice: {
                    type: 'info',
                    message: ''
                },

                init() {
                    this.restoreFromSessionStorage();

                    this.$watch('trainings', () => {
                        this.saveToSessionStorage();
                    }, { deep: true });
                    this.$watch('workExperiences', () => {
                        this.saveToSessionStorage();
                    }, { deep: true });
                    this.$watch('applicantName', () => this.saveToSessionStorage());
                    this.$watch('targetPosition', () => this.saveToSessionStorage());
                    this.$watch('uploadedFileName', () => this.saveToSessionStorage());
                    this.$watch('dateAccomplished', () => this.saveToSessionStorage());
                    this.$watch('scanEngine', () => this.saveToSessionStorage());

                    this.refreshIcons();

                    // Deter casual View Source / DevTools shortcuts (keeps inputs & Copy button working)
                    document.addEventListener('contextmenu', (e) => {
                        if (!e.target.closest('input, textarea')) {
                            e.preventDefault();
                        }
                    });
                    document.addEventListener('keydown', (e) => {
                        const k = (e.key || '').toUpperCase();
                        if (
                            k === 'F12' ||
                            (e.ctrlKey && k === 'U') ||
                            (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(k))
                        ) {
                            e.preventDefault();
                        }
                    });

                    // Automatic 30-minute inactivity auto-lock & memory wipe for shared HR workstations
                    if (this.passcodeEnabled) {
                        const IDLE_LIMIT_MS = 30 * 60 * 1000;
                        let idleTimer = null;
                        const resetIdleTimer = () => {
                            if (idleTimer) clearTimeout(idleTimer);
                            idleTimer = setTimeout(() => {
                                this.performClearAll();
                                this.performLockWorkspace();
                            }, IDLE_LIMIT_MS);
                        };
                        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
                            window.addEventListener(evt, resetIdleTimer, { passive: true });
                        });
                        resetIdleTimer();
                    }
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                },

                matchesTrainingFilter(item) {
                    const q = this.trainingSearch.trim().toLowerCase();
                    if (!q) return true;
                    return (
                        String(item.training_title || '').toLowerCase().includes(q) ||
                        String(item.conducted_by || '').toLowerCase().includes(q) ||
                        String(item.type_of_ld || '').toLowerCase().includes(q) ||
                        String(item.date_from || '').toLowerCase().includes(q)
                    );
                },

                matchesWorkFilter(item) {
                    const q = this.workSearch.trim().toLowerCase();
                    if (!q) return true;
                    return (
                        String(item.position_title || '').toLowerCase().includes(q) ||
                        String(item.department_agency_company || '').toLowerCase().includes(q) ||
                        String(item.status_of_appointment || '').toLowerCase().includes(q) ||
                        String(item.date_from || '').toLowerCase().includes(q)
                    );
                },

                get todayDisplay() {
                    const now = new Date();
                    const dd = String(now.getDate()).padStart(2, '0');
                    const mm = String(now.getMonth() + 1).padStart(2, '0');
                    const yyyy = now.getFullYear();
                    return `${dd}/${mm}/${yyyy}`;
                },

                get hasFilePreview() {
                    return Boolean(this.rawPdfObjectUrl) || this.previewPageImages.length > 0;
                },

                openFilePreview() {
                    if (!this.hasFilePreview) return;
                    this.previewViewMode = this.rawPdfObjectUrl ? 'pdf' : 'pages';
                    this.showPreviewModal = true;
                },

                async copyTitleToClipboard(text, rowId) {
                    const value = String(text || '').trim();
                    if (!value) return;

                    let copied = false;
                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(value);
                            copied = true;
                        }
                    } catch (e) {
                        copied = false;
                    }

                    if (!copied) {
                        try {
                            const ta = document.createElement('textarea');
                            ta.value = value;
                            ta.setAttribute('readonly', '');
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                        } catch (e) {
                            return;
                        }
                    }

                    this.copiedRowId = rowId;
                    if (this.copiedTimerId) clearTimeout(this.copiedTimerId);
                    this.copiedTimerId = setTimeout(() => {
                        if (this.copiedRowId === rowId) {
                            this.copiedRowId = null;
                        }
                    }, 1500);
                },

                onRowClick(item) {
                    if (this.suppressNextClick) {
                        this.suppressNextClick = false;
                        return;
                    }
                    item.included = !item.included;
                },

                onRowPointerDown(event, type, item) {
                    // Only left mouse button / primary touch, and ignore interactive controls inside the row
                    if (event.button !== 0 && event.pointerType === 'mouse') return;
                    if (event.target && event.target.closest('input, button, select, textarea, a, label')) return;

                    const startX = event.clientX;
                    const startY = event.clientY;
                    let dragging = false;

                    const title = type === 'training'
                        ? (item.training_title || 'Untitled Training')
                        : (item.position_title || 'Untitled Position');
                    const badge = type === 'training'
                        ? `${this.formatHours(item.hours)} hrs`
                        : `${this.formatInt(item.duration_years)}y ${this.formatInt(item.duration_months)}m ${this.formatInt(item.duration_days)}d`;
                    const meta = type === 'training'
                        ? [item.conducted_by, this.formatDateRange(item.date_from, item.date_to)].filter(Boolean).join(' • ')
                        : [item.department_agency_company, this.formatDateRange(item.date_from, item.date_to)].filter(Boolean).join(' • ');

                    const hitTestDropZone = (cx, cy) => {
                        const targetEl = type === 'training' ? this.$refs.trainingDropZone : this.$refs.workDropZone;
                        if (!targetEl) return false;
                        const rect = targetEl.getBoundingClientRect();
                        return cx >= rect.left - 16 && cx <= rect.right + 16 && cy >= rect.top - 16 && cy <= rect.bottom + 16;
                    };

                    const onMove = (moveEvent) => {
                        const dx = moveEvent.clientX - startX;
                        const dy = moveEvent.clientY - startY;

                        if (!dragging && Math.hypot(dx, dy) >= 6) {
                            dragging = true;
                            this.suppressNextClick = true;
                            document.body.style.cursor = 'grabbing';
                            document.body.style.userSelect = 'none';
                            this.pointerDrag = {
                                active: true,
                                type,
                                id: item.id,
                                title,
                                meta,
                                badge,
                                x: moveEvent.clientX,
                                y: moveEvent.clientY
                            };
                            const targetEl = type === 'training' ? this.$refs.trainingDropZone : this.$refs.workDropZone;
                            if (targetEl && typeof targetEl.scrollIntoView === 'function') {
                                targetEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        }

                        if (dragging) {
                            this.pointerDrag.x = moveEvent.clientX;
                            this.pointerDrag.y = moveEvent.clientY;
                            this.dragOverTarget = hitTestDropZone(moveEvent.clientX, moveEvent.clientY) ? type : null;
                        }
                    };

                    const onUp = (upEvent) => {
                        window.removeEventListener('pointermove', onMove);
                        window.removeEventListener('pointerup', onUp);
                        window.removeEventListener('pointercancel', onUp);

                        if (dragging) {
                            document.body.style.cursor = '';
                            document.body.style.userSelect = '';

                            const isOverTarget = hitTestDropZone(upEvent.clientX, upEvent.clientY);
                            if (isOverTarget) {
                                item.included = true;
                                this.justDroppedId = item.id;
                                if (this.justDroppedTimerId) clearTimeout(this.justDroppedTimerId);
                                this.justDroppedTimerId = setTimeout(() => {
                                    if (this.justDroppedId === item.id) {
                                        this.justDroppedId = null;
                                    }
                                }, 1200);
                            }

                            this.pointerDrag.active = false;
                            this.pointerDrag.id = null;
                            this.pointerDrag.type = null;
                            this.dragOverTarget = null;

                            // Clear suppress flag after the browser's synthetic click tick
                            setTimeout(() => {
                                this.suppressNextClick = false;
                            }, 50);
                        }
                    };

                    window.addEventListener('pointermove', onMove);
                    window.addEventListener('pointerup', onUp);
                    window.addEventListener('pointercancel', onUp);
                },

                blobToDataUrl(blob) {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(typeof reader.result === 'string' ? reader.result : '');
                        reader.onerror = () => resolve('');
                        reader.readAsDataURL(blob);
                    });
                },

                get hasAnyData() {
                    return this.trainings.length > 0 ||
                        this.workExperiences.length > 0 ||
                        this.applicantName.trim() !== '' ||
                        this.uploadedFileName.trim() !== '' ||
                        this.targetPosition.trim() !== '';
                },

                get selectedTrainings() {
                    return this.trainings.filter(item => Boolean(item.included));
                },

                get selectedWorkExperiences() {
                    return this.workExperiences.filter(item => Boolean(item.included));
                },

                get allTrainingsSelected() {
                    return this.trainings.length > 0 && this.trainings.every(item => Boolean(item.included));
                },

                get allWorkSelected() {
                    return this.workExperiences.length > 0 && this.workExperiences.every(item => Boolean(item.included));
                },

                get totalTrainingHours() {
                    return this.selectedTrainings.reduce((sum, item) => {
                        const val = parseFloat(item.hours);
                        return sum + (Number.isFinite(val) && val > 0 ? val : 0);
                    }, 0);
                },

                get formattedTotalTrainingHours() {
                    const total = this.totalTrainingHours;
                    return Number.isInteger(total) ? String(total) : total.toFixed(1);
                },

                get totalWorkDurationTotals() {
                    let rawYears = 0;
                    let rawMonths = 0;
                    let rawDays = 0;
                    this.selectedWorkExperiences.forEach(item => {
                        const y = parseInt(item.duration_years, 10);
                        const m = parseInt(item.duration_months, 10);
                        const d = parseInt(item.duration_days, 10);
                        rawYears += (Number.isFinite(y) && y > 0) ? y : 0;
                        rawMonths += (Number.isFinite(m) && m > 0) ? m : 0;
                        rawDays += (Number.isFinite(d) && d > 0) ? d : 0;
                    });
                    const extraMonths = Math.floor(rawDays / 30);
                    const days = rawDays % 30;
                    const combinedMonths = rawMonths + extraMonths;
                    const extraYears = Math.floor(combinedMonths / 12);
                    const months = combinedMonths % 12;
                    const years = rawYears + extraYears;
                    return { years, months, days };
                },

                get totalWorkYearsInt() {
                    return this.totalWorkDurationTotals.years;
                },

                get totalWorkMonthsInt() {
                    return this.totalWorkDurationTotals.months;
                },

                get totalWorkDaysInt() {
                    return this.totalWorkDurationTotals.days;
                },

                get formattedTotalWorkDurationShort() {
                    const t = this.totalWorkDurationTotals;
                    return `${t.years}y ${t.months}m ${t.days}d`;
                },

                get totalWorkYears() {
                    const t = this.totalWorkDurationTotals;
                    return Number(((t.years * 365.25 + t.months * 30.4375 + t.days) / 365.25).toFixed(1));
                },

                get formattedTotalWorkYears() {
                    const t = this.totalWorkDurationTotals;
                    return `${t.years} yr${t.years === 1 ? '' : 's'}, ${t.months} mo${t.months === 1 ? '' : 's'}, ${t.days} day${t.days === 1 ? '' : 's'}`;
                },

                get presentReferenceDate() {
                    if (this.dateAccomplished) {
                        const m = String(this.dateAccomplished).trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
                        if (m) {
                            const dt = new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
                            dt.setHours(0, 0, 0, 0);
                            if (!Number.isNaN(dt.getTime())) {
                                return dt;
                            }
                        }
                    }
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    return today;
                },

                formatIsoToDMY(iso) {
                    const m = String(iso || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (!m) return iso || '';
                    return `${m[3]}/${m[2]}/${m[1]}`;
                },

                recomputeAllPresentWorkRows() {
                    this.workExperiences.forEach(item => {
                        if (item.date_from) {
                            this.recomputeRowYears(item);
                        }
                    });
                },

                generateId(prefix = 'row') {
                    return `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
                },

                toggleAllTrainings(checked) {
                    this.trainings.forEach(item => {
                        item.included = Boolean(checked);
                    });
                },

                toggleAllWork(checked) {
                    this.workExperiences.forEach(item => {
                        item.included = Boolean(checked);
                    });
                },

                addTrainingRow(openEditor = true, prefill = {}) {
                    this.activeLeftTab = 'trainings';
                    this.trainings.push({
                        id: this.generateId('tr'),
                        training_title: prefill.training_title ?? '',
                        date_from: prefill.date_from ?? '',
                        date_to: prefill.date_to ?? '',
                        hours: prefill.hours ?? 4,
                        type_of_ld: prefill.type_of_ld ?? 'TECHNICAL',
                        conducted_by: prefill.conducted_by ?? '',
                        included: prefill.included ?? true,
                        editing: Boolean(openEditor)
                    });
                },

                removeTrainingRow(index) {
                    this.trainings.splice(index, 1);
                },

                addWorkRow(openEditor = true, prefill = {}) {
                    this.activeLeftTab = 'work';
                    const dur = prefill.date_from
                        ? this.computeDurationBetween(prefill.date_from, prefill.date_to ?? 'PRESENT', prefill.computed_years ?? 0)
                        : this.decimalYearsToYM(prefill.computed_years ?? 0);
                    const row = {
                        id: this.generateId('wk'),
                        position_title: prefill.position_title ?? '',
                        department_agency_company: prefill.department_agency_company ?? '',
                        date_from: prefill.date_from ?? '',
                        date_to: prefill.date_to ?? 'PRESENT',
                        status_of_appointment: prefill.status_of_appointment ?? 'COSW',
                        gov_service: prefill.gov_service ?? 'N',
                        duration_years: prefill.duration_years !== undefined ? Math.max(0, parseInt(prefill.duration_years, 10) || 0) : dur.years,
                        duration_months: prefill.duration_months !== undefined ? Math.max(0, parseInt(prefill.duration_months, 10) || 0) : dur.months,
                        duration_days: prefill.duration_days !== undefined ? Math.max(0, parseInt(prefill.duration_days, 10) || 0) : dur.days,
                        computed_years: Number(((dur.years * 365.25 + dur.months * 30.4375 + dur.days) / 365.25).toFixed(1)),
                        included: prefill.included ?? true,
                        editing: Boolean(openEditor)
                    };
                    this.normalizeRowDuration(row);
                    this.workExperiences.push(row);
                },

                removeWorkRow(index) {
                    this.workExperiences.splice(index, 1);
                },

                normalizeRowDuration(item) {
                    let y = parseInt(item.duration_years, 10);
                    let m = parseInt(item.duration_months, 10);
                    let d = parseInt(item.duration_days, 10);
                    if (!Number.isFinite(y) || y < 0) y = 0;
                    if (!Number.isFinite(m) || m < 0) m = 0;
                    if (!Number.isFinite(d) || d < 0) d = 0;
                    if (d >= 30) {
                        m += Math.floor(d / 30);
                        d = d % 30;
                    }
                    if (m >= 12) {
                        y += Math.floor(m / 12);
                        m = m % 12;
                    }
                    item.duration_years = y;
                    item.duration_months = m;
                    item.duration_days = d;
                    item.computed_years = Number(((y * 365.25 + m * 30.4375 + d) / 365.25).toFixed(1));
                },

                decimalYearsToYM(decimalYears) {
                    const num = parseFloat(decimalYears);
                    if (!Number.isFinite(num) || num <= 0) {
                        return { years: 0, months: 0, days: 0 };
                    }
                    const totalDays = Math.round(num * 365.25);
                    const years = Math.floor(totalDays / 365);
                    const remDays = totalDays % 365;
                    const months = Math.floor(remDays / 30);
                    const days = remDays % 30;
                    return { years, months, days };
                },

                recomputeRowYears(item) {
                    const dur = this.computeDurationBetween(item.date_from, item.date_to, item.computed_years);
                    item.duration_years = dur.years;
                    item.duration_months = dur.months;
                    item.duration_days = dur.days;
                    item.computed_years = Number(((dur.years * 365.25 + dur.months * 30.4375 + dur.days) / 365.25).toFixed(1));
                },

                /**
                 * Exact Inclusive Calendar Duration (Years, Months, Days):
                 * - Uses PDS "Date Accomplished" (if set) as the exact cut-off date for "PRESENT".
                 * - Adds +1 day to end date so inclusive spans are 100% calendar-exact:
                 *   e.g. 01/01/2025 to 05/01/2026 = 1 yr, 0 mos, 5 days;
                 *        01/01/2024 to 31/12/2024 = 1 yr, 0 mos, 0 days;
                 *        01/04/2021 to 30/06/2021 = 0 yrs, 3 mos, 0 days.
                 */
                computeDurationBetween(dateFromStr, dateToStr, fallbackDecimal = 0.0) {
                    const start = this.parseDMY(dateFromStr);
                    if (!start) {
                        return this.decimalYearsToYM(fallbackDecimal);
                    }

                    let end = null;
                    const cleanTo = String(dateToStr || '').trim().toUpperCase();
                    if (['PRESENT', 'TO DATE', 'CURRENT', 'NOW', 'ONGOING'].includes(cleanTo)) {
                        end = this.presentReferenceDate;
                    } else {
                        end = this.parseDMY(dateToStr);
                    }

                    if (!end || end < start) {
                        return { years: 0, months: 0, days: 0 };
                    }

                    const endInc = new Date(end.getFullYear(), end.getMonth(), end.getDate() + 1);
                    let years = endInc.getFullYear() - start.getFullYear();
                    let months = endInc.getMonth() - start.getMonth();
                    let days = endInc.getDate() - start.getDate();

                    if (days < 0) {
                        months -= 1;
                        const prevMonthDays = new Date(endInc.getFullYear(), endInc.getMonth(), 0).getDate();
                        days += prevMonthDays;
                    }
                    if (months < 0) {
                        years -= 1;
                        months += 12;
                    }

                    return {
                        years: Math.max(0, years),
                        months: Math.max(0, months),
                        days: Math.max(0, days)
                    };
                },

                computeYearsBetween(dateFromStr, dateToStr, fallback = 0.0) {
                    const dur = this.computeDurationBetween(dateFromStr, dateToStr, fallback);
                    return Number(((dur.years * 365.25 + dur.months * 30.4375 + dur.days) / 365.25).toFixed(1));
                },

                get isDocumentMdyOrder() {
                    let firstGt12 = 0;
                    let secondGt12 = 0;
                    const checkStr = (s) => {
                        const clean = String(s || '').trim().replace(/[-.]/g, '/');
                        const m = clean.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
                        if (!m) return;
                        const p1 = parseInt(m[1], 10);
                        const p2 = parseInt(m[2], 10);
                        if (p1 > 12 && p2 <= 12) firstGt12++;
                        else if (p2 > 12 && p1 <= 12) secondGt12++;
                    };
                    this.workExperiences.forEach(w => { checkStr(w.date_from); checkStr(w.date_to); });
                    this.trainings.forEach(t => { checkStr(t.date_from); checkStr(t.date_to); });
                    return secondGt12 > 0 && firstGt12 === 0;
                },

                parseDMY(str) {
                    if (!str) return null;
                    const clean = String(str).trim().replace(/[-.]/g, '/');
                    const parts = clean.split('/');
                    if (parts.length !== 3) return null;

                    let p0 = parseInt(parts[0], 10);
                    let p1 = parseInt(parts[1], 10);
                    let p2 = parseInt(parts[2], 10);

                    let day, month, year;

                    if (parts[0].length === 4) {
                        year = p0;
                        month = p1;
                        day = p2;
                    } else {
                        year = p2 < 100 ? (p2 > 50 ? 1900 + p2 : 2000 + p2) : p2;
                        if (p0 > 12 && p1 <= 12) {
                            day = p0;
                            month = p1;
                        } else if (p1 > 12 && p0 <= 12) {
                            month = p0;
                            day = p1;
                        } else if (this.isDocumentMdyOrder) {
                            month = p0;
                            day = p1;
                        } else {
                            day = p0;
                            month = p1;
                        }
                    }

                    if (!year || !month || !day || month < 1 || month > 12 || day < 1 || day > 31) {
                        return null;
                    }

                    const dt = new Date(year, month - 1, day);
                    if (dt.getFullYear() !== year || (dt.getMonth() + 1) !== month || dt.getDate() !== day) {
                        return null;
                    }
                    dt.setHours(0, 0, 0, 0);
                    return Number.isNaN(dt.getTime()) ? null : dt;
                },

                formatStatusGov(status, gov) {
                    const s = String(status || '—').trim().replace(/\s*\/\s*/g, ' / ');
                    const g = String(gov || 'N').trim().toUpperCase();
                    return `${s} (${g})`;
                },

                formatDateRange(from, to) {
                    const f = String(from || '').trim();
                    const t = String(to || '').trim();
                    if (!f && !t) return '—';
                    if (f && !t) return `${f} – ${f}`;
                    if (!f && t) return t;
                    return `${f} – ${t}`;
                },

                formatHours(val) {
                    const num = parseFloat(val);
                    if (!Number.isFinite(num) || num < 0) return '0';
                    return Number.isInteger(num) ? String(num) : num.toFixed(1);
                },

                formatYears(val) {
                    const num = parseFloat(val);
                    if (!Number.isFinite(num) || num < 0) return '0.0';
                    return num.toFixed(1);
                },

                formatInt(val) {
                    const num = parseInt(val, 10);
                    if (!Number.isFinite(num) || num < 0) return '0';
                    return String(num);
                },

                loadSamplePDS() {
                    this.trainings = [
                        {
                            id: this.generateId('tr'),
                            training_title: 'Data Privacy and Security Awareness Training Phase 1',
                            date_from: '24/09/2026',
                            date_to: '24/09/2026',
                            hours: 4,
                            type_of_ld: 'Technical',
                            conducted_by: 'Philippine Statistics Authority',
                            included: true,
                            editing: false
                        },
                        {
                            id: this.generateId('tr'),
                            training_title: 'Data Privacy and Security Awareness Training Phase 2',
                            date_from: '27/08/2026',
                            date_to: '27/08/2026',
                            hours: 4,
                            type_of_ld: 'Technical',
                            conducted_by: 'Philippine Statistics Authority',
                            included: true,
                            editing: false
                        }
                    ];

                    this.workExperiences = [
                        {
                            id: this.generateId('wk'),
                            position_title: 'ADMINISTRATIVE OFFICER II',
                            department_agency_company: 'PHILIPPINE STATISTICS AUTHORITY',
                            date_from: '01/07/2026',
                            date_to: 'PRESENT',
                            status_of_appointment: 'COSW',
                            gov_service: 'N',
                            computed_years: this.computeYearsBetween('01/07/2026', 'PRESENT', 0.4),
                            included: true,
                            editing: false
                        },
                        {
                            id: this.generateId('wk'),
                            position_title: 'STUDENT TRAINEE',
                            department_agency_company: 'PHILIPPINE STATISTICS AUTHORITY',
                            date_from: '28/08/2025',
                            date_to: '22/01/2026',
                            status_of_appointment: 'INTERNSHIP',
                            gov_service: 'N',
                            computed_years: this.computeYearsBetween('28/08/2025', '22/01/2026', 0.4),
                            included: true,
                            editing: false
                        },
                        {
                            id: this.generateId('wk'),
                            position_title: '(FREELANCE) WORDPRESS PAGE EDITOR',
                            department_agency_company: 'REINMANPRO INC - SOUTHERN CALIFORNIA',
                            date_from: '03/06/2025',
                            date_to: '15/11/2026',
                            status_of_appointment: 'PROJECT-BASED',
                            gov_service: 'N',
                            computed_years: this.computeYearsBetween('03/06/2025', '15/11/2026', 1.5),
                            included: true,
                            editing: false
                        }
                    ];

                    this.scanNotice = { type: 'info', message: '' };
                },

                reparseConvertedText() {
                    if (!this.convertedRawText.trim()) return;
                    const parsed = this.parseCSForm212TextLocally(this.convertedRawText);
                    const total = parsed.trainings.length + parsed.work_experience.length;
                    if (total > 0) {
                        if (parsed.applicant_name && !this.applicantName.trim()) {
                            this.applicantName = parsed.applicant_name;
                        }
                        this.applyExtractedRows(parsed.trainings, parsed.work_experience);
                        this.scanNotice = {
                            type: 'info',
                            message: `Parsed converted text: Extracted ${parsed.trainings.length} training(s) and ${parsed.work_experience.length} work experience item(s).`
                        };
                    } else {
                        this.scanNotice = {
                            type: 'error',
                            message: 'No standard CS Form 212 date rows matched in the edited text.'
                        };
                    }
                },

                clearAll() {
                    if (this.hasAnyData) {
                        this.confirmDialog = {
                            open: true,
                            title: 'Clear Current Workspace',
                            message: 'Are you sure you want to remove all loaded Trainings, Work Experience rows, and uploaded file preview from this screen?',
                            confirmText: 'Clear All',
                            danger: true,
                            action: () => this.performClearAll()
                        };
                        return;
                    }
                    this.performClearAll();
                },

                performClearAll() {
                    this.trainings = [];
                    this.workExperiences = [];
                    this.applicantName = '';
                    this.targetPosition = '';
                    this.uploadedFileName = '';
                    this.dateAccomplished = '';
                    if (this.rawPdfObjectUrl) {
                        URL.revokeObjectURL(this.rawPdfObjectUrl);
                    }
                    this.rawPdfObjectUrl = '';
                    this.previewPageImages = [];
                    this.convertedRawText = '';
                    this.showConvertedText = false;
                    this.showPreviewModal = false;
                    this.confirmDialog.open = false;
                    this.trainingSearch = '';
                    this.workSearch = '';
                    this.scanNotice = { type: 'info', message: '' };
                    sessionStorage.removeItem(STORAGE_KEY);
                },

                printEvaluationSheet() {
                    window.print();
                },

                lockWorkspace() {
                    if (this.hasAnyData) {
                        this.confirmDialog = {
                            open: true,
                            title: 'Lock Workspace',
                            message: 'Lock this session and clear all loaded PDS data from this browser tab?',
                            confirmText: 'Lock Session',
                            danger: false,
                            action: () => this.performLockWorkspace()
                        };
                        return;
                    }
                    this.performLockWorkspace();
                },

                async performLockWorkspace() {
                    this.confirmDialog.open = false;
                    sessionStorage.removeItem(STORAGE_KEY);
                    try {
                        await fetch('{{ route('pds.lock') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                    } finally {
                        window.location.reload();
                    }
                },

                executeConfirmAction() {
                    const fn = this.confirmDialog.action;
                    this.confirmDialog.open = false;
                    if (typeof fn === 'function') {
                        fn();
                    }
                },

                handleDrop(event) {
                    this.isDragging = false;
                    if (this.isScanning) return;
                    const files = event.dataTransfer?.files;
                    if (files && files.length > 0) {
                        this.validateAndUploadFiles(Array.from(files));
                    }
                },

                handleFileSelect(event) {
                    const files = event.target?.files;
                    if (files && files.length > 0) {
                        this.validateAndUploadFiles(Array.from(files));
                    }
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                },

                validateAndUploadFiles(fileList) {
                    const allowedTypes = [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ];
                    const allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
                    const maxBytes = 10 * 1024 * 1024;
                    const validFiles = [];

                    for (const file of fileList) {
                        const ext = (file.name.split('.').pop() || '').toLowerCase();
                        if (!allowedTypes.includes(file.type) && !allowedExts.includes(ext)) {
                            this.scanNotice = {
                                type: 'error',
                                message: `Unsupported file format (${file.name}). Upload PDF, JPG, PNG, or WEBP.`
                            };
                            return;
                        }
                        if (file.size > maxBytes) {
                            this.scanNotice = {
                                type: 'error',
                                message: `File ${file.name} exceeds 10 MB.`
                            };
                            return;
                        }
                        validFiles.push(file);
                    }

                    if (validFiles.length > 0) {
                        this.processDocuments(validFiles);
                    }
                },

                /**
                 * Multi-Page & Multi-File Extraction Pipeline:
                 * Inspects every page of uploaded PDFs and all uploaded images so that
                 * Work Experience (Page 2) and Trainings (Page 3) are both captured even
                 * when they reside on separate pages or mixed text/image pages.
                 */
                async processDocuments(files) {
                    // When starting a new PDS upload (Append mode OFF), immediately reset previous applicant's data
                    // so old rows, owner name, and previews never mix with the newly dropped PDS.
                    if (!this.appendOnScan) {
                        this.trainings = [];
                        this.workExperiences = [];
                        this.applicantName = '';
                        this.dateAccomplished = '';
                        this.convertedRawText = '';
                        this.showConvertedText = false;
                        this.previewPageImages = [];
                        this.trainingSearch = '';
                        this.workSearch = '';
                        this.showPreviewModal = false;
                        if (this.rawPdfObjectUrl) {
                            URL.revokeObjectURL(this.rawPdfObjectUrl);
                            this.rawPdfObjectUrl = '';
                        }
                    }

                    this.isScanning = true;
                    this.scanCancelled = false;
                    const fileNamesJoined = files.map(f => f.name).join(', ');
                    this.scanningFileName = files.length === 1 ? files[0].name : `${files.length} files`;
                    this.uploadedFileName = this.appendOnScan && this.uploadedFileName
                        ? `${this.uploadedFileName}, ${fileNamesJoined}`
                        : fileNamesJoined;
                    this.scanStageLabel = 'Reading';
                    this.scanSubStatus = 'Inspecting all pages...';
                    this.scanElapsed = 0;
                    this.scanNotice = { type: 'info', message: '' };

                    this.abortController = new AbortController();
                    const startedAt = performance.now();
                    this.scanTimerId = setInterval(() => {
                        this.scanElapsed = (performance.now() - startedAt) / 1000;
                    }, 100);

                    try {
                        let combinedTextBlocks = [];
                        let all2DItems = [];
                        let pageImageBlobs = [];
                        let pageCountScanned = 0;

                        if (this.rawPdfObjectUrl) {
                            URL.revokeObjectURL(this.rawPdfObjectUrl);
                            this.rawPdfObjectUrl = '';
                        }
                        const newPreviewImages = [];

                        for (const file of files) {
                            if (this.scanCancelled) return;
                            const ext = (file.name.split('.').pop() || '').toLowerCase();
                            const isPdf = ext === 'pdf' || file.type === 'application/pdf';

                            if (isPdf) {
                                if (!this.rawPdfObjectUrl) {
                                    this.rawPdfObjectUrl = URL.createObjectURL(file);
                                }
                            }

                            if (isPdf && window.pdfjsLib) {
                                this.scanStageLabel = 'Reading PDF';
                                const pdfResult = await this.inspectAllPdfPages(file);
                                pageCountScanned += pdfResult.numPages;

                                if (pdfResult.text.trim()) {
                                    combinedTextBlocks.push(pdfResult.text);
                                }
                                if (pdfResult.items2D.length > 0) {
                                    all2DItems.push(...pdfResult.items2D);
                                }
                                if (pdfResult.renderedPageBlobs.length > 0) {
                                    pageImageBlobs.push(...pdfResult.renderedPageBlobs);
                                    for (const b of pdfResult.renderedPageBlobs) {
                                        const dataUrl = await this.blobToDataUrl(b);
                                        if (dataUrl) newPreviewImages.push(dataUrl);
                                    }
                                }
                            } else if (!isPdf) {
                                pageCountScanned += 1;
                                this.scanStageLabel = 'Reading image';
                                if (this.scanEngine === 'local') {
                                    pageImageBlobs.push(file);
                                    const compressed = await this.compressImageForFastUpload(file);
                                    const dataUrl = await this.blobToDataUrl(compressed);
                                    if (dataUrl) newPreviewImages.push(dataUrl);
                                } else {
                                    const compressed = await this.compressImageForFastUpload(file);
                                    pageImageBlobs.push(compressed);
                                    const dataUrl = await this.blobToDataUrl(compressed);
                                    if (dataUrl) newPreviewImages.push(dataUrl);
                                }
                            }
                        }

                        if (newPreviewImages.length > 0) {
                            this.previewPageImages = newPreviewImages;
                            this.saveToSessionStorage();
                        }

                        if (this.scanCancelled) return;

                        const fullExtractedText = combinedTextBlocks.join('\n');

                        // 1. LOCAL OCR / PDF ONLY MODE (2-Stage: PDF/Image -> Converted Text -> Structured Tables)
                        if (this.scanEngine === 'local') {
                            let ocrText = '';
                            if (all2DItems.length < 30 && pageImageBlobs.length > 0) {
                                this.scanStageLabel = 'Stage 1: Image to Text';
                                const ocrResult = await this.runTesseractOcrOnBlobs(pageImageBlobs);
                                ocrText = ocrResult.text;
                                if (ocrResult.items2D.length > 0) {
                                    all2DItems.push(...ocrResult.items2D);
                                }
                            }
                            if (this.scanCancelled) return;

                            const combinedLocalText = [fullExtractedText, ocrText].filter(Boolean).join('\n');
                            this.convertedRawText = combinedLocalText;

                            this.scanStageLabel = 'Stage 2: Structuring Text';
                            const localParsed = this.parseCombined2DAndTextLocally(all2DItems, combinedLocalText);
                            const localCount = localParsed.trainings.length + localParsed.work_experience.length;

                            if (localCount > 0) {
                                if (localParsed.applicant_name && (!this.appendOnScan || !this.applicantName.trim())) {
                                    this.applicantName = localParsed.applicant_name;
                                }
                                const detectedLocalDateAcc = this.extractDateAccomplishedFromText(combinedLocalText);
                                if (detectedLocalDateAcc && (!this.appendOnScan || !this.dateAccomplished)) {
                                    this.dateAccomplished = detectedLocalDateAcc;
                                }
                                this.applyExtractedRows(localParsed.trainings, localParsed.work_experience);
                                this.scanNotice = {
                                    type: 'info',
                                    message: `PDF/OCR-to-Text Parser (${this.scanElapsed.toFixed(1)}s across ${pageCountScanned} page(s)): Extracted ${localParsed.trainings.length} training(s) and ${localParsed.work_experience.length} work experience item(s).`
                                };
                            } else {
                                this.scanNotice = {
                                    type: 'error',
                                    message: `Stage 1 converted document to text (${this.scanElapsed.toFixed(1)}s), but no standard date rows matched. Click "View Converted Text" to inspect or switch to Hybrid mode.`
                                };
                            }
                            return;
                        }

                        // 2. HYBRID OR GEMINI VISION TURBO MODE (With Automatic Seamless Fallback to Stage 1 Text + Stage 2 Local Structurer!)
                        this.convertedRawText = fullExtractedText;
                        const localPreParsed = this.parseCombined2DAndTextLocally(all2DItems, fullExtractedText);
                        this.scanStageLabel = 'AI Multi-Page Scan';
                        this.scanSubStatus = `Analyzing ${pageCountScanned} page(s) for Work Experience & Trainings...`;

                        const aiSuccess = await this.sendMultiPageToGemini({
                            extractedText: this.scanEngine === 'hybrid' ? fullExtractedText : '',
                            pageBlobs: pageImageBlobs,
                            fallbackFiles: pageImageBlobs.length === 0 ? files : [],
                            fallbackLocal: localPreParsed,
                            pageCount: pageCountScanned
                        });

                        // If Gemini hit a rate limit (429) or failed, AUTOMATICALLY run Stage 1 Image-to-Text + Stage 2 Local Text Structurer!
                        if (!aiSuccess && !this.scanCancelled) {
                            this.scanStageLabel = 'Auto-Fallback: OCR to Text';
                            this.scanSubStatus = 'Converting image/PDF to text and structuring rows...';

                            let fallbackParsed = localPreParsed;

                            if ((fallbackParsed.trainings.length + fallbackParsed.work_experience.length) === 0 && pageImageBlobs.length > 0) {
                                const ocrResult = await this.runTesseractOcrOnBlobs(pageImageBlobs);
                                if (this.scanCancelled) return;
                                const combinedFallbackText = [fullExtractedText, ocrResult.text].filter(Boolean).join('\n');
                                this.convertedRawText = combinedFallbackText;
                                fallbackParsed = this.parseCombined2DAndTextLocally([...all2DItems, ...ocrResult.items2D], combinedFallbackText);
                            }

                            const fallbackCount = fallbackParsed.trainings.length + fallbackParsed.work_experience.length;
                            if (fallbackCount > 0) {
                                if (fallbackParsed.applicant_name && (!this.appendOnScan || !this.applicantName.trim())) {
                                    this.applicantName = fallbackParsed.applicant_name;
                                }
                                const detectedFbDateAcc = this.extractDateAccomplishedFromText(this.convertedRawText || fullExtractedText);
                                if (detectedFbDateAcc && (!this.appendOnScan || !this.dateAccomplished)) {
                                    this.dateAccomplished = detectedFbDateAcc;
                                }
                                this.applyExtractedRows(fallbackParsed.trainings, fallbackParsed.work_experience);
                                this.scanNotice = {
                                    type: 'info',
                                    message: `Auto-switched to PDF/OCR-to-Text Parser (${this.scanElapsed.toFixed(1)}s): Extracted ${fallbackParsed.trainings.length} training(s) and ${fallbackParsed.work_experience.length} work experience item(s).`
                                };
                            } else {
                                this.scanNotice = {
                                    type: 'error',
                                    message: 'Could not detect standard CS Form 212 rows on this image. Click "View Converted Text" or add rows manually.'
                                };
                            }
                        }
                    } catch (err) {
                        if (!this.scanCancelled) {
                            this.scanNotice = {
                                type: 'error',
                                message: 'Could not complete scan. Try "Local OCR / PDF Only" or add rows manually.'
                            };
                        }
                    } finally {
                        clearInterval(this.scanTimerId);
                        this.scanTimerId = null;
                        this.isScanning = false;
                        this.abortController = null;
                    }
                },

                /**
                 * Multi-Page PDF Inspector:
                 * - Reads EVERY page of the PDF
                 * - Reconstructs table rows with a generous 7.5px Y-coordinate tolerance
                 * - Renders relevant pages (Page 2, Page 3, or any page that isn't purely Page 1/4 boilerplate,
                 *   PLUS any page with low selectable text / embedded images) into fast JPEG canvases so
                 *   trainings on Page 3 or image-based pages inside a PDF are NEVER missed!
                 */
                async inspectAllPdfPages(file) {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const numPages = pdf.numPages;

                    const allLines = [];
                    const items2D = [];
                    const renderedPageBlobs = [];

                    for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                        if (this.scanCancelled) break;
                        this.scanSubStatus = `Reading PDF page ${pageNum} of ${numPages}...`;

                        const page = await pdf.getPage(pageNum);
                        const baseVp = page.getViewport({ scale: 1.0 });
                        const pageWidth = baseVp.width || 612;
                        const textContent = await page.getTextContent();
                        const items = textContent.items || [];

                        let pageChars = 0;
                        const rowsByY = [];
                        const page2D = [];

                        for (const item of items) {
                            const str = String(item.str || '').trim();
                            if (!str) continue;
                            pageChars += str.length;
                            const x = item.transform ? item.transform[4] : 0;
                            const y = item.transform ? item.transform[5] : 0;
                            const w = item.width || (str.length * 4.5);

                            page2D.push({
                                str,
                                x,
                                y,
                                width: w,
                                pageWidth,
                                pageNum
                            });

                            let existingRow = rowsByY.find(r => Math.abs(r.y - y) <= 7.5);
                            if (!existingRow) {
                                existingRow = { y, cells: [] };
                                rowsByY.push(existingRow);
                            }
                            existingRow.cells.push({ x, str });
                        }

                        rowsByY.sort((a, b) => b.y - a.y);

                        const pageLines = rowsByY.map(row => {
                            row.cells.sort((a, b) => a.x - b.x);
                            let lineStr = '';
                            let lastX = null;
                            for (const cell of row.cells) {
                                if (lastX !== null) {
                                    const gap = cell.x - lastX;
                                    lineStr += gap > 12 ? '\t' : ' ';
                                }
                                lineStr += cell.str;
                                lastX = cell.x + (cell.str.length * 4.5);
                            }
                            return lineStr.trim();
                        }).filter(Boolean);

                        const pageText = pageLines.join('\n');
                        const upperPage = pageText.toUpperCase();

                        const hasWorkOrTrainingHeader =
                            upperPage.includes('WORK EXPERIENCE') ||
                            upperPage.includes('LEARNING AND DEVELOPMENT') ||
                            upperPage.includes('TRAINING PROGRAMS') ||
                            upperPage.includes('CONDUCTED/ SPONSORED') ||
                            upperPage.includes('NUMBER OF HOURS') ||
                            upperPage.includes('STATUS OF APPOINTMENT');

                        const isPurePage1 = !hasWorkOrTrainingHeader &&
                            upperPage.includes('PERSONAL INFORMATION') &&
                            upperPage.includes('FAMILY BACKGROUND') &&
                            !upperPage.includes('V. WORK EXPERIENCE');

                        const isPurePage4 = !hasWorkOrTrainingHeader &&
                            (upperPage.includes('CONSANGUINITY') || upperPage.includes('ADMINISTRATIVE OFFENSE') || upperPage.includes('GOVERNMENT ISSUED ID'));

                        const isCandidatePage = !isPurePage1 && !isPurePage4;

                        if (isCandidatePage && pageLines.length > 0) {
                            allLines.push(`=== PDF PAGE ${pageNum} ===`);
                            allLines.push(...pageLines);
                            items2D.push(...page2D);
                        } else if (isPurePage1 && pageLines.length > 0) {
                            allLines.push(`=== PDF PAGE 1 HEADER (NAME ONLY) ===`);
                            allLines.push(...pageLines.slice(0, 18));
                        } else if (isPurePage4 && pageLines.length > 0) {
                            allLines.push(`=== PDF PAGE ${pageNum} FOOTER (DATE ACCOMPLISHED) ===`);
                            allLines.push(...pageLines.slice(-28));
                        }

                        const isLastPage = pageNum === numPages;
                        const shouldRenderPageImage =
                            this.scanEngine === 'gemini'
                                ? (isCandidatePage || isLastPage)
                                : (pageChars < 180 || ((isCandidatePage || isLastPage) && renderedPageBlobs.length < 4));

                        if (shouldRenderPageImage && renderedPageBlobs.length < 5) {
                            this.scanSubStatus = `Rendering page ${pageNum} of ${numPages}...`;
                            const targetScale = Math.min(2.2, Math.max(1.4, 1500 / Math.max(baseVp.width, baseVp.height)));
                            const viewport = page.getViewport({ scale: targetScale });

                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(viewport.width);
                            canvas.height = Math.round(viewport.height);
                            const ctx = canvas.getContext('2d');
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            await page.render({ canvasContext: ctx, viewport }).promise;

                            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.86));
                            if (blob) {
                                renderedPageBlobs.push(blob);
                            }
                        }
                    }

                    return {
                        numPages,
                        text: allLines.join('\n'),
                        items2D,
                        renderedPageBlobs
                    };
                },

                /**
                 * Stage 1 Image-to-Text Converter (Zero Gemini API Quota Used):
                 * 1. Converts uploaded page images into Tab-Separated Table Text + 2D word coordinates
                 *    using Table OCR Engine 2 (`scan_mode=ocr_text_only`).
                 * 2. Falls back to in-browser Tesseract.js LSTM OCR if offline.
                 */
                async runTesseractOcrOnBlobs(blobs) {
                    const textSegments = [];
                    const items2D = [];

                    // Preprocess blobs to erase vertical table border lines touching characters while preserving anti-aliased text
                    const preparedPages = [];
                    for (let i = 0; i < blobs.length; i++) {
                        if (this.scanCancelled) break;
                        const enhanced = await this.preprocessBlobForOcr(blobs[i]);
                        preparedPages.push(enhanced);
                    }

                    // Try Stage 1 Table OCR-to-Text first (fast tab-separated table extraction, zero Gemini quota)
                    try {
                        this.scanSubStatus = `Converting ${preparedPages.length} page image(s) to table text...`;
                        const formData = new FormData();
                        formData.append('scan_mode', 'ocr_text_only');
                        preparedPages.forEach((p, idx) => {
                            formData.append('page_images[]', p.blob, `ocr_page_${idx + 1}.jpg`);
                        });

                        const res = await fetch('{{ route('pds.scan') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: formData,
                            signal: this.abortController?.signal
                        });

                        if (res.ok) {
                            const data = await res.json().catch(() => ({}));
                            if (data.ok && data.converted_text) {
                                const cleanText = String(data.converted_text)
                                    .replace(/\[Note:[^\]]*\]/gi, '')
                                    .trim();
                                const clean2D = (Array.isArray(data.items_2d) ? data.items_2d : [])
                                    .filter(it => !/^\[?Note:/i.test(String(it.str || '')));
                                return {
                                    text: cleanText,
                                    items2D: clean2D
                                };
                            }
                        }
                    } catch (e) {
                        // Fall through to offline in-browser Tesseract.js
                    }

                    if (!window.Tesseract) {
                        return { text: '', items2D: [] };
                    }

                    let worker = null;
                    try {
                        worker = await window.Tesseract.createWorker('eng', 1, {
                            logger: m => {
                                if (m.status === 'recognizing text' && typeof m.progress === 'number') {
                                    const pct = Math.round(m.progress * 100);
                                    this.scanSubStatus = `Offline Browser OCR: ${pct}% completed...`;
                                }
                            }
                        });
                        await worker.setParameters({
                            preserve_interword_spaces: '1'
                        });
                    } catch (workerErr) {
                        worker = null;
                    }

                    try {
                        for (let i = 0; i < preparedPages.length; i++) {
                            if (this.scanCancelled) break;
                            const enhanced = preparedPages[i];

                            const result = worker
                                ? await worker.recognize(enhanced.blob)
                                : await window.Tesseract.recognize(enhanced.blob, 'eng');

                            if (result?.data?.text) {
                                textSegments.push(`=== OCR PAGE ${i + 1} ===\n` + result.data.text);
                            }

                            const words = result?.data?.words || [];
                            const pageW = enhanced.width || 1600;
                            const pageH = enhanced.height || 1200;

                            for (const w of words) {
                                const txt = String(w.text || '').trim();
                                if (!txt || (w.confidence !== undefined && w.confidence < 20)) continue;
                                const x0 = w.bbox?.x0 ?? 0;
                                const y0 = w.bbox?.y0 ?? 0;
                                const x1 = w.bbox?.x1 ?? (x0 + txt.length * 8);
                                items2D.push({
                                    str: txt,
                                    x: x0,
                                    y: pageH - y0,
                                    width: Math.max(4, x1 - x0),
                                    pageWidth: pageW,
                                    pageNum: 100 + i
                                });
                            }
                        }
                    } finally {
                        if (worker) {
                            await worker.terminate().catch(() => {});
                        }
                    }

                    return {
                        text: textSegments.join('\n'),
                        items2D
                    };
                },

                /**
                 * Non-Destructive Vertical Table Border Eraser for CS Form No. 212 Images:
                 * Preserves 100% of original anti-aliased text pixels, and ONLY erases
                 * continuous vertical table column divider lines (spanning >= 18% of image height)
                 * so numbers/letters touching vertical table borders (e.g. "|16.0", "|24.0", "|CSC", "|PSA")
                 * are cleanly separated from the table grid before Stage 1 OCR-to-Text.
                 */
                async preprocessBlobForOcr(blob) {
                    return new Promise((resolve) => {
                        const img = new Image();
                        const url = URL.createObjectURL(blob);
                        img.onload = () => {
                            URL.revokeObjectURL(url);
                            let { width, height } = img;
                            if (width < 1500) {
                                const scale = Math.min(2.0, 1600 / width);
                                width = Math.round(width * scale);
                                height = Math.round(height * scale);
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.imageSmoothingEnabled = true;
                            ctx.imageSmoothingQuality = 'high';
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, width, height);
                            ctx.drawImage(img, 0, 0, width, height);

                            try {
                                const imgData = ctx.getImageData(0, 0, width, height);
                                const data = imgData.data;
                                const minVertLineLen = Math.max(75, Math.round(height * 0.18));

                                const getLum = (x, y) => {
                                    const idx = (y * width + x) * 4;
                                    return 0.299 * data[idx] + 0.587 * data[idx + 1] + 0.114 * data[idx + 2];
                                };

                                for (let x = 0; x < width; x++) {
                                    let runStart = -1;
                                    for (let y = 0; y < height; y++) {
                                        if (getLum(x, y) < 178) {
                                            if (runStart === -1) runStart = y;
                                        } else {
                                            if (runStart !== -1 && (y - runStart) >= minVertLineLen) {
                                                for (let k = runStart; k < y; k++) {
                                                    const pIdx = (k * width + x) * 4;
                                                    data[pIdx] = 255;
                                                    data[pIdx + 1] = 255;
                                                    data[pIdx + 2] = 255;
                                                }
                                            }
                                            runStart = -1;
                                        }
                                    }
                                    if (runStart !== -1 && (height - runStart) >= minVertLineLen) {
                                        for (let k = runStart; k < height; k++) {
                                            const pIdx = (k * width + x) * 4;
                                            data[pIdx] = 255;
                                            data[pIdx + 1] = 255;
                                            data[pIdx + 2] = 255;
                                        }
                                    }
                                }

                                ctx.putImageData(imgData, 0, 0);
                            } catch (e) {
                                // Keep original canvas if pixel inspection fails
                            }

                            canvas.toBlob(outBlob => {
                                resolve({ blob: outBlob || blob, width, height });
                            }, 'image/jpeg', 0.92);
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(url);
                            resolve({ blob, width: 1600, height: 1200 });
                        };
                        img.src = url;
                    });
                },

                /**
                 * Combined 2D Spatial Grid + Line Parser:
                 * Uses 2D (X, Y) coordinates from PDF.js / Tesseract bounding boxes to accurately
                 * reconstruct multi-line table cells in Section V (Work Experience) and Section VII (Trainings),
                 * then merges with the line-based parser so zero rows are missed.
                 */
                parseCombined2DAndTextLocally(items2D, rawText) {
                    const lineParsed = this.parseCSForm212TextLocally(rawText);
                    if (!Array.isArray(items2D) || items2D.length === 0) {
                        return lineParsed;
                    }

                    const gridParsed = this.parse2DCoordinatesCSForm212(items2D);

                    const mergedTrainings = this.deduplicateRows(
                        [...gridParsed.trainings, ...lineParsed.trainings],
                        'training_title'
                    );
                    const mergedWork = this.deduplicateRows(
                        [...gridParsed.work_experience, ...lineParsed.work_experience],
                        'position_title'
                    );

                    return {
                        applicant_name: lineParsed.applicant_name || '',
                        trainings: mergedTrainings,
                        work_experience: mergedWork
                    };
                },

                /**
                 * 2D Spatial Coordinate Table Reconstructor for CS Form No. 212:
                 * Anchors on inclusive date pairs (From / To) and collects multi-line cell text
                 * within each row's vertical [yTop, yBottom] band and horizontal X column boundaries.
                 */
                parse2DCoordinatesCSForm212(items2D) {
                    const trainings = [];
                    const work_experience = [];

                    const dateExtractRegex = /(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/g;
                    const presentExtractRegex = /\b(PRESENT|CURRENT|ONGOING)\b/i;
                    const knownStatuses = [
                        'PERMANENT', 'COSW', 'CONTRACT OF SERVICE/JOB ORDER', 'CONTRACT OF SERVICE',
                        'CONTRACTUAL', 'CASUAL', 'JOB ORDER', 'INTERNSHIP', 'PROJECT-BASED',
                        'PROJECT BASED', 'TEMPORARY', 'COTERMINOUS', 'FIXED TERM', 'PART-TIME', 'FREELANCE'
                    ];
                    const knownLdTypes = [
                        'TECHNICAL', 'MANAGERIAL', 'SUPERVISORY', 'FOUNDATION', 'CLERICAL', 'ADMINISTRATIVE', 'FOUNDATIONAL'
                    ];

                    const byPage = {};
                    for (const it of items2D) {
                        const p = it.pageNum || 1;
                        if (!byPage[p]) byPage[p] = [];
                        byPage[p].push(it);
                    }

                    for (const pageKey of Object.keys(byPage)) {
                        const pageItems = byPage[pageKey];
                        if (pageItems.length < 5) continue;

                        const pageW = pageItems[0].pageWidth || 612;
                        const sf = Math.max(1.0, pageW / 612); // Scale factor for 2000px OCR images vs 612px PDFs

                        const dateTokens = [];
                        for (const it of pageItems) {
                            const raw = String(it.str || '').trim();
                            const matches = Array.from(raw.matchAll(dateExtractRegex));
                            if (matches.length >= 2) {
                                dateTokens.push({
                                    str: matches[0][1],
                                    x: it.x,
                                    y: it.y,
                                    width: (it.width || 45 * sf) * 0.48,
                                    isDate: true
                                });
                                dateTokens.push({
                                    str: matches[1][1],
                                    x: it.x + (it.width || 90 * sf) * 0.52,
                                    y: it.y,
                                    width: (it.width || 45 * sf) * 0.48,
                                    isDate: true
                                });
                                continue;
                            }
                            if (matches.length === 1) {
                                dateTokens.push({
                                    str: matches[0][1],
                                    x: it.x,
                                    y: it.y,
                                    width: it.width || (42 * sf),
                                    isDate: true
                                });
                                continue;
                            }
                            if (presentExtractRegex.test(raw)) {
                                dateTokens.push({
                                    str: 'PRESENT',
                                    x: it.x,
                                    y: it.y,
                                    width: it.width || (42 * sf),
                                    isPresent: true
                                });
                            }
                        }

                        // Pair up (date_from, date_to) tokens on the same horizontal row
                        const ySameLineTol = 10.5 * sf;
                        dateTokens.sort((a, b) => b.y - a.y || a.x - b.x);
                        const dateRows = [];
                        const used = new Set();

                        for (let i = 0; i < dateTokens.length; i++) {
                            if (used.has(i)) continue;
                            const t1 = dateTokens[i];
                            if (t1.isPresent) continue;

                            let bestIdx = -1;
                            for (let j = 0; j < dateTokens.length; j++) {
                                if (i === j || used.has(j)) continue;
                                const t2 = dateTokens[j];
                                if (Math.abs(t1.y - t2.y) <= ySameLineTol && t2.x > t1.x && (t2.x - t1.x) < pageW * 0.28) {
                                    if (bestIdx === -1 || t2.x < dateTokens[bestIdx].x) {
                                        bestIdx = j;
                                    }
                                }
                            }

                            if (bestIdx !== -1) {
                                used.add(i);
                                used.add(bestIdx);
                                const t2 = dateTokens[bestIdx];
                                dateRows.push({
                                    y: (t1.y + t2.y) / 2,
                                    xFrom: t1.x,
                                    xTo: t2.x + (t2.width || 40 * sf),
                                    dateFrom: this.normalizeLocalDate(t1.str, false),
                                    dateTo: this.normalizeLocalDate(t2.str, true)
                                });
                            }
                        }

                        dateRows.sort((a, b) => b.y - a.y);

                        for (let rIdx = 0; rIdx < dateRows.length; rIdx++) {
                            const dr = dateRows[rIdx];
                            const prevY = rIdx > 0 ? dateRows[rIdx - 1].y : dr.y + (26 * sf);
                            const nextY = rIdx < dateRows.length - 1 ? dateRows[rIdx + 1].y : dr.y - (26 * sf);

                            const halfUp = Math.min(20 * sf, Math.max(8 * sf, (prevY - dr.y) * 0.48));
                            const halfDown = Math.min(20 * sf, Math.max(8 * sf, (dr.y - nextY) * 0.48));
                            const yTop = dr.y + halfUp;
                            const yBottom = dr.y - halfDown;

                            const bandItems = pageItems.filter(it => {
                                if (it.y > yTop || it.y < yBottom) return false;
                                if (Math.abs(it.y - dr.y) <= ySameLineTol && it.x >= dr.xFrom - (6 * sf) && it.x <= dr.xTo + (4 * sf)) {
                                    return false;
                                }
                                return true;
                            });

                            const sortReadingOrder = (arr) => {
                                return arr.slice().sort((a, b) => {
                                    if (Math.abs(a.y - b.y) > (6 * sf)) return b.y - a.y;
                                    return a.x - b.x;
                                });
                            };

                            if (dr.xFrom < pageW * 0.27) {
                                // WORK EXPERIENCE ROW (Section V)
                                const rightItems = bandItems.filter(it => it.x >= dr.xTo - (6 * sf));
                                if (rightItems.length === 0) continue;

                                const posItems = sortReadingOrder(rightItems.filter(it => it.x < pageW * 0.445));
                                const compItems = sortReadingOrder(rightItems.filter(it => it.x >= pageW * 0.445 && it.x < pageW * 0.685));
                                const tailItems = sortReadingOrder(rightItems.filter(it => it.x >= pageW * 0.685));

                                let posTitle = posItems.map(it => it.str).join(' ').replace(/\s+/g, ' ').trim();
                                let company = compItems.map(it => it.str).join(' ').replace(/\s+/g, ' ').trim();
                                const tailStr = tailItems.map(it => it.str).join(' ').replace(/\s+/g, ' ').trim();

                                if (!posTitle && company) {
                                    posTitle = company;
                                    company = '';
                                }

                                let govService = 'N';
                                const govMatch = tailStr.match(/\b(Y|N|YES|NO)$/i);
                                if (govMatch) {
                                    govService = govMatch[1].toUpperCase().startsWith('Y') ? 'Y' : 'N';
                                }

                                let status = 'COSW';
                                for (const ks of knownStatuses) {
                                    if (tailStr.toUpperCase().includes(ks)) {
                                        status = ks;
                                        break;
                                    }
                                }

                                if (posTitle && !['N/A', 'NONE', 'POSITION TITLE'].includes(posTitle.toUpperCase())) {
                                    work_experience.push({
                                        position_title: posTitle,
                                        department_agency_company: company,
                                        date_from: dr.dateFrom,
                                        date_to: dr.dateTo,
                                        status_of_appointment: status,
                                        gov_service: govService,
                                        computed_years: this.computeYearsBetween(dr.dateFrom, dr.dateTo, 0.0)
                                    });
                                }
                            } else {
                                // LEARNING & DEVELOPMENT / TRAINING ROW (Section VII)
                                const leftTitleItems = sortReadingOrder(bandItems.filter(it => it.x < dr.xFrom - (6 * sf)));
                                const rightTailItems = sortReadingOrder(bandItems.filter(it => it.x >= dr.xTo - (6 * sf)));

                                const trainingTitle = leftTitleItems
                                    .map(it => it.str)
                                    .join(' ')
                                    .replace(/^[|!\[\]1-9\.\)\s]+(?=[A-Z]{2,})/, '')
                                    .replace(/\s+/g, ' ')
                                    .trim();

                                if (!trainingTitle || trainingTitle.length < 3 || ['N/A', 'NONE'].includes(trainingTitle.toUpperCase())) {
                                    continue;
                                }

                                const rightText = rightTailItems.map(it => it.str).join(' ').replace(/[|]/g, ' ').replace(/\s+/g, ' ').trim();
                                const hrsMatch = rightText.match(/(\d+(?:\.\d+)?)/);
                                const hours = hrsMatch ? parseFloat(hrsMatch[1]) : 0;

                                let afterHours = hrsMatch
                                    ? rightText.slice(rightText.indexOf(hrsMatch[0]) + hrsMatch[0].length).trim()
                                    : rightText;
                                const parsedTail = this.splitTrainingTail(afterHours, knownLdTypes);

                                trainings.push({
                                    training_title: trainingTitle,
                                    date_from: dr.dateFrom,
                                    date_to: dr.dateTo,
                                    hours: Number.isFinite(hours) ? hours : 0,
                                    type_of_ld: parsedTail.typeOfLd,
                                    conducted_by: parsedTail.conductedBy
                                });
                            }
                        }
                    }

                    return { trainings, work_experience };
                },

                async compressImageForFastUpload(file) {
                    return new Promise((resolve) => {
                        const img = new Image();
                        const url = URL.createObjectURL(file);
                        img.onload = () => {
                            URL.revokeObjectURL(url);
                            const maxDim = 1500;
                            let { width, height } = img;
                            if (width > maxDim || height > maxDim) {
                                const ratio = Math.min(maxDim / width, maxDim / height);
                                width = Math.round(width * ratio);
                                height = Math.round(height * ratio);
                            }
                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, width, height);
                            ctx.drawImage(img, 0, 0, width, height);
                            canvas.toBlob(blob => resolve(blob || file), 'image/jpeg', 0.85);
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(url);
                            resolve(file);
                        };
                        img.src = url;
                    });
                },

                /**
                 * Local CS Form No. 212 (Revised 2025 / 2017) Multi-Page Table Parser.
                 * Handles both same-line and split-line Training Titles across pages.
                 */
                extractApplicantNameFromTextOrFilename(rawText, fileName = '') {
                    if (rawText) {
                        const lines = rawText.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
                        let surname = '';
                        let firstName = '';
                        let middleName = '';

                        for (let i = 0; i < Math.min(lines.length, 40); i++) {
                            const line = lines[i];
                            const mSurname = line.match(/(?:2\.\s*)?SURNAME[\s\t:]+([A-ZÑ\s\-',.]{2,40})/i);
                            if (mSurname && !/FIRST\s*NAME/i.test(mSurname[1])) {
                                surname = mSurname[1].trim();
                            }
                            const mFirst = line.match(/FIRST\s*NAME[\s\t:]+([A-ZÑ\s\-',.]{2,40})/i);
                            if (mFirst && !/NAME\s*EXTENSION|MIDDLE/i.test(mFirst[1])) {
                                firstName = mFirst[1].trim();
                            }
                            const mMiddle = line.match(/MIDDLE\s*NAME[\s\t:]+([A-ZÑ\s\-',.]{2,40})/i);
                            if (mMiddle && !/3\.\s*DATE/i.test(mMiddle[1])) {
                                middleName = mMiddle[1].trim();
                            }
                        }

                        if (surname && firstName) {
                            return `${surname}, ${firstName}${middleName && middleName !== 'N/A' ? ' ' + middleName : ''}`;
                        }
                    }

                    // Fallback: clean human-readable name from filename (e.g. "PDS_Juan_Dela_Cruz.pdf")
                    if (fileName) {
                        const firstFile = fileName.split(',')[0].trim();
                        const base = firstFile.replace(/\.[^.]+$/, '')
                            .replace(/\b(PDS|CS\s*FORM\s*212|REVISED\s*2025|2025|2026|2024|PAGE\s*\d+|PAGE\d+|TRAININGS|TRAINING|WORK|EXPERIENCE|LEARNING|DEVELOPMENT|SECTION|SCAN|SCREENSHOT|IMG|IMAGE)\b/gi, ' ')
                            .replace(/[_\-]+/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                        if (base.length >= 3 && /[A-Za-z]{2,}/.test(base) && !/^(media|whatsapp|viber|capture|untitled|\d+)/i.test(base)) {
                            return base.toUpperCase();
                        }
                    }
                    return '';
                },

                parseCSForm212TextLocally(rawText) {
                    const work_experience = [];
                    const trainings = [];
                    const applicant_name = this.extractApplicantNameFromTextOrFilename(rawText, this.uploadedFileName);
                    if (!rawText || !rawText.trim()) {
                        return { applicant_name, work_experience, trainings };
                    }

                    const lines = rawText
                        .split(/\r?\n/)
                        .map(l => l
                            .replace(/(\d{1,2})\s*[\/]\s*(\d{1,2})\s*[\/]\s*(\d{2,4})/g, '$1/$2/$3')
                            .replace(/\s*\|\s*/g, '\t')
                            .trim()
                        )
                        .filter(Boolean);

                    let currentSection = 'UNKNOWN';
                    let pendingTitleCandidate = '';

                    const datePattern = '(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})';
                    const dateOrPresentPattern = '(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4}|PRESENT|TO\\s*DATE|CURRENT)';

                    const workRowRegex = new RegExp(`^[^A-Za-z0-9]*(?:\\d{1,2}[\\.\\)]\\s*)?${datePattern}[\\s\\t\\-–|toTO]*${dateOrPresentPattern}[\\s\\t|]+(.+)$`, 'i');
                    const trainingRowRegex = new RegExp(`^(.+?)[\\s\\t|]+${datePattern}[\\s\\t\\-–|toTO]*${datePattern}(?:[\\s\\t|]+(\\d+(?:\\.\\d+)?))?[\\s\\t|]*(.*)$`, 'i');
                    const splitTrainingDatesRegex = new RegExp(`^[^A-Za-z0-9]*${datePattern}[\\s\\t\\-–|toTO]*${datePattern}(?:[\\s\\t|]+(\\d+(?:\\.\\d+)?))?[\\s\\t|]+(.+)$`, 'i');

                    const knownStatuses = [
                        'PERMANENT', 'COSW', 'CONTRACT OF SERVICE', 'CONTRACTUAL', 'CASUAL',
                        'JOB ORDER', 'INTERNSHIP', 'PROJECT-BASED', 'PROJECT BASED', 'TEMPORARY',
                        'COTERMINOUS', 'FIXED TERM', 'PART-TIME', 'FREELANCE', 'PROBATIONARY', 'VOLUNTEER'
                    ];
                    const knownLdTypes = [
                        'TECHNICAL', 'MANAGERIAL', 'SUPERVISORY', 'FOUNDATION', 'CLERICAL', 'ADMINISTRATIVE', 'FOUNDATIONAL'
                    ];

                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const upper = line.toUpperCase();

                        if (upper.startsWith('=== PDF PAGE') || upper.startsWith('=== OCR PAGE')) {
                            pendingTitleCandidate = '';
                            continue;
                        }

                        if (upper.includes('WORK EXPERIENCE') && !upper.includes('VOLUNTARY')) {
                            currentSection = 'WORK';
                            pendingTitleCandidate = '';
                            continue;
                        }
                        if (upper.includes('VOLUNTARY WORK')) {
                            currentSection = 'OTHER';
                            pendingTitleCandidate = '';
                            continue;
                        }
                        if (upper.includes('LEARNING AND DEVELOPMENT') || upper.includes('TRAINING PROGRAMS ATTENDED')) {
                            currentSection = 'TRAINING';
                            pendingTitleCandidate = '';
                            continue;
                        }
                        if (upper.includes('OTHER INFORMATION') || upper.includes('SPECIAL SKILLS AND HOBBIES')) {
                            currentSection = 'OTHER';
                            pendingTitleCandidate = '';
                            continue;
                        }
                        if (upper.includes('EDUCATIONAL BACKGROUND') || upper.includes('CIVIL SERVICE ELIGIBILITY') || upper.includes('FAMILY BACKGROUND')) {
                            currentSection = 'OTHER';
                            pendingTitleCandidate = '';
                            continue;
                        }

                        if (currentSection === 'OTHER') {
                            continue;
                        }

                        if (
                            upper.includes('INCLUSIVE DATES') ||
                            upper.includes('POSITION TITLE') ||
                            upper.includes('WRITE IN FULL') ||
                            upper.includes('CONTINUE ON SEPARATE SHEET') ||
                            upper.includes('NUMBER OF HOURS') ||
                            upper.includes('CONDUCTED/ SPONSORED') ||
                            upper === 'FROM TO'
                        ) {
                            pendingTitleCandidate = '';
                            continue;
                        }

                        // 1. Standard Training Row: [Title] [DateFrom] [DateTo] [Hours (optional if OCR missed cell)] [Type] [ConductedBy]
                        if (currentSection !== 'WORK') {
                            const trMatch = line.match(trainingRowRegex);
                            if (trMatch) {
                                const rawTitle = trMatch[1].replace(/\t+/g, ' ').replace(/^\d{1,2}[\.\)]\s*/, '').trim();
                                const dateFrom = this.normalizeLocalDate(trMatch[2], false);
                                const dateTo = this.normalizeLocalDate(trMatch[3], false);
                                const hours = trMatch[4] !== undefined && trMatch[4] !== '' ? parseFloat(trMatch[4]) : 8;
                                const tail = (trMatch[5] || '').trim();

                                if (rawTitle.length >= 3 && !/^\d+$/.test(rawTitle) && !/^\d{1,2}[\/\-\.]\d{1,2}/.test(rawTitle) && Number.isFinite(hours) && hours <= 2000) {
                                    const parsedTail = this.splitTrainingTail(tail, knownLdTypes);
                                    trainings.push({
                                        training_title: rawTitle,
                                        date_from: dateFrom,
                                        date_to: dateTo,
                                        hours: hours,
                                        type_of_ld: parsedTail.typeOfLd,
                                        conducted_by: parsedTail.conductedBy
                                    });
                                    pendingTitleCandidate = '';
                                    continue;
                                }
                            }

                            // 1B. Split-Line Training Row (where Title was on the preceding line and Dates+Hours+Type+Sponsor are on this line)
                            const splitTrMatch = line.match(splitTrainingDatesRegex);
                            if (splitTrMatch && (currentSection === 'TRAINING' || pendingTitleCandidate !== '')) {
                                const dateFrom = this.normalizeLocalDate(splitTrMatch[1], false);
                                const dateTo = this.normalizeLocalDate(splitTrMatch[2], false);
                                const hours = parseFloat(splitTrMatch[3]);
                                const tail = splitTrMatch[4].trim();

                                if (Number.isFinite(hours) && hours > 0 && hours <= 2000) {
                                    const parsedTail = this.splitTrainingTail(tail, knownLdTypes);
                                    const titleToUse = pendingTitleCandidate || parsedTail.conductedBy || 'Training Program';
                                    trainings.push({
                                        training_title: titleToUse,
                                        date_from: dateFrom,
                                        date_to: dateTo,
                                        hours: hours,
                                        type_of_ld: parsedTail.typeOfLd,
                                        conducted_by: pendingTitleCandidate ? parsedTail.conductedBy : ''
                                    });
                                    pendingTitleCandidate = '';
                                    continue;
                                }
                            }
                        }

                        // 2. Work Experience Row: [DateFrom] [DateTo/PRESENT] [Position] [Company] ... [Status] [Y/N]
                        if (currentSection !== 'TRAINING') {
                            const wkMatch = line.match(workRowRegex);
                            if (wkMatch) {
                                const dateFrom = this.normalizeLocalDate(wkMatch[1], false);
                                const dateTo = this.normalizeLocalDate(wkMatch[2], true);
                                let rest = wkMatch[3].trim();

                                let govService = 'N';
                                const govMatch = rest.match(/[\s\t]+(Y|N|YES|NO)$/i);
                                if (govMatch) {
                                    govService = govMatch[1].toUpperCase().startsWith('Y') ? 'Y' : 'N';
                                    rest = rest.slice(0, rest.length - govMatch[0].length).trim();
                                }

                                let status = '';
                                for (const ks of knownStatuses) {
                                    const regex = new RegExp(`[\\s\\t]+(${ks})$`, 'i');
                                    const sm = rest.match(regex);
                                    if (sm) {
                                        status = sm[1].toUpperCase();
                                        rest = rest.slice(0, rest.length - sm[0].length).trim();
                                        break;
                                    }
                                }

                                rest = rest
                                    .replace(/[\s\t]+(?:PHP|₱|P)?\s*[\d,]+(?:\.\d{2})?(?:[\s\t]+(?:\d{1,2}\s*-\s*\d{1,2}|N\/A|NONE|-))?$/i, '')
                                    .replace(/[\s\t]+(?:N\/A|NONE|-)(?:[\s\t]+(?:N\/A|NONE|-))?$/i, '')
                                    .trim();

                                let positionTitle = rest;
                                let company = '';

                                const tabCols = rest.split(/\t+/).map(s => s.trim()).filter(Boolean);
                                if (tabCols.length >= 2) {
                                    positionTitle = tabCols[0];
                                    company = tabCols.slice(1).join(' ');
                                } else {
                                    const multiSpaceCols = rest.split(/\s{2,}/).map(s => s.trim()).filter(Boolean);
                                    if (multiSpaceCols.length >= 2) {
                                        positionTitle = multiSpaceCols[0];
                                        company = multiSpaceCols.slice(1).join(' ');
                                    }
                                }

                                if (positionTitle && !['N/A', 'NONE'].includes(positionTitle.toUpperCase())) {
                                    work_experience.push({
                                        position_title: positionTitle,
                                        department_agency_company: company,
                                        date_from: dateFrom,
                                        date_to: dateTo,
                                        status_of_appointment: status || 'COSW',
                                        gov_service: govService,
                                        computed_years: this.computeYearsBetween(dateFrom, dateTo, 0.0)
                                    });
                                    pendingTitleCandidate = '';
                                    continue;
                                }
                            }
                        }

                        // Save non-date text line as a potential multi-line Training Title candidate
                        if (line.length >= 4 && !/\b\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}\b/.test(line)) {
                            pendingTitleCandidate = line.replace(/\t+/g, ' ').trim();
                        }
                    }

                    return { applicant_name, work_experience, trainings };
                },

                splitTrainingTail(tail, knownLdTypes) {
                    let typeOfLd = 'TECHNICAL';
                    let conductedBy = tail.replace(/\t+/g, ' ').trim();

                    const tailParts = tail.split(/\t+/).map(s => s.trim()).filter(Boolean);
                    if (tailParts.length >= 2) {
                        typeOfLd = tailParts[0];
                        conductedBy = tailParts.slice(1).join(' ');
                    } else {
                        for (const kt of knownLdTypes) {
                            const idx = tail.toUpperCase().indexOf(kt);
                            if (idx !== -1) {
                                typeOfLd = tail.slice(idx, idx + kt.length);
                                conductedBy = (tail.slice(0, idx) + ' ' + tail.slice(idx + kt.length)).trim();
                                break;
                            }
                        }
                    }
                    return { typeOfLd, conductedBy };
                },

                normalizeLocalDate(str, allowPresent = true) {
                    const clean = String(str || '').trim();
                    if (allowPresent && ['PRESENT', 'TO DATE', 'CURRENT'].includes(clean.toUpperCase())) {
                        return 'PRESENT';
                    }
                    const dt = this.parseDMY(clean);
                    if (!dt) return clean.replace(/[-.]/g, '/');
                    const dd = String(dt.getDate()).padStart(2, '0');
                    const mm = String(dt.getMonth() + 1).padStart(2, '0');
                    const yyyy = dt.getFullYear();
                    return `${dd}/${mm}/${yyyy}`;
                },

                extractDateAccomplishedFromText(rawText) {
                    if (!rawText) return '';
                    const months = {
                        JAN: '01', JANUARY: '01',
                        FEB: '02', FEBRUARY: '02',
                        MAR: '03', MARCH: '03',
                        APR: '04', APRIL: '04',
                        MAY: '05',
                        JUN: '06', JUNE: '06',
                        JUL: '07', JULY: '07',
                        AUG: '08', AUGUST: '08',
                        SEP: '09', SEPT: '09', SEPTEMBER: '09',
                        OCT: '10', OCTOBER: '10',
                        NOV: '11', NOVEMBER: '11',
                        DEC: '12', DECEMBER: '12'
                    };

                    const parseFlexibleDate = (str) => {
                        const s = String(str || '').trim();
                        if (!s) return '';

                        // 1. DD MONTH YYYY (e.g. "05 JANUARY 2026", "5 Jan 2026")
                        let m = s.match(/\b(\d{1,2})\s+([A-Z]{3,9})\.?\s*,?\s*(\d{4})\b/i);
                        if (m && months[m[2].toUpperCase()]) {
                            const dd = String(parseInt(m[1], 10)).padStart(2, '0');
                            const mm = months[m[2].toUpperCase()];
                            return `${m[3]}-${mm}-${dd}`;
                        }

                        // 2. MONTH DD, YYYY (e.g. "JANUARY 05, 2026", "Jan. 5 2026")
                        m = s.match(/\b([A-Z]{3,9})\.?\s+(\d{1,2})(?:st|nd|rd|th)?\s*,?\s*(\d{4})\b/i);
                        if (m && months[m[1].toUpperCase()]) {
                            const dd = String(parseInt(m[2], 10)).padStart(2, '0');
                            const mm = months[m[1].toUpperCase()];
                            return `${m[3]}-${mm}-${dd}`;
                        }

                        // 3. ISO YYYY-MM-DD
                        m = s.match(/\b(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\b/);
                        if (m) {
                            return `${m[1]}-${String(parseInt(m[2], 10)).padStart(2, '0')}-${String(parseInt(m[3], 10)).padStart(2, '0')}`;
                        }

                        // 4. Numeric DD/MM/YYYY or MM/DD/YYYY
                        m = s.match(/\b(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})\b/);
                        if (m) {
                            const dt = this.parseDMY(m[1]);
                            if (dt) {
                                const yyyy = dt.getFullYear();
                                const mm = String(dt.getMonth() + 1).padStart(2, '0');
                                const dd = String(dt.getDate()).padStart(2, '0');
                                return `${yyyy}-${mm}-${dd}`;
                            }
                        }

                        return '';
                    };

                    const lines = rawText.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
                    for (let i = 0; i < lines.length; i++) {
                        if (/DATE\s*ACCOMPLISHED/i.test(lines[i])) {
                            // Check same line, line directly above (CS Form 212 puts value right above "Date Accomplished"), or line directly below
                            const candidates = [
                                lines[i],
                                i > 0 ? lines[i - 1] : '',
                                i + 1 < lines.length ? lines[i + 1] : '',
                                i > 1 ? lines[i - 2] : ''
                            ];
                            for (const c of candidates) {
                                const parsed = parseFlexibleDate(c);
                                if (parsed) return parsed;
                            }
                        }
                    }
                    return '';
                },

                /**
                 * Send all extracted text + rendered multi-page images to POST /api/scan-pds.
                 */
                async sendMultiPageToGemini({ extractedText = '', pageBlobs = [], fallbackFiles = [], fallbackLocal = null, pageCount = 1 }) {
                    const formData = new FormData();
                    if (extractedText) {
                        formData.append('extracted_text', extractedText);
                    }

                    // Attach rendered page images (e.g. Page 2 Work Experience & Page 3 Trainings)
                    pageBlobs.forEach((blob, idx) => {
                        formData.append('page_images[]', blob, `pds_page_${idx + 1}.jpg`);
                    });

                    // If no page blobs were rendered, attach original file
                    if (pageBlobs.length === 0 && fallbackFiles.length > 0) {
                        formData.append('pds_file', fallbackFiles[0], fallbackFiles[0].name);
                    }

                    const response = await fetch('{{ route('pds.scan') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: formData,
                        signal: this.abortController.signal
                    });

                    if (response.status === 401) {
                        window.location.reload();
                        return false;
                    }

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok || !result.ok) {
                        // Return false silently so processDocuments automatically triggers Local OCR fallback
                        return false;
                    }

                    let extractedTrainings = Array.isArray(result.data?.trainings) ? result.data.trainings : [];
                    let extractedWork = Array.isArray(result.data?.work_experience) ? result.data.work_experience : [];
                    const detectedName = String(result.data?.applicant_name || fallbackLocal?.applicant_name || '').trim()
                        || this.extractApplicantNameFromTextOrFilename('', this.uploadedFileName);

                    if (detectedName && (!this.appendOnScan || !this.applicantName.trim())) {
                        this.applicantName = detectedName;
                    }

                    const detectedDateAcc = String(result.data?.date_accomplished || '').trim()
                        || this.extractDateAccomplishedFromText(extractedText);
                    if (detectedDateAcc && (!this.appendOnScan || !this.dateAccomplished)) {
                        this.dateAccomplished = detectedDateAcc;
                    }

                    // If AI missed a section that our local parser found, merge it in!
                    if (extractedTrainings.length === 0 && fallbackLocal?.trainings?.length > 0) {
                        extractedTrainings = fallbackLocal.trainings;
                    }
                    if (extractedWork.length === 0 && fallbackLocal?.work_experience?.length > 0) {
                        extractedWork = fallbackLocal.work_experience;
                    }

                    this.applyExtractedRows(extractedTrainings, extractedWork);

                    const totalAdded = extractedTrainings.length + extractedWork.length;
                    this.scanNotice = {
                        type: totalAdded === 0 ? 'error' : 'info',
                        message: `Scanned ${pageCount} page(s) in ${this.scanElapsed.toFixed(1)}s: Extracted ${extractedTrainings.length} training(s) and ${extractedWork.length} work experience item(s).`
                    };
                    return true;
                },

                /**
                 * Smart Section-Aware Merge:
                 * - Never wipes out Trainings if the newly scanned page only contained Work Experience (and vice versa).
                 * - Deduplicates rows by title + date_from so re-scanning or multi-page overlap never creates duplicates.
                 */
                applyExtractedRows(rawTrainings, rawWork) {
                    const mappedTrainings = rawTrainings.map(row => ({
                        id: this.generateId('tr'),
                        training_title: row.training_title || '',
                        date_from: row.date_from || '',
                        date_to: row.date_to || '',
                        hours: Number.isFinite(Number(row.hours)) ? Number(row.hours) : 0,
                        type_of_ld: row.type_of_ld || 'TECHNICAL',
                        conducted_by: row.conducted_by || '',
                        included: false,
                        editing: false
                    }));

                    const mappedWork = rawWork.map(row => {
                        const dur = row.date_from
                            ? this.computeDurationBetween(row.date_from, row.date_to, row.computed_years ?? 0)
                            : this.decimalYearsToYM(row.computed_years ?? 0);
                        return {
                            id: this.generateId('wk'),
                            position_title: row.position_title || '',
                            department_agency_company: row.department_agency_company || '',
                            date_from: row.date_from || '',
                            date_to: row.date_to || '',
                            status_of_appointment: row.status_of_appointment || '',
                            gov_service: row.gov_service === 'Y' ? 'Y' : 'N',
                            duration_years: dur.years,
                            duration_months: dur.months,
                            duration_days: dur.days,
                            computed_years: Number(((dur.years * 365.25 + dur.months * 30.4375 + dur.days) / 365.25).toFixed(1)),
                            included: false,
                            editing: false
                        };
                    });

                    if (this.appendOnScan) {
                        if (mappedTrainings.length > 0) {
                            this.trainings = this.deduplicateRows([...this.trainings, ...mappedTrainings], 'training_title');
                        }
                        if (mappedWork.length > 0) {
                            this.workExperiences = this.deduplicateRows([...this.workExperiences, ...mappedWork], 'position_title');
                        }
                    } else {
                        // Replace cleanly with the newly scanned PDS (no leftover rows from a previous applicant)
                        this.trainings = mappedTrainings;
                        this.workExperiences = mappedWork;
                    }

                    // Recalculate work experience Years, Months & Days with document-level date format detection (dd/mm/yyyy vs mm/dd/yyyy)
                    this.workExperiences.forEach(w => {
                        if (w.date_from) {
                            this.recomputeRowYears(w);
                        } else if (w.duration_years === undefined || w.duration_months === undefined || w.duration_days === undefined) {
                            const dur = this.decimalYearsToYM(w.computed_years ?? 0);
                            w.duration_years = dur.years;
                            w.duration_months = dur.months;
                            w.duration_days = dur.days;
                        }
                    });

                    // Smart auto-switch left tab to whichever section has newly extracted items
                    if (mappedTrainings.length === 0 && mappedWork.length > 0) {
                        this.activeLeftTab = 'work';
                    } else if (mappedTrainings.length > 0) {
                        this.activeLeftTab = 'trainings';
                    }
                },

                deduplicateRows(rows, titleKey) {
                    const seen = new Set();
                    return rows.filter(row => {
                        const sig = `${String(row[titleKey] || '').trim().toUpperCase()}|${String(row.date_from || '').trim()}|${String(row.date_to || '').trim()}`;
                        if (seen.has(sig)) return false;
                        seen.add(sig);
                        return true;
                    });
                },

                cancelScan() {
                    this.scanCancelled = true;
                    if (this.abortController) {
                        this.abortController.abort();
                    }
                    this.isScanning = false;
                    this.scanNotice = {
                        type: 'error',
                        message: 'Scan cancelled.'
                    };
                },

                saveToSessionStorage() {
                    try {
                        const basePayload = {
                            applicantName: this.applicantName,
                            targetPosition: this.targetPosition,
                            uploadedFileName: this.uploadedFileName,
                            dateAccomplished: this.dateAccomplished,
                            scanEngine: this.scanEngine,
                            trainings: this.trainings.map(t => ({ ...t, editing: false })),
                            workExperiences: this.workExperiences.map(w => ({ ...w, editing: false }))
                        };
                        try {
                            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                                ...basePayload,
                                previewPageImages: this.previewPageImages.slice(0, 4)
                            }));
                        } catch (quotaErr) {
                            // If images exceed sessionStorage quota, save rows without preview images
                            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(basePayload));
                        }
                    } catch (e) {
                        // Ignore storage errors
                    }
                },

                restoreFromSessionStorage() {
                    try {
                        const raw = sessionStorage.getItem(STORAGE_KEY);
                        if (!raw) return;
                        const parsed = JSON.parse(raw);
                        if (parsed && typeof parsed === 'object') {
                            this.applicantName = parsed.applicantName || '';
                            this.targetPosition = parsed.targetPosition || '';
                            this.uploadedFileName = parsed.uploadedFileName || '';
                            this.dateAccomplished = parsed.dateAccomplished || '';
                            this.previewPageImages = Array.isArray(parsed.previewPageImages) ? parsed.previewPageImages : [];
                            if (['hybrid', 'local', 'gemini'].includes(parsed.scanEngine)) {
                                this.scanEngine = parsed.scanEngine;
                            }
                            this.trainings = Array.isArray(parsed.trainings)
                                ? parsed.trainings.map(t => ({ ...t, editing: false }))
                                : [];
                            this.workExperiences = Array.isArray(parsed.workExperiences)
                                ? parsed.workExperiences.map(w => ({ ...w, editing: false }))
                                : [];
                            if (this.trainings.length === 0 && this.workExperiences.length > 0) {
                                this.activeLeftTab = 'work';
                            }
                            this.workExperiences.forEach(w => {
                                if (w.date_from) {
                                    this.recomputeRowYears(w);
                                } else if (w.duration_years === undefined || w.duration_months === undefined || w.duration_days === undefined) {
                                    const dur = this.decimalYearsToYM(w.computed_years ?? 0);
                                    w.duration_years = dur.years;
                                    w.duration_months = dur.months;
                                    w.duration_days = dur.days;
                                } else {
                                    this.normalizeRowDuration(w);
                                }
                            });
                        }
                    } catch (e) {
                        sessionStorage.removeItem(STORAGE_KEY);
                    }
                }
            };
        }
    </script>
@endif

</body>
</html>
