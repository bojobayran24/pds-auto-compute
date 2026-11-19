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

            <p class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 leading-normal">
                Session-only workspace. Uploaded files are parsed in memory and never stored on disk or in a database.
            </p>
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
            <div class="max-w-[1480px] mx-auto px-4 sm:px-6 h-12 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="font-semibold text-sm text-slate-900 truncate">
                        PDS Auto-Compute
                    </span>
                    <span class="hidden sm:inline-block text-slate-300">|</span>
                    <span class="hidden sm:inline-block text-xs text-slate-500 truncate">
                        CS Form No. 212 (Revised 2025)
                    </span>
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
        <main class="flex-1 max-w-[1480px] w-full mx-auto px-4 sm:px-6 py-5 print-layout">
            <div class="flex flex-col lg:flex-row items-start gap-6">

                <!-- =========================================================== -->
                <!-- LEFT COLUMN (45% width): SCANNER + CLEAN CHECKLIST PICKER   -->
                <!-- =========================================================== -->
                <div class="no-print w-full lg:w-[45%] space-y-4 shrink-0">

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

                        <!-- Non-Blocking Notice / Error Fallback -->
                        <template x-if="scanNotice.message">
                            <div
                                :class="scanNotice.type === 'error'
                                    ? 'bg-amber-50 border-amber-300 text-amber-900'
                                    : 'bg-slate-100 border-slate-300 text-slate-800'"
                                class="mt-2.5 rounded border px-3 py-2 text-xs flex items-start justify-between gap-2"
                            >
                                <div class="space-y-1.5">
                                    <p x-text="scanNotice.message"></p>
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
                    </section>

                    <!-- ======================================================= -->
                    <!-- SECTION VII: LEARNING & DEVELOPMENT (TRAININGS)         -->
                    <!-- ======================================================= -->
                    <section class="bg-white border border-slate-300 rounded-md overflow-hidden">
                        <div class="px-3.5 py-2.5 bg-slate-100 border-b border-slate-300 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-semibold text-slate-900">
                                    Trainings / Learning &amp; Development
                                </h3>
                                <span class="text-[11px] font-mono tabular-nums px-1.5 py-0.5 rounded bg-white border border-slate-300 text-slate-700 font-medium">
                                    <span x-text="selectedTrainings.length + '/' + trainings.length"></span> • <span x-text="formattedTotalTrainingHours + ' hrs'"></span>
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <template x-if="trainings.length > 0">
                                    <div class="inline-flex rounded border border-slate-300 bg-white overflow-hidden text-[11px] font-medium">
                                        <button
                                            type="button"
                                            @click="toggleAllTrainings(true)"
                                            :class="allTrainingsSelected ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                            class="px-2 py-0.5 transition"
                                        >
                                            All
                                        </button>
                                        <button
                                            type="button"
                                            @click="toggleAllTrainings(false)"
                                            :class="selectedTrainings.length === 0 ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                            class="px-2 py-0.5 border-l border-slate-300 transition"
                                        >
                                            None
                                        </button>
                                    </div>
                                </template>

                                <button
                                    type="button"
                                    @click="addTrainingRow(true)"
                                    class="px-2 py-0.5 text-[11px] font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 transition"
                                >
                                    + Add
                                </button>
                            </div>
                        </div>

                        <!-- Quick Filter Bar (Appears when > 3 trainings exist) -->
                        <template x-if="trainings.length > 3">
                            <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                                <input
                                    type="text"
                                    x-model="trainingSearch"
                                    placeholder="Filter trainings..."
                                    class="w-full text-xs bg-white border border-slate-300 rounded px-2.5 py-1 text-slate-800 placeholder-slate-400 focus:border-slate-600 focus:outline-none"
                                >
                                <template x-if="trainingSearch">
                                    <button
                                        type="button"
                                        @click="trainingSearch = ''"
                                        class="text-xs text-slate-500 hover:text-slate-800 px-1.5"
                                    >
                                        Clear
                                    </button>
                                </template>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <template x-if="trainings.length === 0">
                            <div class="px-4 py-6 text-center text-xs text-slate-500">
                                No training entries loaded. Upload a PDS above or <button type="button" @click="addTrainingRow(true)" class="underline text-slate-800 font-medium">add a row manually</button>.
                            </div>
                        </template>

                        <!-- Professional Highlightable Rows -->
                        <template x-if="trainings.length > 0">
                            <div class="divide-y divide-slate-200">
                                <template x-for="(item, index) in trainings" :key="item.id">
                                    <div x-show="matchesTrainingFilter(item)">

                                        <!-- 1. SELECTION ROW (HIGHLIGHTED WHEN SELECTED, CLEAN WHITE WHEN UNSELECTED) -->
                                        <div
                                            x-show="!item.editing"
                                            @click="item.included = !item.included"
                                            :class="item.included
                                                ? 'bg-blue-50/75 hover:bg-blue-50'
                                                : 'bg-white hover:bg-slate-50'"
                                            class="px-3.5 py-2.5 flex items-center justify-between gap-3 cursor-pointer select-none transition-colors"
                                        >
                                            <div class="flex items-start gap-2.5 min-w-0 flex-1">
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
                    </section>

                    <!-- ======================================================= -->
                    <!-- SECTION V: WORK EXPERIENCE                              -->
                    <!-- ======================================================= -->
                    <section class="bg-white border border-slate-300 rounded-md overflow-hidden">
                        <div class="px-3.5 py-2.5 bg-slate-100 border-b border-slate-300 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-semibold text-slate-900">
                                    Work Experience
                                </h3>
                                <span class="text-[11px] font-mono tabular-nums px-1.5 py-0.5 rounded bg-white border border-slate-300 text-slate-700 font-medium">
                                    <span x-text="selectedWorkExperiences.length + '/' + workExperiences.length"></span> • <span x-text="formattedTotalWorkYears + ' yrs'"></span>
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <template x-if="workExperiences.length > 0">
                                    <div class="inline-flex rounded border border-slate-300 bg-white overflow-hidden text-[11px] font-medium">
                                        <button
                                            type="button"
                                            @click="toggleAllWork(true)"
                                            :class="allWorkSelected ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                            class="px-2 py-0.5 transition"
                                        >
                                            All
                                        </button>
                                        <button
                                            type="button"
                                            @click="toggleAllWork(false)"
                                            :class="selectedWorkExperiences.length === 0 ? 'bg-slate-200 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                                            class="px-2 py-0.5 border-l border-slate-300 transition"
                                        >
                                            None
                                        </button>
                                    </div>
                                </template>

                                <button
                                    type="button"
                                    @click="addWorkRow(true)"
                                    class="px-2 py-0.5 text-[11px] font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 transition"
                                >
                                    + Add
                                </button>
                            </div>
                        </div>

                        <!-- Quick Filter Bar (Appears when > 3 work items exist) -->
                        <template x-if="workExperiences.length > 3">
                            <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                                <input
                                    type="text"
                                    x-model="workSearch"
                                    placeholder="Filter positions..."
                                    class="w-full text-xs bg-white border border-slate-300 rounded px-2.5 py-1 text-slate-800 placeholder-slate-400 focus:border-slate-600 focus:outline-none"
                                >
                                <template x-if="workSearch">
                                    <button
                                        type="button"
                                        @click="workSearch = ''"
                                        class="text-xs text-slate-500 hover:text-slate-800 px-1.5"
                                    >
                                        Clear
                                    </button>
                                </template>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <template x-if="workExperiences.length === 0">
                            <div class="px-4 py-6 text-center text-xs text-slate-500">
                                No work experience entries loaded. Upload a PDS above or <button type="button" @click="addWorkRow(true)" class="underline text-slate-800 font-medium">add a row manually</button>.
                            </div>
                        </template>

                        <!-- Professional Highlightable Rows -->
                        <template x-if="workExperiences.length > 0">
                            <div class="divide-y divide-slate-200">
                                <template x-for="(item, index) in workExperiences" :key="item.id">
                                    <div x-show="matchesWorkFilter(item)">

                                        <!-- 1. SELECTION ROW (HIGHLIGHTED WHEN SELECTED, CLEAN WHITE WHEN UNSELECTED) -->
                                        <div
                                            x-show="!item.editing"
                                            @click="item.included = !item.included"
                                            :class="item.included
                                                ? 'bg-blue-50/75 hover:bg-blue-50'
                                                : 'bg-white hover:bg-slate-50'"
                                            class="px-3.5 py-2.5 flex items-center justify-between gap-3 cursor-pointer select-none transition-colors"
                                        >
                                            <div class="flex items-start gap-2.5 min-w-0 flex-1">
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

                                            <!-- Right Side: Direct Years Input + Edit/Delete -->
                                            <div class="flex items-center gap-1.5 shrink-0" @click.stop>
                                                <div
                                                    :class="item.included ? 'border-blue-300 bg-white' : 'border-slate-200 bg-slate-50'"
                                                    class="flex items-center border rounded px-1.5 py-0.5 focus-within:border-blue-600"
                                                >
                                                    <input
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        x-model.number="item.computed_years"
                                                        class="w-10 text-right font-mono text-xs font-semibold tabular-nums text-slate-900 bg-transparent focus:outline-none"
                                                        title="Edit Computed Years"
                                                    >
                                                    <span class="ml-1 text-[10px] font-mono text-slate-500">yrs</span>
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

                                            <div class="flex items-center justify-between pt-1">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-[10px] font-semibold uppercase text-slate-500">Years:</label>
                                                    <input
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        x-model.number="item.computed_years"
                                                        class="w-20 font-mono text-xs font-semibold tabular-nums text-slate-900 bg-white border border-slate-300 rounded px-2 py-1 focus:border-slate-900 focus:outline-none"
                                                    >
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
                    </section>
                </div>

                <!-- =========================================================== -->
                <!-- RIGHT COLUMN (55% width, sticky): SIMPLE EVALUATION TABLES  -->
                <!-- =========================================================== -->
                <div class="w-full lg:w-[55%] lg:sticky lg:top-16 print-sheet">
                    <section class="bg-white border border-slate-300 rounded-md shadow-2xs p-6 sm:p-7 print-sheet space-y-6">

                        <!-- =================================================== -->
                        <!-- TABLE I: RELEVANT TRAINING                          -->
                        <!-- =================================================== -->
                        <div>
                            <div class="flex items-baseline justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">
                                    Relevant Training
                                </h3>
                                <span class="no-print text-[11px] font-mono text-slate-500 tabular-nums">
                                    <span x-text="selectedTrainings.length"></span> item(s) included
                                </span>
                            </div>

                            <table class="w-full border-collapse border border-slate-400 text-xs print-table">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900">
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold w-7">#</th>
                                        <th class="border border-slate-400 px-2.5 py-1.5 text-left font-semibold">Title of Learning &amp; Development / Training</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-left font-semibold">Conducted / Sponsored By</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold whitespace-nowrap">Inclusive Dates (From – To)</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold whitespace-nowrap">Type</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-right font-semibold whitespace-nowrap">Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="selectedTrainings.length === 0">
                                        <tr>
                                            <td colspan="6" class="border border-slate-400 px-3 py-4 text-center text-slate-400">
                                                No relevant training items selected.
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="(item, idx) in selectedTrainings" :key="item.id">
                                        <tr>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center font-mono text-[11px] text-slate-500 tabular-nums" x-text="idx + 1"></td>
                                            <td class="border border-slate-400 px-2.5 py-1.5 font-medium text-slate-900" x-text="item.training_title || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-slate-700" x-text="item.conducted_by || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center font-mono text-[11px] text-slate-700 tabular-nums whitespace-nowrap">
                                                <span x-text="formatDateRange(item.date_from, item.date_to)"></span>
                                            </td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center text-[11px] text-slate-700 whitespace-nowrap" x-text="item.type_of_ld || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-right font-mono font-semibold text-slate-900 tabular-nums whitespace-nowrap" x-text="formatHours(item.hours)"></td>
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
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- =================================================== -->
                        <!-- TABLE II: RELEVANT WORK EXPERIENCE                  -->
                        <!-- =================================================== -->
                        <div>
                            <div class="flex items-baseline justify-between mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">
                                    Relevant Work Experience
                                </h3>
                                <span class="no-print text-[11px] font-mono text-slate-500 tabular-nums">
                                    <span x-text="selectedWorkExperiences.length"></span> position(s) included
                                </span>
                            </div>

                            <table class="w-full border-collapse border border-slate-400 text-xs print-table">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900">
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold w-7">#</th>
                                        <th class="border border-slate-400 px-2.5 py-1.5 text-left font-semibold">Position Title</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-left font-semibold">Department / Agency / Company</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold whitespace-nowrap">Inclusive Dates (From – To)</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-center font-semibold whitespace-nowrap">Status / Gov't</th>
                                        <th class="border border-slate-400 px-2 py-1.5 text-right font-semibold whitespace-nowrap">Years</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="selectedWorkExperiences.length === 0">
                                        <tr>
                                            <td colspan="6" class="border border-slate-400 px-3 py-4 text-center text-slate-400">
                                                No relevant work experience items selected.
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="(item, idx) in selectedWorkExperiences" :key="item.id">
                                        <tr>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center font-mono text-[11px] text-slate-500 tabular-nums" x-text="idx + 1"></td>
                                            <td class="border border-slate-400 px-2.5 py-1.5 font-medium text-slate-900" x-text="item.position_title || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-slate-700" x-text="item.department_agency_company || '—'"></td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center font-mono text-[11px] text-slate-700 tabular-nums whitespace-nowrap">
                                                <span x-text="formatDateRange(item.date_from, item.date_to)"></span>
                                            </td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-center text-[11px] text-slate-700 whitespace-nowrap">
                                                <span x-text="(item.status_of_appointment || '—') + ' (' + (item.gov_service || 'N') + ')'"></span>
                                            </td>
                                            <td class="border border-slate-400 px-2 py-1.5 text-right font-mono font-semibold text-slate-900 tabular-nums whitespace-nowrap" x-text="formatYears(item.computed_years)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-100 font-bold text-slate-900">
                                        <td colspan="5" class="border border-slate-400 px-2.5 py-2 text-right text-xs">
                                            Total no. of years
                                        </td>
                                        <td class="border border-slate-400 px-2 py-2 text-right font-mono text-xs tabular-nums">
                                            <span x-text="formattedTotalWorkYears"></span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </section>
                </div>

            </div>
        </main>
    </div>

    <!-- ======================================================================= -->
    <!-- ALPINE.JS STATE, MULTI-PAGE PDF/OCR ENGINE & GEMINI TURBO SCANNER       -->
    <!-- ======================================================================= -->
    <script>
        function pdsAutoComputeApp(config = {}) {
            const STORAGE_KEY = 'pds_autocompute_session_v1';

            return {
                passcodeEnabled: Boolean(config.passcodeEnabled),
                applicantName: '',
                targetPosition: '',
                trainings: [],
                workExperiences: [],
                trainingSearch: '',
                workSearch: '',
                scanEngine: 'hybrid', // 'hybrid' | 'local' | 'gemini'
                appendOnScan: true,
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
                    this.$watch('scanEngine', () => this.saveToSessionStorage());

                    this.refreshIcons();
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

                get hasAnyData() {
                    return this.trainings.length > 0 ||
                        this.workExperiences.length > 0 ||
                        this.applicantName.trim() !== '' ||
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

                get totalWorkYears() {
                    return this.selectedWorkExperiences.reduce((sum, item) => {
                        const val = parseFloat(item.computed_years);
                        return sum + (Number.isFinite(val) && val > 0 ? val : 0);
                    }, 0);
                },

                get formattedTotalWorkYears() {
                    return this.totalWorkYears.toFixed(1);
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
                    const row = {
                        id: this.generateId('wk'),
                        position_title: prefill.position_title ?? '',
                        department_agency_company: prefill.department_agency_company ?? '',
                        date_from: prefill.date_from ?? '',
                        date_to: prefill.date_to ?? 'PRESENT',
                        status_of_appointment: prefill.status_of_appointment ?? 'COSW',
                        gov_service: prefill.gov_service ?? 'N',
                        computed_years: prefill.computed_years ?? 0.0,
                        included: prefill.included ?? true,
                        editing: Boolean(openEditor)
                    };
                    if (prefill.computed_years === undefined && row.date_from) {
                        row.computed_years = this.computeYearsBetween(row.date_from, row.date_to, 0.0);
                    }
                    this.workExperiences.push(row);
                },

                removeWorkRow(index) {
                    this.workExperiences.splice(index, 1);
                },

                recomputeRowYears(item) {
                    item.computed_years = this.computeYearsBetween(item.date_from, item.date_to, item.computed_years);
                },

                computeYearsBetween(dateFromStr, dateToStr, fallback = 0.0) {
                    const start = this.parseDMY(dateFromStr);
                    if (!start) {
                        const num = parseFloat(fallback);
                        return Number.isFinite(num) ? Number(num.toFixed(1)) : 0.0;
                    }

                    let end = null;
                    const cleanTo = String(dateToStr || '').trim().toUpperCase();
                    if (['PRESENT', 'TO DATE', 'CURRENT', 'NOW', 'ONGOING'].includes(cleanTo)) {
                        end = new Date();
                        end.setHours(0, 0, 0, 0);
                    } else {
                        end = this.parseDMY(dateToStr);
                    }

                    if (!end || end < start) {
                        return 0.0;
                    }

                    const diffMs = end.getTime() - start.getTime();
                    const inclusiveDays = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
                    return Number((inclusiveDays / 365.25).toFixed(1));
                },

                parseDMY(str) {
                    if (!str) return null;
                    const clean = String(str).trim().replace(/[-.]/g, '/');
                    const parts = clean.split('/');
                    if (parts.length !== 3) return null;

                    let day = parseInt(parts[0], 10);
                    let month = parseInt(parts[1], 10);
                    let year = parseInt(parts[2], 10);

                    if (parts[0].length === 4) {
                        year = parseInt(parts[0], 10);
                        month = parseInt(parts[1], 10);
                        day = parseInt(parts[2], 10);
                    } else if (month > 12 && day <= 12) {
                        const tmp = day;
                        day = month;
                        month = tmp;
                    }

                    if (year < 100) {
                        year += year > 50 ? 1900 : 2000;
                    }

                    if (!year || !month || !day || month < 1 || month > 12 || day < 1 || day > 31) {
                        return null;
                    }

                    const dt = new Date(year, month - 1, day);
                    dt.setHours(0, 0, 0, 0);
                    return Number.isNaN(dt.getTime()) ? null : dt;
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

                clearAll() {
                    if (this.hasAnyData) {
                        const confirmed = window.confirm('Clear all entries from the current workspace?');
                        if (!confirmed) return;
                    }
                    this.trainings = [];
                    this.workExperiences = [];
                    this.applicantName = '';
                    this.targetPosition = '';
                    this.trainingSearch = '';
                    this.workSearch = '';
                    this.scanNotice = { type: 'info', message: '' };
                    sessionStorage.removeItem(STORAGE_KEY);
                },

                printEvaluationSheet() {
                    window.print();
                },

                async lockWorkspace() {
                    if (this.hasAnyData) {
                        const confirmed = window.confirm('Lock workspace and clear current session data?');
                        if (!confirmed) return;
                    }
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
                    this.isScanning = true;
                    this.scanCancelled = false;
                    this.scanningFileName = files.length === 1 ? files[0].name : `${files.length} files`;
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
                        let pageImageBlobs = [];
                        let pageCountScanned = 0;

                        for (const file of files) {
                            if (this.scanCancelled) return;
                            const ext = (file.name.split('.').pop() || '').toLowerCase();
                            const isPdf = ext === 'pdf' || file.type === 'application/pdf';

                            if (isPdf && window.pdfjsLib) {
                                this.scanStageLabel = 'Reading PDF';
                                const pdfResult = await this.inspectAllPdfPages(file);
                                pageCountScanned += pdfResult.numPages;

                                if (pdfResult.text.trim()) {
                                    combinedTextBlocks.push(pdfResult.text);
                                }
                                if (pdfResult.renderedPageBlobs.length > 0) {
                                    pageImageBlobs.push(...pdfResult.renderedPageBlobs);
                                }
                            } else if (!isPdf) {
                                pageCountScanned += 1;
                                this.scanStageLabel = 'Optimizing page';
                                const compressed = await this.compressImageForFastUpload(file);
                                pageImageBlobs.push(compressed);
                            }
                        }

                        if (this.scanCancelled) return;

                        const fullExtractedText = combinedTextBlocks.join('\n');

                        // 1. LOCAL OCR / PDF ONLY MODE
                        if (this.scanEngine === 'local') {
                            let ocrText = '';
                            if (pageImageBlobs.length > 0 && window.Tesseract) {
                                this.scanStageLabel = 'Local OCR';
                                ocrText = await this.runTesseractOcrOnBlobs(pageImageBlobs);
                            }
                            if (this.scanCancelled) return;

                            const combinedLocalText = [fullExtractedText, ocrText].filter(Boolean).join('\n');
                            const localParsed = this.parseCSForm212TextLocally(combinedLocalText);
                            const localCount = localParsed.trainings.length + localParsed.work_experience.length;

                            if (localCount > 0) {
                                this.applyExtractedRows(localParsed.trainings, localParsed.work_experience);
                                this.scanNotice = {
                                    type: 'info',
                                    message: `Local Parser (${this.scanElapsed.toFixed(1)}s across ${pageCountScanned} page(s)): Extracted ${localParsed.trainings.length} training(s) and ${localParsed.work_experience.length} work experience item(s).`
                                };
                            } else {
                                this.scanNotice = {
                                    type: 'error',
                                    message: `Local Parser finished (${this.scanElapsed.toFixed(1)}s), but no rows matched. Switch to "Hybrid (Multi-Page + AI Turbo)" or add rows manually.`
                                };
                            }
                            return;
                        }

                        // 2. HYBRID OR GEMINI VISION TURBO MODE
                        const localPreParsed = this.parseCSForm212TextLocally(fullExtractedText);
                        this.scanStageLabel = 'AI Multi-Page Scan';
                        this.scanSubStatus = `Analyzing ${pageCountScanned} page(s) for Work Experience & Trainings...`;

                        const aiSuccess = await this.sendMultiPageToGemini({
                            extractedText: this.scanEngine === 'hybrid' ? fullExtractedText : '',
                            pageBlobs: pageImageBlobs,
                            fallbackFiles: pageImageBlobs.length === 0 ? files : [],
                            fallbackLocal: localPreParsed,
                            pageCount: pageCountScanned
                        });

                        if (!aiSuccess && (localPreParsed.trainings.length + localPreParsed.work_experience.length) > 0) {
                            this.applyExtractedRows(localPreParsed.trainings, localPreParsed.work_experience);
                            this.scanNotice = {
                                type: 'info',
                                message: `Used local multi-page fallback: Loaded ${localPreParsed.trainings.length} training(s) and ${localPreParsed.work_experience.length} work experience item(s).`
                            };
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
                    const renderedPageBlobs = [];

                    for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                        if (this.scanCancelled) break;
                        this.scanSubStatus = `Reading PDF page ${pageNum} of ${numPages}...`;

                        const page = await pdf.getPage(pageNum);
                        const textContent = await page.getTextContent();
                        const items = textContent.items || [];

                        let pageChars = 0;
                        const rowsByY = [];

                        for (const item of items) {
                            const str = String(item.str || '').trim();
                            if (!str) continue;
                            pageChars += str.length;
                            const x = item.transform ? item.transform[4] : 0;
                            const y = item.transform ? item.transform[5] : 0;

                            // 7.5px vertical tolerance keeps slightly offset table cells in the same row
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

                        // Identify if this page is strictly Page 1 (Personal/Family/Education only) or Page 4 (Legal/References/Oath only)
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
                        }

                        // Render the page to a fast compressed JPEG if:
                        // - Engine is 'gemini', OR
                        // - This page has very little selectable text (e.g. an image/scanned page inside the PDF!), OR
                        // - It is a candidate page (Page 2 / Page 3 / Work Experience / Trainings page) and we have <= 4 rendered pages
                        const shouldRenderPageImage =
                            this.scanEngine === 'gemini'
                                ? isCandidatePage
                                : (pageChars < 180 || (isCandidatePage && renderedPageBlobs.length < 4));

                        if (shouldRenderPageImage && renderedPageBlobs.length < 5) {
                            this.scanSubStatus = `Rendering page ${pageNum} of ${numPages} for multi-page vision...`;
                            const baseViewport = page.getViewport({ scale: 1.0 });
                            const targetScale = Math.min(2.0, Math.max(1.3, 1350 / Math.max(baseViewport.width, baseViewport.height)));
                            const viewport = page.getViewport({ scale: targetScale });

                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(viewport.width);
                            canvas.height = Math.round(viewport.height);
                            const ctx = canvas.getContext('2d');
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            await page.render({ canvasContext: ctx, viewport }).promise;

                            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.84));
                            if (blob) {
                                renderedPageBlobs.push(blob);
                            }
                        }
                    }

                    return {
                        numPages,
                        text: allLines.join('\n'),
                        renderedPageBlobs
                    };
                },

                async runTesseractOcrOnBlobs(blobs) {
                    const textSegments = [];
                    for (let i = 0; i < blobs.length; i++) {
                        if (this.scanCancelled) break;
                        const pageLabel = blobs.length > 1 ? ` (Page ${i + 1}/${blobs.length})` : '';
                        const result = await window.Tesseract.recognize(blobs[i], 'eng', {
                            logger: m => {
                                if (m.status === 'recognizing text' && typeof m.progress === 'number') {
                                    const pct = Math.round(m.progress * 100);
                                    this.scanSubStatus = `Running browser OCR${pageLabel}: ${pct}% completed...`;
                                }
                            }
                        });
                        if (result?.data?.text) {
                            textSegments.push(`=== OCR PAGE ${i + 1} ===\n` + result.data.text);
                        }
                    }
                    return textSegments.join('\n');
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
                parseCSForm212TextLocally(rawText) {
                    const work_experience = [];
                    const trainings = [];
                    if (!rawText || !rawText.trim()) {
                        return { work_experience, trainings };
                    }

                    const lines = rawText
                        .split(/\r?\n/)
                        .map(l => l.replace(/\s+\|\s+/g, '\t').trim())
                        .filter(Boolean);

                    let currentSection = 'UNKNOWN';
                    let pendingTitleCandidate = '';

                    const datePattern = '\\b(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})\\b';
                    const dateOrPresentPattern = '\\b(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4}|PRESENT|TO\\s*DATE|CURRENT)\\b';

                    const workRowRegex = new RegExp(`^[^A-Za-z0-9]*(?:\\d{1,2}[\\.\\)]\\s*)?${datePattern}[\\s\\t\\-–toTO]+${dateOrPresentPattern}[\\s\\t]+(.+)$`, 'i');
                    const trainingRowRegex = new RegExp(`^(.+?)\\s+${datePattern}[\\s\\t\\-–toTO]+${datePattern}[\\s\\t]+(\\d+(?:\\.\\d+)?)[\\s\\t]*(.*)$`, 'i');
                    const splitTrainingDatesRegex = new RegExp(`^[^A-Za-z0-9]*${datePattern}[\\s\\t\\-–toTO]+${datePattern}[\\s\\t]+(\\d+(?:\\.\\d+)?)[\\s\\t]+(.+)$`, 'i');

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

                        // 1. Standard Training Row: [Title] [DateFrom] [DateTo] [Hours] [Type] [ConductedBy]
                        if (currentSection !== 'WORK') {
                            const trMatch = line.match(trainingRowRegex);
                            if (trMatch) {
                                const rawTitle = trMatch[1].replace(/\t+/g, ' ').trim();
                                const dateFrom = this.normalizeLocalDate(trMatch[2], false);
                                const dateTo = this.normalizeLocalDate(trMatch[3], false);
                                const hours = parseFloat(trMatch[4]);
                                const tail = trMatch[5].trim();

                                if (rawTitle.length >= 3 && !/^\d+$/.test(rawTitle) && Number.isFinite(hours) && hours <= 2000) {
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

                    return { work_experience, trainings };
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
                        if (fallbackLocal && (fallbackLocal.trainings.length + fallbackLocal.work_experience.length) > 0) {
                            return false;
                        }
                        this.scanNotice = {
                            type: 'error',
                            message: result.message || 'Extraction failed. Switch engine to "Local OCR / PDF Only" or add rows manually.'
                        };
                        return false;
                    }

                    let extractedTrainings = Array.isArray(result.data?.trainings) ? result.data.trainings : [];
                    let extractedWork = Array.isArray(result.data?.work_experience) ? result.data.work_experience : [];

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
                        included: true,
                        editing: false
                    }));

                    const mappedWork = rawWork.map(row => ({
                        id: this.generateId('wk'),
                        position_title: row.position_title || '',
                        department_agency_company: row.department_agency_company || '',
                        date_from: row.date_from || '',
                        date_to: row.date_to || '',
                        status_of_appointment: row.status_of_appointment || '',
                        gov_service: row.gov_service === 'Y' ? 'Y' : 'N',
                        computed_years: Number.isFinite(Number(row.computed_years))
                            ? Number(Number(row.computed_years).toFixed(1))
                            : this.computeYearsBetween(row.date_from, row.date_to, 0.0),
                        included: true,
                        editing: false
                    }));

                    if (this.appendOnScan) {
                        if (mappedTrainings.length > 0) {
                            this.trainings = this.deduplicateRows([...this.trainings, ...mappedTrainings], 'training_title');
                        }
                        if (mappedWork.length > 0) {
                            this.workExperiences = this.deduplicateRows([...this.workExperiences, ...mappedWork], 'position_title');
                        }
                    } else {
                        // Even when Append is off, only replace a section if the new scan actually contained rows for that section
                        if (mappedTrainings.length > 0 || mappedWork.length === 0) {
                            this.trainings = mappedTrainings;
                        }
                        if (mappedWork.length > 0 || mappedTrainings.length === 0) {
                            this.workExperiences = mappedWork;
                        }
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
                        const payload = {
                            applicantName: this.applicantName,
                            targetPosition: this.targetPosition,
                            scanEngine: this.scanEngine,
                            trainings: this.trainings.map(t => ({ ...t, editing: false })),
                            workExperiences: this.workExperiences.map(w => ({ ...w, editing: false }))
                        };
                        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
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
                            if (['hybrid', 'local', 'gemini'].includes(parsed.scanEngine)) {
                                this.scanEngine = parsed.scanEngine;
                            }
                            this.trainings = Array.isArray(parsed.trainings)
                                ? parsed.trainings.map(t => ({ ...t, editing: false }))
                                : [];
                            this.workExperiences = Array.isArray(parsed.workExperiences)
                                ? parsed.workExperiences.map(w => ({ ...w, editing: false }))
                                : [];
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
