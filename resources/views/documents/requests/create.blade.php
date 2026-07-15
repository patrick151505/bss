@extends('layouts.vertical', [
    'title'         => 'New Document Request',
    'sub_title'     => 'Requests',
    'sub_title_url' => route('documents.requests.index'),
    'tagline'       => 'Scan citizen ID or search by name to create a request.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('css')
<style>
/* Custom field grid span, set inline via --field-col-span (1-12) on each field wrapper */
@media (min-width: 1024px) {
    #custom-fields-container > [style*="--field-col-span"] {
        grid-column: span var(--field-col-span) / span var(--field-col-span);
    }
}
</style>
@endsection

@section('content')

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif


<form action="{{ route('documents.requests.store') }}" method="POST" id="request-form">
    @csrf
    <input type="hidden" name="citizen_id" id="citizen_id" value="{{ old('citizen_id') }}">

    <div class="grid grid-cols-12 gap-6">

        {{-- Left column --}}
        <div class="col-span-12 lg:col-span-7 flex flex-col gap-5">

            {{-- Step 1: Citizen Lookup --}}
            <div class="card p-5">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                    Find Citizen
                </h6>

                {{-- Search input --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="mgc_search_line text-gray-400"></i>
                    </div>
                    <input type="text" id="citizen-search"
                           class="form-input pl-9 pr-10"
                           placeholder="Scan QR / barcode or type name…"
                           autocomplete="off">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="mgc_qrcode_line text-gray-300 text-lg"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">
                    <i class="mgc_qrcode_line"></i> Click here once, then scan — citizen is auto-selected instantly.
                </p>

                {{-- Search results grid --}}
                <div id="citizen-dropdown" class="hidden mt-3">
                    <p class="text-xs text-gray-400 font-medium mb-2 flex items-center gap-1">
                        <i class="mgc_search_line"></i> <span id="search-results-label">Results</span>
                    </p>
                    <div id="search-results-grid" class="grid grid-cols-2 gap-2"></div>
                </div>

                {{-- Citizen info card (shown after selection) --}}
                <div id="citizen-card" class="hidden mt-3 p-3 rounded-lg border border-success/40 bg-success/5 flex items-start gap-3">
                    <div id="card-avatar" class="w-10 h-10 rounded-full bg-success/20 flex items-center justify-center shrink-0 overflow-hidden">
                        <i class="mgc_user_3_line text-success"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="card-name"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" id="card-address"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" id="card-contact"></p>
                    </div>
                    <button type="button" onclick="clearCitizen()"
                            class="text-gray-400 hover:text-danger transition shrink-0" title="Clear">
                        <i class="mgc_close_line"></i>
                    </button>
                </div>

                {{-- Recent citizens quick-select grid --}}
                @if($recentCitizens->isNotEmpty())
                <div id="recent-grid" class="mt-4">
                    <p class="text-xs text-gray-400 font-medium mb-2 flex items-center gap-1">
                        <i class="mgc_history_line"></i> Recently Updated
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($recentCitizens as $rc)
                        @php
                            $rcProfile = $rc->profile
                                ? asset(str_replace('public/', 'storage/', $rc->profile))
                                : null;
                            $rcInitial = strtoupper(substr($rc->fname ?? '?', 0, 1));
                        @endphp
                        <button type="button"
                                onclick="selectCitizen({{ $rc->id }}, '{{ addslashes($rc->full_name) }}', '{{ addslashes($rc->complete_address ?? '') }}', '{{ addslashes($rc->contact ?? '') }}', '{{ addslashes($rc->qrcode ?? '') }}', '{{ $rcProfile }}')"
                                class="recent-citizen-btn flex items-center gap-2.5 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5 transition text-left w-full group">
                            {{-- Avatar --}}
                            <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">
                                @if($rcProfile)
                                <img src="{{ $rcProfile }}" alt="{{ $rc->full_name }}"
                                     class="w-full h-full object-cover">
                                @else
                                <span class="text-sm font-bold text-primary">{{ $rcInitial }}</span>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-primary">
                                    {{ $rc->full_name }}
                                </p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    {{ $rc->complete_address ?? '—' }}
                                </p>
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            {{-- Step 2: Document Type --}}
            <div class="card p-5">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                    Document Type
                </h6>

                {{-- Real select stays in the DOM (hidden) — drives loadTypeDetails() and form submission --}}
                <select name="document_type_id" id="document_type_id" class="hidden" required
                        onchange="loadTypeDetails(this)">
                    <option value="">— Select Document Type —</option>
                    @foreach($types as $t)
                    <option value="{{ $t->id }}"
                            data-paid="{{ $t->is_paid ? '1' : '0' }}"
                            data-fee="{{ $t->fee }}"
                            data-approval="{{ $t->requires_approval ? '1' : '0' }}"
                            data-fields="{{ $t->fields->toJson() }}"
                            {{ old('document_type_id', $selectedType?->id) == $t->id ? 'selected' : '' }}>
                        {{ $t->name }}{{ $t->is_paid ? ' — ₱' . number_format($t->fee, 2) : ' — FREE' }}
                    </option>
                    @endforeach
                </select>

                {{-- Visual card picker --}}
                <div id="type-cards-picker">
                    <div id="type-cards-grid" class="grid grid-cols-1 sm:grid-cols-3 gap-3"></div>
                    <div id="type-cards-pagination" class="hidden flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" onclick="typeCardsPage(-1)" id="type-cards-prev"
                                class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-1">
                            <i class="mgc_arrow_left_line"></i> Prev
                        </button>
                        <span class="text-xs text-gray-400" id="type-cards-page-label"></span>
                        <button type="button" onclick="typeCardsPage(1)" id="type-cards-next"
                                class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-1">
                            Next <i class="mgc_arrow_right_line"></i>
                        </button>
                    </div>
                </div>

                {{-- Selected document type summary (shown after a card is picked) --}}
                <div id="selected-type-card" class="hidden p-3 rounded-lg border border-success/40 bg-success/5 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-success/20 flex items-center justify-center shrink-0">
                        <i class="mgc_document_2_line text-success"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="selected-type-name"></p>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap" id="selected-type-badges"></div>
                    </div>
                    <button type="button" onclick="clearTypeSelection()"
                            class="text-gray-400 hover:text-danger transition shrink-0" title="Change document type">
                        <i class="mgc_close_line"></i>
                    </button>
                </div>

                {{-- Fee / approval notices --}}
                <div id="fee-info" class="hidden mt-3 p-3 rounded-lg bg-warning/10 border border-warning/30">
                    <p class="text-sm text-warning font-medium flex items-center gap-2">
                        <i class="mgc_wallet_3_line"></i>
                        Fee: <strong id="fee-display"></strong>
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">Official receipt number will be collected upon release.</p>
                </div>
                <div id="free-info" class="hidden mt-3 p-3 rounded-lg bg-success/10 border border-success/30">
                    <p class="text-sm text-success font-medium flex items-center gap-2">
                        <i class="mgc_check_circle_line"></i> This document is FREE
                    </p>
                </div>
                <div id="approval-notice" class="hidden mt-3 p-3 rounded-lg bg-info/10 border border-info/30">
                    <p class="text-sm text-info flex items-center gap-2">
                        <i class="mgc_shield_check_line"></i> This document requires approval before release.
                    </p>
                </div>

                {{-- Dynamic Custom Fields --}}
                <div id="custom-fields-section" class="hidden mt-4 space-y-4">
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                            <i class="mgc_list_check_2_line text-purple-500"></i> Additional Information
                        </h6>
                        <div id="custom-fields-container" class="grid grid-cols-12 gap-3"></div>
                    </div>
                </div>
            </div>

            {{-- Step 3: Remarks + Submit --}}
            <div class="card p-5">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
                    Remarks
                </h6>
                <textarea name="remarks" rows="2" class="form-input"
                          placeholder="Any notes about this request… (optional)">{{ old('remarks') }}</textarea>

                <div class="flex gap-3 pt-4">
                    <a href="{{ route('documents.requests.index') }}"
                       class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">Cancel</a>
                    <button type="button" id="submit-btn" onclick="openRequestReview()"
                            class="btn bg-primary text-white flex-1 flex items-center justify-center gap-2">
                        <i class="mgc_eye_2_line"></i> Review &amp; Submit
                    </button>
                </div>
            </div>

        </div>

        {{-- Right: Workflow guide --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-5">

            {{-- Past Document Requests --}}
            <div id="history-requests-card" class="card p-5 hidden">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <i class="mgc_document_line text-primary"></i> Past Document Requests
                </h6>
                <div id="history-requests-body"></div>
            </div>

            {{-- Blotter History --}}
            <div id="history-blotters-card" class="card p-5 hidden">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <i class="mgc_alert_line text-danger"></i> Blotter Records
                </h6>
                <div id="history-blotters-body"></div>
            </div>

            <div class="card p-5">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <i class="mgc_information_line text-info"></i> Request Workflow
                </h6>
                <ol class="space-y-3">
                    <li class="flex gap-3">
                        <div class="w-6 h-6 rounded-full bg-warning/20 text-warning text-xs flex items-center justify-center font-bold shrink-0 mt-0.5">1</div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pending</p>
                            <p class="text-xs text-gray-400">Request submitted, waiting for action.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <div class="w-6 h-6 rounded-full bg-info/20 text-info text-xs flex items-center justify-center font-bold shrink-0 mt-0.5">2</div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Approved</p>
                            <p class="text-xs text-gray-400">Admin verifies and approves.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <div class="w-6 h-6 rounded-full bg-success/20 text-success text-xs flex items-center justify-center font-bold shrink-0 mt-0.5">3</div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Released</p>
                            <p class="text-xs text-gray-400">Document printed and released. OR number recorded if paid.</p>
                        </div>
                    </li>
                </ol>

                <div class="mt-5 p-3 rounded-lg bg-primary/5 border border-primary/20">
                    <p class="text-xs text-primary font-medium flex items-center gap-1.5 mb-1">
                        <i class="mgc_qrcode_line"></i> Scanner Tip
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Click the search box, then scan the QR or barcode on the citizen's ID with a handheld scanner.
                        The citizen is <strong>auto-selected instantly</strong> — no need to press anything.
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>

{{-- Review & Confirm Modal --}}
<div id="request-review-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60" onclick="if(event.target===this)closeRequestReview()">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col w-full max-w-3xl mx-4" style="max-height:90vh;">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_eye_2_line text-primary"></i> Review Request
            </h6>
            <button type="button" onclick="closeRequestReview()"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-500 text-sm py-1.5 px-3">
                <i class="mgc_close_line"></i>
            </button>
        </div>

        <div class="overflow-y-auto flex-1">

            {{-- Loading state --}}
            <div id="review-loading" class="p-10 flex flex-col items-center justify-center gap-2 text-gray-400">
                <svg class="animate-spin w-6 h-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Rendering preview…
            </div>

            {{-- Error state (e.g. required custom field missing) --}}
            <div id="review-errors" class="hidden p-5">
                <div class="p-4 rounded-lg bg-danger/10 border border-danger/30">
                    <p class="text-sm font-medium text-danger mb-1">Please fix the following before submitting:</p>
                    <ul id="review-errors-list" class="list-disc list-inside text-sm text-danger/80 space-y-0.5"></ul>
                </div>
            </div>

            {{-- Certificate preview --}}
            <div id="review-body-wrap" class="hidden bg-gray-100 dark:bg-gray-800/50 p-6">
                <div id="review-paper"
                     style="width:8.5in; min-width:8.5in; margin:0 auto; background:#fff; border:1px solid #ccc;
                            box-sizing:border-box; box-shadow:0 0 10px rgba(0,0,0,0.1); font-family:'Times New Roman', Times, serif;
                            font-size:14px; line-height:1.8; color:#111827;">
                </div>
            </div>

            {{-- Confirmation summary --}}
            <div id="review-summary" class="hidden p-5 border-t border-gray-200 dark:border-gray-700 space-y-3">
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Confirm Request Details</h6>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-400">Citizen</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100" id="review-citizen-name"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Document Type</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100" id="review-document-type"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Fee</p>
                        <p class="font-medium" id="review-fee"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Approval</p>
                        <p class="font-medium" id="review-approval"></p>
                    </div>
                </div>
                <div id="review-fields-wrap" class="hidden">
                    <p class="text-xs text-gray-400 mb-1">Additional Information</p>
                    <div id="review-fields-list" class="text-sm text-gray-700 dark:text-gray-200 space-y-1"></div>
                </div>
                <div id="review-remarks-wrap" class="hidden">
                    <p class="text-xs text-gray-400">Remarks</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200" id="review-remarks"></p>
                </div>

                <label class="flex items-center gap-2 pt-2 cursor-pointer">
                    <input type="checkbox" id="review-confirm-checkbox" class="form-checkbox" onchange="document.getElementById('review-confirm-btn').disabled = !this.checked">
                    <span class="text-sm text-gray-600 dark:text-gray-300">I've reviewed the details above and they are correct.</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
            <button type="button" onclick="closeRequestReview()"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                Back to Edit
            </button>
            <button type="button" id="review-confirm-btn" disabled onclick="confirmRequestSubmit()"
                    class="btn bg-primary text-white flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="mgc_check_circle_line"></i> Confirm &amp; Submit
            </button>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
const SEARCH_URL   = '{{ route('citizens.search') }}';
const HISTORY_BASE = '{{ url('citizens') }}';
const RESOLVE_DEFAULTS_URL = '{{ route('documents.requests.resolve-defaults') }}';
const oldValues    = @json(old());

let searchTimer   = null;
let selectedId    = '{{ old('citizen_id') }}';

// ── Citizen search ────────────────────────────────────────────────────
const searchInput = document.getElementById('citizen-search');
const dropdown    = document.getElementById('citizen-dropdown');

// Physical QR/barcode scanner detection
// Scanners type very fast (< 50ms per char) then send Enter.
// We track a scannerActive flag to suppress the normal input→dropdown path.
let lastKeyTime   = 0;
let scannerBuffer = '';
let scannerTimer  = null;
let scannerActive = false; // true while a scanner sequence is in progress

function doSearch(q, autoSelect) {
    if (q.length < 2) { dropdown.classList.add('hidden'); return; }

    if (autoSelect) {
        // QR/scanner path: exact qrcode match preferred, fallback to first result.
        // No dropdown shown — citizen is selected instantly.
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(results => {
                if (!results.length) return; // no match, do nothing
                const hit = results.find(c => c.qrcode === q) ?? results[0];
                dropdown.classList.add('hidden');
                selectCitizen(hit.id, hit.name, hit.address, hit.contact, hit.qrcode, hit.profile ?? '');
            });
        return;
    }

    // Manual typing path: show results grid
    const grid  = document.getElementById('search-results-grid');
    const label = document.getElementById('search-results-label');
    const skeletonCard = `
        <div class="flex items-center gap-2.5 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 animate-pulse">
            <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0"></div>
            <div class="flex-1 min-w-0 space-y-1.5">
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            </div>
        </div>`;
    grid.innerHTML = skeletonCard.repeat(4);
    dropdown.classList.remove('hidden');
    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(results => {
            if (!results.length) {
                grid.innerHTML = '<p class="col-span-2 text-xs text-gray-400 py-1">No citizen found.</p>';
                label.textContent = 'Results';
                return;
            }
            label.textContent = results.length + ' result' + (results.length > 1 ? 's' : '');
            grid.innerHTML = results.map(c => {
                const initial = escHtml(c.name.trim()[0] ?? '?').toUpperCase();
                const avatar  = c.profile
                    ? `<img src="${escHtml(c.profile)}" alt="${escHtml(c.name)}" class="w-full h-full object-cover">`
                    : `<span class="text-sm font-bold text-primary">${initial}</span>`;
                return `
                <button type="button"
                        onclick="selectCitizen(${c.id}, '${escHtml(c.name)}', '${escHtml(c.address)}', '${escHtml(c.contact)}', '${escHtml(c.qrcode)}', '${escHtml(c.profile ?? '')}')"
                        class="flex items-center gap-2.5 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5 transition text-left w-full group">
                    <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">
                        ${avatar}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-primary">${escHtml(c.name)}</p>
                        <p class="text-[10px] text-gray-400 truncate">${escHtml(c.address) || '—'}</p>
                    </div>
                    ${c.qrcode ? '<i class="mgc_qrcode_line text-gray-300 text-xs shrink-0"></i>' : ''}
                </button>`;
            }).join('');
            dropdown.classList.remove('hidden');
        });
}

searchInput.addEventListener('keydown', function (e) {
    const now = Date.now();
    const gap = now - lastKeyTime;
    lastKeyTime = now;

    if (e.key === 'Enter') {
        e.preventDefault();
        if (scannerBuffer.length >= 2) {
            const q = scannerBuffer.trim();
            clearTimeout(scannerTimer);
            scannerBuffer = '';
            scannerActive = false;
            clearTimeout(searchTimer);
            doSearch(q, true);
        }
        return;
    }

    if (e.key.length === 1 && gap < 50) {
        // Fast keystroke — scanner input
        scannerActive = true;
        scannerBuffer += e.key;
        clearTimeout(scannerTimer);
        // Some scanners don't send Enter; fire after 150ms silence
        scannerTimer = setTimeout(() => {
            const q = scannerBuffer.trim();
            scannerBuffer = '';
            scannerActive = false;
            if (q.length >= 2) doSearch(q, true);
        }, 150);
    } else {
        // Slow keystroke — human typing; cancel any scanner sequence
        if (scannerActive) {
            clearTimeout(scannerTimer);
            scannerBuffer = '';
            scannerActive = false;
        }
    }
});

searchInput.addEventListener('input', function () {
    // Suppress normal dropdown search while scanner is actively typing
    if (scannerActive) return;
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) { dropdown.classList.add('hidden'); return; }
    // QR/barcode heuristic: long string with no spaces → auto-select, no dropdown
    const isQrLike = q.length > 12 && !q.includes(' ');
    searchTimer = setTimeout(() => doSearch(q, isQrLike), isQrLike ? 150 : 300);
});

document.addEventListener('click', function (e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

function selectCitizen(id, name, address, contact, qrcode, profileUrl) {
    selectedId = id;
    document.getElementById('citizen_id').value = id;
    searchInput.value = '';
    dropdown.classList.add('hidden');
    document.getElementById('search-results-grid').innerHTML = '';

    document.getElementById('card-name').textContent    = name;
    document.getElementById('card-address').textContent = address || '—';
    document.getElementById('card-contact').textContent = contact ? ('📞 ' + contact) : '';

    // Show profile photo if available
    const avatar = document.getElementById('card-avatar');
    if (profileUrl) {
        avatar.innerHTML = `<img src="${profileUrl}" alt="${escHtml(name)}" class="w-full h-full object-cover">`;
    } else {
        avatar.innerHTML = `<i class="mgc_user_3_line text-success"></i>`;
    }

    document.getElementById('citizen-card').classList.remove('hidden');

    // Hide recent grid — a citizen is already chosen
    const grid = document.getElementById('recent-grid');
    if (grid) grid.classList.add('hidden');

    // Highlight selected card in recent grid
    document.querySelectorAll('.recent-citizen-btn').forEach(btn => btn.classList.remove('border-primary', 'bg-primary/5'));
    const match = [...document.querySelectorAll('.recent-citizen-btn')]
        .find(btn => btn.getAttribute('onclick').startsWith(`selectCitizen(${id},`));
    if (match) match.classList.add('border-primary', 'bg-primary/5');

    loadCitizenHistory(id);

    // Re-resolve custom field defaults now that we know the citizen
    // (covers the case where a document type was already selected first).
    const typeSelect = document.getElementById('document_type_id');
    if (typeSelect.value) resolveCustomFieldDefaults(typeSelect.value);
}

function clearCitizen() {
    selectedId = '';
    document.getElementById('citizen_id').value = '';
    searchInput.value = '';
    document.getElementById('citizen-card').classList.add('hidden');
    const grid = document.getElementById('recent-grid');
    if (grid) grid.classList.remove('hidden');
    document.querySelectorAll('.recent-citizen-btn').forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary/5');
    });
    // Hide history cards
    document.getElementById('history-requests-card').classList.add('hidden');
    document.getElementById('history-blotters-card').classList.add('hidden');
    searchInput.focus();
}

// ── Citizen quick history ─────────────────────────────────────────────
const skeletonRow = `
    <div class="flex items-center gap-3 py-2 animate-pulse">
        <div class="flex-1 space-y-1.5">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
            <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
        </div>
        <div class="h-5 w-14 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
    </div>`;

function loadCitizenHistory(id) {
    const reqCard  = document.getElementById('history-requests-card');
    const reqBody  = document.getElementById('history-requests-body');
    const bltCard  = document.getElementById('history-blotters-card');
    const bltBody  = document.getElementById('history-blotters-body');

    // Show cards with skeleton while loading
    reqBody.innerHTML = skeletonRow.repeat(3);
    bltBody.innerHTML = skeletonRow.repeat(2);
    reqCard.classList.remove('hidden');
    bltCard.classList.remove('hidden');

    fetch(`${HISTORY_BASE}/${id}/quick-history`)
        .then(r => r.json())
        .then(({ requests, blotters }) => {
            // Document requests
            if (!requests.length) {
                reqBody.innerHTML = '<p class="text-xs text-gray-400 py-1">No document requests found.</p>';
            } else {
                reqBody.innerHTML = requests.map(r => `
                    <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${escHtml(r.type)}</p>
                            <p class="text-xs text-gray-400">${escHtml(r.date)}</p>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-${escHtml(r.color)}/10 text-${escHtml(r.color)} shrink-0">
                            ${escHtml(r.label)}
                        </span>
                    </div>`).join('');
            }

            // Blotters
            if (!blotters.length) {
                bltBody.innerHTML = '<p class="text-xs text-gray-400 py-1">No blotter records found.</p>';
            } else {
                bltBody.innerHTML = blotters.map(b => `
                    <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${escHtml(b.no)} — ${escHtml(b.type)}</p>
                            <p class="text-xs text-gray-400">${escHtml(b.date)}</p>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full ${escHtml(b.color)} shrink-0">
                            ${escHtml(b.label)}
                        </span>
                    </div>`).join('');
            }
        })
        .catch(() => {
            reqBody.innerHTML = '<p class="text-xs text-danger py-1">Failed to load history.</p>';
            bltBody.innerHTML = '<p class="text-xs text-danger py-1">Failed to load records.</p>';
        });
}

// ── Document Type card picker ──────────────────────────────────────────
const TYPE_CARDS_PER_PAGE = 9;
let typeCardsPageIndex = 0;

function renderTypeCards() {
    const select = document.getElementById('document_type_id');
    const grid    = document.getElementById('type-cards-grid');
    const pager   = document.getElementById('type-cards-pagination');
    const options = Array.from(select.options).filter(o => o.value !== '');

    const totalPages = Math.max(1, Math.ceil(options.length / TYPE_CARDS_PER_PAGE));
    typeCardsPageIndex = Math.min(Math.max(typeCardsPageIndex, 0), totalPages - 1);

    const start = typeCardsPageIndex * TYPE_CARDS_PER_PAGE;
    const pageOptions = options.slice(start, start + TYPE_CARDS_PER_PAGE);

    grid.innerHTML = pageOptions.map(opt => {
        const isPaid    = opt.dataset.paid === '1';
        const approval  = opt.dataset.approval === '1';
        const fee       = parseFloat(opt.dataset.fee || 0);
        const isChecked = opt.value === select.value;
        const feeLabel  = isPaid ? '₱ ' + fee.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : 'FREE';
        const feeClass  = isPaid ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success';

        return `
            <button type="button"
                    onclick="selectTypeCard('${opt.value}')"
                    class="type-card text-left p-3 rounded-lg border-2 transition ${isChecked ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700 hover:border-primary/40'}"
                    data-type-value="${opt.value}">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">${escHtml(opt.text.split(' — ')[0])}</p>
                <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded ${feeClass}">${feeLabel}</span>
                    ${approval ? '<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-info/10 text-info">Needs approval</span>' : ''}
                </div>
            </button>`;
    }).join('');

    pager.classList.toggle('hidden', totalPages <= 1);
    document.getElementById('type-cards-page-label').textContent = `Page ${typeCardsPageIndex + 1} of ${totalPages}`;
    document.getElementById('type-cards-prev').disabled = typeCardsPageIndex === 0;
    document.getElementById('type-cards-next').disabled = typeCardsPageIndex === totalPages - 1;
    document.getElementById('type-cards-prev').classList.toggle('opacity-40', typeCardsPageIndex === 0);
    document.getElementById('type-cards-next').classList.toggle('opacity-40', typeCardsPageIndex === totalPages - 1);
}

function typeCardsPage(delta) {
    typeCardsPageIndex += delta;
    renderTypeCards();
}

function selectTypeCard(value) {
    const select = document.getElementById('document_type_id');
    select.value = value;
    loadTypeDetails(select);
    renderTypeCards();
    showSelectedTypeSummary();
}

function showSelectedTypeSummary() {
    const select = document.getElementById('document_type_id');
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const isPaid   = opt.dataset.paid === '1';
    const approval = opt.dataset.approval === '1';
    const fee      = parseFloat(opt.dataset.fee || 0);
    const feeLabel = isPaid ? '₱ ' + fee.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : 'FREE';
    const feeClass = isPaid ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success';

    document.getElementById('selected-type-name').textContent = opt.text.split(' — ')[0];
    document.getElementById('selected-type-badges').innerHTML = `
        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded ${feeClass}">${feeLabel}</span>
        ${approval ? '<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-info/10 text-info">Needs approval</span>' : ''}
    `;

    document.getElementById('type-cards-picker').classList.add('hidden');
    document.getElementById('selected-type-card').classList.remove('hidden');
}

function clearTypeSelection() {
    const select = document.getElementById('document_type_id');
    select.value = '';
    loadTypeDetails(select);

    document.getElementById('selected-type-card').classList.add('hidden');
    document.getElementById('type-cards-picker').classList.remove('hidden');
    renderTypeCards();
}

// ── Document Type details ─────────────────────────────────────────────
function loadTypeDetails(select) {
    const opt      = select.options[select.selectedIndex];
    const isPaid   = opt.dataset.paid === '1';
    const fee      = parseFloat(opt.dataset.fee || 0);
    const approval = opt.dataset.approval === '1';
    const fields   = JSON.parse(opt.dataset.fields || '[]');

    document.getElementById('fee-info').classList.toggle('hidden', !isPaid);
    document.getElementById('free-info').classList.toggle('hidden', isPaid || !opt.value);
    document.getElementById('approval-notice').classList.toggle('hidden', !approval);

    if (isPaid) {
        document.getElementById('fee-display').textContent =
            '₱ ' + fee.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    }

    const section   = document.getElementById('custom-fields-section');
    const container = document.getElementById('custom-fields-container');
    container.innerHTML = '';

    if (!fields.length) { section.classList.add('hidden'); return; }
    section.classList.remove('hidden');

    fields.forEach(field => {
        // Raw default_value may contain unresolved placeholder tags — only use it here
        // if there's no old (resubmitted) value; resolveCustomFieldDefaults()
        // will replace it with the real resolved value once the citizen is known.
        const oldVal = oldValues['field_' + field.field_key] || '';
        const req    = field.is_required ? '<span class="text-danger">*</span>' : '';
        const colSpan = Math.max(1, Math.min(12, parseInt(field.column_width) || 12));
        let   input  = '';

        if (field.field_type === 'textarea') {
            input = `<textarea name="field_${field.field_key}" rows="2" class="form-input"
                              ${field.is_required ? 'required' : ''}>${oldVal}</textarea>`;
        } else if (field.field_type === 'select') {
            const opts = (field.field_options || []).map(o =>
                `<option value="${o}" ${oldVal === o ? 'selected' : ''}>${o}</option>`
            ).join('');
            input = `<select name="field_${field.field_key}" class="form-select" ${field.is_required ? 'required' : ''}>
                        <option value="">— Select —</option>${opts}
                     </select>`;
        } else if (field.field_type === 'date') {
            input = `<input type="date" name="field_${field.field_key}" class="form-input"
                            value="${oldVal}" ${field.is_required ? 'required' : ''}>`;
        } else {
            input = `<input type="text" name="field_${field.field_key}" class="form-input"
                            value="${oldVal}" ${field.is_required ? 'required' : ''}>`;
        }

        container.insertAdjacentHTML('beforeend', `
            <div class="col-span-12" style="--field-col-span:${colSpan}">
                <label class="form-label text-sm">${escHtml(field.field_label)} ${req}</label>
                ${input}
            </div>
        `);
    });

    resolveCustomFieldDefaults(opt.value);
}

// Fetch server-resolved default values (placeholder tags replaced with
// the selected citizen's real data) and fill in any custom
// field the staff hasn't typed into yet.
function resolveCustomFieldDefaults(documentTypeId) {
    if (!documentTypeId) return;

    const params = new URLSearchParams({ document_type_id: documentTypeId });
    if (selectedId) params.set('citizen_id', selectedId);

    fetch(RESOLVE_DEFAULTS_URL + '?' + params.toString())
        .then(r => r.json())
        .then(resolved => {
            Object.entries(resolved).forEach(([key, value]) => {
                const el = document.querySelector(`#custom-fields-container [name="field_${key}"]`);
                if (!el) return;
                // Don't clobber anything the citizen already typed, or a resubmitted old value.
                if (el.value.trim() !== '') return;
                el.value = value;
            });
        })
        .catch(() => {});
}

// ── Helpers ───────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Review & Confirm Modal ──────────────────────────────────────────────
const PREVIEW_URL = '{{ route('documents.requests.preview') }}';
const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;

function openRequestReview() {
    const form = document.getElementById('request-form');
    if (!form.reportValidity()) return; // let native required-field validation run first

    if (!selectedId) {
        Swal.fire({
            icon: 'warning',
            title: 'No citizen selected',
            text: 'Please select a citizen before submitting.',
            didOpen: () => {
                document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#727cf5', 'important');
            },
        });
        searchInput.focus();
        return;
    }

    document.getElementById('request-review-modal').classList.remove('hidden');
    document.getElementById('review-loading').classList.remove('hidden');
    document.getElementById('review-errors').classList.add('hidden');
    document.getElementById('review-body-wrap').classList.add('hidden');
    document.getElementById('review-summary').classList.add('hidden');
    document.getElementById('review-confirm-checkbox').checked = false;
    document.getElementById('review-confirm-btn').disabled = true;

    fetch(PREVIEW_URL, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: new FormData(form),
    })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(({ ok, data }) => {
            document.getElementById('review-loading').classList.add('hidden');

            if (!ok) {
                const list = document.getElementById('review-errors-list');
                list.innerHTML = Object.values(data.errors || {}).flat()
                    .map(msg => `<li>${escHtml(msg)}</li>`).join('');
                document.getElementById('review-errors').classList.remove('hidden');
                return;
            }

            renderReviewCertificate(data);
            renderReviewSummary(data.summary);
            document.getElementById('review-body-wrap').classList.remove('hidden');
            document.getElementById('review-summary').classList.remove('hidden');
        })
        .catch(() => {
            document.getElementById('review-loading').classList.add('hidden');
            const list = document.getElementById('review-errors-list');
            list.innerHTML = '<li>Something went wrong loading the preview. Please try again.</li>';
            document.getElementById('review-errors').classList.remove('hidden');
        });
}

function renderReviewCertificate(data) {
    const paper = document.getElementById('review-paper');
    const p = data.padding;

    paper.style.padding = `${p.top}px ${p.right}px ${p.bottom}px ${p.left}px`;
    if (data.bg_url) {
        paper.style.backgroundImage    = `url('${data.bg_url}')`;
        paper.style.backgroundSize     = 'cover';
        paper.style.backgroundPosition = 'center';
        paper.style.backgroundRepeat   = 'no-repeat';
    } else {
        paper.style.backgroundImage = 'none';
    }

    paper.innerHTML = (data.header || '') + (data.body || '');
}

function renderReviewSummary(summary) {
    document.getElementById('review-citizen-name').textContent  = summary.citizen_name;
    document.getElementById('review-document-type').textContent = summary.document_type;
    document.getElementById('review-fee').innerHTML = summary.is_paid
        ? `<span class="text-warning">₱ ${summary.fee.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>`
        : `<span class="text-success">FREE</span>`;
    document.getElementById('review-approval').innerHTML = summary.requires_approval
        ? `<span class="text-info">Requires approval</span>`
        : `<span class="text-gray-500">Not required</span>`;

    const fieldsWrap = document.getElementById('review-fields-wrap');
    const fieldsList = document.getElementById('review-fields-list');
    const fields = (summary.fields || []).filter(f => f.value !== '');
    if (fields.length) {
        fieldsList.innerHTML = fields.map(f =>
            `<div class="flex justify-between gap-3"><span class="text-gray-400">${escHtml(f.label)}</span><span class="font-medium">${escHtml(f.value)}</span></div>`
        ).join('');
        fieldsWrap.classList.remove('hidden');
    } else {
        fieldsWrap.classList.add('hidden');
    }

    const remarksWrap = document.getElementById('review-remarks-wrap');
    if (summary.remarks) {
        document.getElementById('review-remarks').textContent = summary.remarks;
        remarksWrap.classList.remove('hidden');
    } else {
        remarksWrap.classList.add('hidden');
    }
}

function closeRequestReview() {
    document.getElementById('request-review-modal').classList.add('hidden');
}

function confirmRequestSubmit() {
    if (!document.getElementById('review-confirm-checkbox').checked) return;
    const btn = document.getElementById('review-confirm-btn');
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg> Submitting…`;
    document.getElementById('request-form').submit();
}

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Restore citizen if validation failed and old citizen_id exists
    const oldCitizenId = document.getElementById('citizen_id').value;
    if (oldCitizenId) {
        fetch(SEARCH_URL + '?q=' + oldCitizenId)
            .then(r => r.json())
            .then(results => {
                const c = results.find(r => String(r.id) === String(oldCitizenId));
                if (c) selectCitizen(c.id, c.name, c.address, c.contact, c.qrcode, c.profile ?? '');
            });
    }

    const typeSelect = document.getElementById('document_type_id');

    // Jump the card grid to whichever page contains the pre-selected type (validation redisplay)
    if (typeSelect.value) {
        const options = Array.from(typeSelect.options).filter(o => o.value !== '');
        const idx = options.findIndex(o => o.value === typeSelect.value);
        if (idx >= 0) typeCardsPageIndex = Math.floor(idx / TYPE_CARDS_PER_PAGE);
        loadTypeDetails(typeSelect);
        renderTypeCards();
        showSelectedTypeSummary();
    } else {
        renderTypeCards();
    }

    // Auto-focus search on load
    searchInput.focus();
});

</script>
@endsection
