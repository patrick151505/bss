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

@include('documents.requests._paper-css')
</style>
@endsection

@section('content')
@include('partials.photo-cropper')

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
    <input type="hidden" name="care_of_citizen_id" id="care_of_citizen_id" value="">
    <input type="hidden" name="body_override" id="body_override_input" value="">

    <div class="grid grid-cols-12 gap-6">

        {{-- Left column --}}
        <div class="col-span-12 lg:col-span-7 flex flex-col gap-5">

            {{-- Step 1: Citizen Lookup --}}
            <div class="card p-5">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                        Find Citizen
                    </h6>
                    <button type="button" onclick="openWalkin()" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white gap-1"
                            title="For people who are not registered residents">
                        <i class="mgc_walk_line"></i> Walk-in
                    </button>
                </div>

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
                    <i class="mgc_qrcode_line"></i> Scan a citizen's ID anytime on this page — no need to click here first.
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
                        <div id="card-tags" class="flex flex-wrap gap-1.5 mt-1.5"></div>
                    </div>
                    <button type="button" onclick="clearCitizen()"
                            class="text-gray-400 hover:text-danger transition shrink-0" title="Clear">
                        <i class="mgc_close_line"></i>
                    </button>
                </div>

                {{-- Walk-ins: the resident who vouches for them ("care of") — required --}}
                <div id="careof-box" class="hidden mt-3 p-3 rounded-lg border border-warning/40 bg-warning/5">
                    <label class="form-label text-sm" for="careof-search">
                        <i class="mgc_user_follow_line text-warning"></i> Care of <span class="text-danger">*</span>
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">A registered resident who knows this walk-in and vouches for them.</span>
                    </label>
                    <div id="careof-picker" class="relative">
                        <input type="text" id="careof-search" class="form-input w-full" placeholder="Search resident by name…" autocomplete="off">
                        <div id="careof-results" class="hidden absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"></div>
                    </div>
                    <div id="careof-chosen" class="hidden flex items-center gap-2.5">
                        <div id="careof-avatar" class="w-8 h-8 rounded-full bg-primary/15 text-primary flex items-center justify-center shrink-0 overflow-hidden text-xs font-bold"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate" id="careof-name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" id="careof-address"></p>
                        </div>
                        <button type="button" onclick="clearCareOf(true)" class="text-xs text-primary hover:underline shrink-0">Change</button>
                    </div>
                    <p id="careof-error" class="hidden text-xs text-danger mt-1.5"></p>
                </div>

                {{-- Shown when the selected citizen is on hold — the request is blocked --}}
                <div id="citizen-hold-alert" class="hidden mt-2 p-3 rounded-lg bg-danger/10 border border-danger/40 flex items-start gap-2.5">
                    <i class="mgc_lock_line text-danger text-xl shrink-0 mt-0.5"></i>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-danger">On Hold — cannot be issued documents</p>
                        <p class="text-sm text-gray-800 dark:text-gray-200 mt-0.5 whitespace-pre-line" id="citizen-hold-reason"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">The hold must be lifted from the citizen's profile before a request can be made.</p>
                    </div>
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
                            $rcProfile = $rc->profile_url;
                            $rcInitial = strtoupper(substr($rc->fname ?? '?', 0, 1));
                            $rcFlags = [
                                'voter'  => (bool) $rc->voters,
                                'senior' => $rc->age !== null && $rc->age >= 60,
                                'pwd'    => (bool) $rc->is_pwd,
                                'solo'   => (bool) $rc->is_soloparents,
                                'hold'   => $rc->activeHold?->reason,
                            ];
                        @endphp
                        <button type="button"
                                onclick='selectCitizen({{ $rc->id }}, @json($rc->full_name), @json($rc->full_address), @json($rc->contact ?? ""), @json($rc->qrcode ?? ""), @json($rcProfile ?? ""), @json($rcFlags))'
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
                                    @if($rc->activeHold)<i class="mgc_lock_line text-danger" title="On hold"></i>@endif
                                    {{ $rc->full_name }}
                                </p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    {{ $rc->full_address ?: '—' }}
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
                            data-color="{{ $t->badge_color }}"
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
                <div id="selected-type-card" class="hidden p-3 rounded-lg border bg-success/5 flex items-start gap-3">
                    <div id="selected-type-icon" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                        <i class="mgc_document_2_line"></i>
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

                {{-- Personal details the chosen document prints (walk-ins fill gaps here) --}}
                <div id="person-section" class="hidden mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                    <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1 flex items-center gap-2">
                        <i class="mgc_user_3_line text-primary"></i> Details needed for this document
                    </h6>
                    <p id="person-note" class="text-xs text-gray-500 dark:text-gray-400 mb-3"></p>
                    <div id="person-alert" class="hidden mb-3 p-3 rounded-lg text-sm"></div>

                    {{-- Photo: shown when the document prints a profile photo. Take or upload a new one
                         (residents and walk-ins); it is saved as the person's profile photo. --}}
                    <div id="pp-box" class="hidden mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative w-16 h-16 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                <img id="pp-img" src="" alt="" class="hidden w-full h-full object-cover">
                                <i id="pp-empty" class="mgc_user_3_line text-2xl text-gray-400"></i>
                                <div id="pp-saving" class="hidden absolute inset-0 bg-black/50 items-center justify-center text-white">
                                    <i class="mgc_loading_3_line animate-spin text-xl"></i>
                                </div>
                            </div>
                            <div class="min-w-0" style="margin-right:auto">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                    <i class="mgc_pic_line text-primary"></i> Photo
                                </p>
                                <p id="pp-status" class="text-xs"></p>
                            </div>
                            <button type="button" id="pp-camera-btn" class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                <i class="mgc_camera_line"></i> <span id="pp-camera-label">Take photo</span>
                            </button>
                            <button type="button" id="pp-upload-btn" class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                <i class="mgc_upload_2_line"></i> <span id="pp-upload-label">Upload photo</span>
                            </button>
                            {{-- No name attribute: these must not be sent with the request form --}}
                            <input type="file" id="pp-file" accept="image/jpeg,image/png" class="hidden">
                            <input type="file" id="pp-file-camera" accept="image/*" capture="user" class="hidden">
                        </div>
                        <p id="pp-error" class="hidden text-xs text-danger mt-2"></p>

                        <div id="pp-camera-panel" class="hidden mt-3">
                            <div class="relative mx-auto w-full max-w-xs rounded-lg overflow-hidden bg-black" style="aspect-ratio:1/1">
                                <video id="pp-video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                <div class="absolute rounded-full border-2 border-dashed pointer-events-none" style="inset:12%;border-color:rgba(255,255,255,.6)"></div>
                            </div>
                            <div class="flex justify-center gap-2 mt-3">
                                <button type="button" id="pp-camera-cancel" class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">Cancel</button>
                                <button type="button" id="pp-camera-switch" class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hidden" title="Switch camera"><i class="mgc_refresh_2_line"></i></button>
                                <button type="button" id="pp-camera-capture" class="btn btn-sm bg-primary text-white flex items-center gap-1.5"><i class="mgc_camera_line"></i> Capture</button>
                            </div>
                        </div>
                    </div>

                    <div id="person-fields" class="wi-grid"></div>
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

            {{-- Step 3: Purpose + Remarks + Submit --}}
            <div class="card p-5">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
                    Purpose &amp; Remarks
                </h6>

                {{-- Purpose (required, searchable dropdown; type a new one to add it) --}}
                <div class="mb-4">
                    <label for="purpose-search" class="form-label text-sm">
                        Purpose <span class="text-danger">*</span>
                    </label>

                    {{-- The value actually submitted --}}
                    <input type="hidden" name="purpose" id="purpose-input" value="{{ old('purpose') }}">

                    <div id="purpose-dd" class="relative">
                        {{-- Search / display box --}}
                        <div class="relative">
                            <input type="text" id="purpose-search" autocomplete="off"
                                   class="form-input pr-9"
                                   value="{{ old('purpose') }}"
                                   placeholder="Search or type a purpose…" maxlength="255"
                                   onfocus="openPurposeDropdown()"
                                   oninput="onPurposeSearch()"
                                   onkeydown="onPurposeKeydown(event)">
                            <button type="button" tabindex="-1"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                    onclick="togglePurposeDropdown()">
                                <i id="purpose-chevron" class="mgc_down_line"></i>
                            </button>
                        </div>

                        {{-- Options panel --}}
                        <div id="purpose-list"
                             class="hidden absolute z-30 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            @foreach($purposes as $p)
                            <button type="button"
                                    class="purpose-option w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-primary/10"
                                    data-name="{{ $p->name }}"
                                    onclick="selectPurpose(this.dataset.name)">{{ $p->name }}</button>
                            @endforeach
                            {{-- "Add new" row, shown by JS when the typed text has no exact match --}}
                            <button type="button" id="purpose-add-new"
                                    class="hidden w-full text-left px-3 py-2 text-sm text-primary hover:bg-primary/10 border-t border-gray-100 dark:border-gray-700"
                                    onclick="selectTypedPurpose()">
                                <i class="mgc_add_line"></i> Use "<span id="purpose-add-label"></span>"
                            </button>
                            {{-- Empty state --}}
                            <div id="purpose-empty" class="hidden px-3 py-3 text-xs text-gray-400 text-center">No matches.</div>
                        </div>
                    </div>

                    <p id="purpose-error" class="hidden text-xs text-danger mt-1">Purpose is required.</p>
                    <p class="text-xs text-gray-400 mt-1">
                        Click to pick, or type to search. A new purpose is saved for next time; if it already exists it reuses that one.
                        Use <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&#123;&#123; purpose &#125;&#125;</code> in the template.
                    </p>
                </div>

                <label class="form-label text-sm">Remarks</label>
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
                        Scan the QR code on the citizen's ID with a handheld scanner at any time — you don't need to click
                        the search box first. The citizen is <strong>auto-selected instantly</strong>, even while you are
                        filling in another field.
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>

{{-- Walk-in: find a saved walk-in or add a new one (outside the request form on purpose) --}}
<style>
    .wi-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .75rem; }
    .wi-grid > * { grid-column: span 12 / span 12; }
    @media (min-width: 640px) {
        .wi-grid > .wi-6 { grid-column: span 6 / span 6; }
        .wi-grid > .wi-4 { grid-column: span 4 / span 4; }
        .wi-grid > .wi-2 { grid-column: span 2 / span 2; }
    }
</style>
<div id="walkin-modal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="walkin-title" onclick="if(event.target===this)closeWalkin()">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-xl flex flex-col" style="max-height: 92vh;">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 id="walkin-title" class="font-bold text-gray-800 dark:text-white flex items-center gap-2"><i class="mgc_walk_line"></i> Walk-in</h3>
            <button type="button" onclick="closeWalkin()" aria-label="Close"><i class="mgc_close_line text-xl text-gray-500 dark:text-gray-400"></i></button>
        </div>
        <div class="p-4 space-y-4 overflow-y-auto">
            <p class="text-xs text-gray-500 dark:text-gray-400">For people who are not registered residents. They're saved for their next visit, but are not counted as residents anywhere else.</p>

            {{-- Returning walk-in --}}
            <div>
                <label class="form-label" for="walkin-search">Came before? Search by name or reference (WI-…)</label>
                <input type="text" id="walkin-search" class="form-input w-full" placeholder="e.g. Dela Cruz or WI-00012" autocomplete="off">
                <div id="walkin-results" class="mt-2 space-y-1.5"></div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">New walk-in</p>
                <div class="wi-grid">
                    <div class="wi-6"><label class="form-label" for="wi-fname">First name <span class="text-danger">*</span></label><input id="wi-fname" class="form-input w-full" maxlength="100"></div>
                    <div class="wi-6"><label class="form-label" for="wi-lname">Last name <span class="text-danger">*</span></label><input id="wi-lname" class="form-input w-full" maxlength="100"></div>
                    <div class="wi-4"><label class="form-label" for="wi-mname">Middle name</label><input id="wi-mname" class="form-input w-full" maxlength="100"></div>
                    <div class="wi-2"><label class="form-label" for="wi-suffix">Suffix</label><input id="wi-suffix" class="form-input w-full" maxlength="20" placeholder="Jr."></div>
                    <div class="wi-6"><label class="form-label" for="wi-bday">Birthday <span class="text-danger">*</span></label><input id="wi-bday" type="date" class="form-input w-full" max="{{ now()->toDateString() }}"></div>
                    <div class="wi-6"><label class="form-label" for="wi-contact">Contact number</label><input id="wi-contact" class="form-input w-full" maxlength="20"></div>
                    <div class="wi-6">
                        <label class="form-label" for="wi-idtype">ID presented</label>
                        <select id="wi-idtype" class="form-select w-full">
                            <option value="">— None —</option>
                            @foreach(['PhilSys National ID', "Driver's License", 'UMID', 'Passport', "Voter's ID", 'Postal ID', 'PRC ID', 'Senior Citizen ID', 'PWD ID', 'School ID', 'Other'] as $idt)
                                <option>{{ $idt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wi-6"><label class="form-label" for="wi-idno">ID number</label><input id="wi-idno" class="form-input w-full" maxlength="60"></div>
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                        <input type="checkbox" id="wi-consent" class="form-checkbox mt-0.5">
                        <span>The person agreed to the collection and use of their information for this request (Data Privacy Act).</span>
                    </label>
                </div>
                <p id="wi-error" class="hidden mt-3 text-sm text-danger"></p>
                <div id="wi-resident" class="hidden mt-3 p-3 rounded-lg bg-warning/10 border border-warning/30 text-sm text-gray-800 dark:text-gray-100"></div>
            </div>
        </div>
        <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
            <button type="button" onclick="closeWalkin()" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
            <button type="button" id="wi-save" onclick="saveWalkin()" class="btn bg-primary text-white"><i class="mgc_check_line"></i> Add walk-in</button>
        </div>
    </div>
</div>

{{-- Review & Confirm Modal --}}
<div id="request-review-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" onclick="if(event.target===this)closeRequestReview()">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col w-full max-w-5xl" style="height:95vh; max-height:95vh;">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_eye_2_line text-primary"></i> Review Request
            </h6>
            <button type="button" onclick="closeRequestReview()"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-500 text-sm py-1.5 px-3">
                <i class="mgc_close_line"></i>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 min-h-0">

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

            {{-- Editable-body hint (shown only when the type allows editing) --}}
            <div id="body-edit-hint" class="hidden mx-6 mt-4 -mb-2 p-2.5 rounded-lg bg-info/10 border border-info/30 text-xs text-info flex items-center gap-2">
                <i class="mgc_edit_line"></i> You can edit the certificate wording below for this request.
            </div>

            {{-- Certificate preview --}}
            <div id="review-body-wrap" class="hidden bg-gray-100 dark:bg-gray-800/50 p-6 overflow-x-auto">
                <div id="review-paper"
                     style="width:8.5in; min-width:8.5in; min-height:11in; margin:0 auto; background:#fff; border:1px solid #ccc;
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
                        <p class="hidden text-xs text-gray-500 dark:text-gray-400 mt-0.5" id="review-care-of"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Document Type</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100" id="review-document-type"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Purpose</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100" id="review-purpose"></p>
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
@include('partials.scan-anywhere')
<script>
const SEARCH_URL   = '{{ route('citizens.search') }}';
const HISTORY_BASE = '{{ url('citizens') }}';
const RESOLVE_DEFAULTS_URL = '{{ route('documents.requests.resolve-defaults') }}';
const WALKIN_SEARCH_URL = '{{ route('documents.requests.walkins.search') }}';
const WALKIN_STORE_URL  = '{{ route('documents.requests.walkins.store') }}';
const PERSON_CHECK_URL  = '{{ route('documents.requests.person-check') }}';
const CITIZEN_EDIT_BASE = '{{ url('citizens') }}';
let selectedWalkin = null;   // the walk-in payload when the requester is a walk-in

// ── Walk-in pop-up ────────────────────────────────────────────────────
function openWalkin() {
    ['wi-fname', 'wi-lname', 'wi-mname', 'wi-suffix', 'wi-bday', 'wi-contact', 'wi-idno', 'walkin-search'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('wi-idtype').value = '';
    document.getElementById('wi-consent').checked = false;
    document.getElementById('walkin-results').innerHTML = '';
    document.getElementById('wi-error').classList.add('hidden');
    document.getElementById('wi-resident').classList.add('hidden');
    const m = document.getElementById('walkin-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
    setTimeout(() => document.getElementById('walkin-search').focus(), 50);
}

function closeWalkin() {
    const m = document.getElementById('walkin-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
}

let walkinSearchTimer = null;
document.getElementById('walkin-search').addEventListener('input', function () {
    clearTimeout(walkinSearchTimer);
    const q = this.value.trim();
    const box = document.getElementById('walkin-results');
    if (q.length < 2) { box.innerHTML = ''; return; }
    walkinSearchTimer = setTimeout(() => {
        fetch(WALKIN_SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(list => {
                box.innerHTML = list.length ? list.map((w, i) => `
                    <button type="button" data-i="${i}" class="walkin-pick w-full text-left flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5">
                        <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0"><i class="mgc_walk_line"></i></span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${escHtml(w.name)}</span>
                            <span class="block text-xs text-gray-400">${escHtml(w.code)} · born ${escHtml(w.bday || '—')}${w.last_visit ? ' · last visit ' + escHtml(w.last_visit) : ''}</span>
                        </span>
                    </button>`).join('')
                    : '<p class="text-xs text-gray-400">No saved walk-in matches. Add them below.</p>';
                box.querySelectorAll('.walkin-pick').forEach(b => b.addEventListener('click', () => { selectWalkin(list[b.dataset.i]); closeWalkin(); }));
            });
    }, 250);
});

async function saveWalkin() {
    const val = id => document.getElementById(id).value.trim();
    const err = document.getElementById('wi-error');
    const resident = document.getElementById('wi-resident');
    err.classList.add('hidden'); resident.classList.add('hidden');
    const btn = document.getElementById('wi-save');
    btn.disabled = true;
    const res = await fetch(WALKIN_STORE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
        body: JSON.stringify({
            fname: val('wi-fname'), mname: val('wi-mname'), lname: val('wi-lname'), suffix: val('wi-suffix'),
            bday: val('wi-bday'), contact: val('wi-contact'),
            walkin_id_type: val('wi-idtype'), walkin_id_number: val('wi-idno'),
            consent: document.getElementById('wi-consent').checked ? 1 : 0,
        }),
    }).then(async r => ({ status: r.status, data: await r.json() }))
      .catch(() => ({ status: 0, data: { message: 'Could not reach the server. Try again.' } }));
    btn.disabled = false;

    const d = res.data;
    if (res.status === 200 && d.success) { selectWalkin(d.walkin); closeWalkin(); return; }
    if (res.status === 409 && d.duplicate === 'walkin') {           // came before — reuse
        selectWalkin(d.walkin); closeWalkin();
        Swal.fire({ icon: 'info', title: 'Returning walk-in', text: d.message, timer: 2500, showConfirmButton: false });
        return;
    }
    if (res.status === 409 && d.duplicate === 'resident') {         // actually a registered resident
        resident.innerHTML = `${escHtml(d.message)}<button type="button" id="wi-use-resident" class="btn btn-sm bg-primary text-white mt-2">Use registered record</button>`;
        resident.classList.remove('hidden');
        document.getElementById('wi-use-resident').onclick = () => {
            const c = d.citizen;
            selectCitizen(c.id, c.name, c.address || '', c.contact || '', c.qrcode || '', c.profile || '', { voter: c.voter, senior: c.senior, pwd: c.pwd, solo: c.solo, hold: c.hold });
            closeWalkin();
        };
        return;
    }
    err.textContent = d.message && !d.errors ? d.message : Object.values(d.errors || {}).flat().join(' ') || 'Could not add the walk-in.';
    err.classList.remove('hidden');
}

function selectWalkin(w) {
    selectCitizen(w.id, w.name, w.address || '', w.contact || '', '', w.profile || '',
        { voter: false, senior: w.senior, pwd: false, solo: false, hold: w.hold || null });
    selectedWalkin = w;                                       // selectCitizen() clears it — set after
    showCareOf(true);
    const tags = document.getElementById('card-tags');
    tags.innerHTML = `<span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700"><i class="mgc_walk_line"></i> Walk-in · ${escHtml(w.code)}</span>`
        + (w.last_visit ? `<span class="text-[11px] text-gray-400 self-center">last visit ${escHtml(w.last_visit)}</span>` : '');
    checkPersonDetails();
}

// ── "Care of" for walk-ins ────────────────────────────────────────────
// A walk-in must name a registered resident who vouches for them. Picked from
// residents only (the citizen search leaves walk-ins out).
let careOfTimer = null, careOfSeq = 0;
function showCareOf(on) {
    document.getElementById('careof-box').classList.toggle('hidden', !on);
    clearCareOf(false);
}
function clearCareOf(focus) {
    document.getElementById('care_of_citizen_id').value = '';
    document.getElementById('careof-chosen').classList.add('hidden');
    document.getElementById('careof-picker').classList.remove('hidden');
    document.getElementById('careof-search').value = '';
    document.getElementById('careof-results').classList.add('hidden');
    document.getElementById('careof-error').classList.add('hidden');
    if (focus) document.getElementById('careof-search').focus();
}
function pickCareOf(c) {
    const err = document.getElementById('careof-error');
    if (c.hold) {
        err.textContent = c.name + ' is on hold and cannot vouch for a walk-in.';
        err.classList.remove('hidden');
        return;
    }
    document.getElementById('care_of_citizen_id').value = c.id;
    document.getElementById('careof-name').textContent = c.name;
    document.getElementById('careof-address').textContent = c.address || '';
    document.getElementById('careof-avatar').innerHTML = c.profile
        ? `<img src="${escHtml(c.profile)}" alt="" class="w-full h-full object-cover">`
        : escHtml((c.name || '?').charAt(0).toUpperCase());
    document.getElementById('careof-picker').classList.add('hidden');
    document.getElementById('careof-results').classList.add('hidden');
    document.getElementById('careof-chosen').classList.remove('hidden');
    err.classList.add('hidden');
}
document.getElementById('careof-search').addEventListener('input', function () {
    clearTimeout(careOfTimer);
    const q = this.value.trim();
    const box = document.getElementById('careof-results');
    if (q.length < 2) { box.classList.add('hidden'); return; }
    careOfTimer = setTimeout(async () => {
        const mine = ++careOfSeq;
        const list = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
            .then(r => r.json()).catch(() => []);
        if (mine !== careOfSeq) return;
        box.innerHTML = list.length ? list.map((c, i) => `
            <button type="button" data-i="${i}" class="careof-pick w-full text-left flex items-center gap-2.5 px-3 py-2 hover:bg-primary/5 border-b last:border-0 border-gray-100 dark:border-gray-700 ${c.hold ? 'opacity-60' : ''}">
                <span class="w-7 h-7 rounded-full bg-primary/15 text-primary flex items-center justify-center shrink-0 overflow-hidden text-xs font-bold">
                    ${c.profile ? `<img src="${escHtml(c.profile)}" alt="" class="w-full h-full object-cover">` : escHtml(c.name.charAt(0).toUpperCase())}
                </span>
                <span class="min-w-0">
                    <span class="block text-sm text-gray-800 dark:text-gray-100 truncate">${escHtml(c.name)}${c.hold ? ' <span class="text-danger text-xs">(on hold)</span>' : ''}</span>
                    <span class="block text-xs text-gray-400 truncate">${escHtml(c.address || '')}</span>
                </span>
            </button>`).join('')
            : '<p class="px-3 py-3 text-sm text-gray-400">No resident found.</p>';
        box.querySelectorAll('.careof-pick').forEach(b => b.addEventListener('click', () => pickCareOf(list[b.dataset.i])));
        box.classList.remove('hidden');
    }, 300);
});
// Enter in this box must not submit the request form.
document.getElementById('careof-search').addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
document.addEventListener('click', e => {
    if (!e.target.closest('#careof-picker')) document.getElementById('careof-results').classList.add('hidden');
});

// ── Photo for documents that print the profile photo ─────────────────
// Saved straight away as the person's profile photo (resident or walk-in),
// so the review preview and the printed certificate use it.
const PERSON_PHOTO_URL = '{{ route('documents.requests.person-photo') }}';
let ppStream = null, ppFacing = 'user';

function ppShow(photo) {
    ppStopCamera();
    ppError('');
    document.getElementById('pp-box').classList.toggle('hidden', !photo);
    if (photo) ppSetImage(photo.url);
}

function ppSetImage(url) {
    const img = document.getElementById('pp-img'), status = document.getElementById('pp-status');
    img.classList.toggle('hidden', !url);
    document.getElementById('pp-empty').classList.toggle('hidden', !!url);
    if (url) img.src = url;
    status.className = 'text-xs ' + (url ? 'text-success' : 'text-warning');
    status.textContent = url
        ? 'On file — this document prints it. Retake or upload a new one if it is outdated.'
        : 'No photo yet — this document prints one. Take or upload a photo.';
    document.getElementById('pp-upload-label').textContent = url ? 'Upload new' : 'Upload photo';
    document.getElementById('pp-camera-label').textContent = url ? 'Retake photo' : 'Take photo';
}

function ppError(msg) {
    const el = document.getElementById('pp-error');
    el.textContent = msg || '';
    el.classList.toggle('hidden', !msg);
}

function ppSaving(on) {
    const ov = document.getElementById('pp-saving');
    ov.classList.toggle('hidden', !on);
    ov.classList.toggle('flex', on);
    ['pp-camera-btn', 'pp-upload-btn'].forEach(id => {
        const b = document.getElementById(id);
        b.disabled = on; b.classList.toggle('opacity-60', on);
    });
}


function ppSave(blob) {
    if (!selectedId || !blob) return;
    ppError('');
    ppSaving(true);
    const fd = new FormData();
    fd.append('citizen_id', selectedId);
    fd.append('photo', blob, 'photo.jpg');
    fetch(PERSON_PHOTO_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: fd })
        .then(async r => {
            const d = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(d.errors?.photo?.[0] || d.message || 'Could not save the photo.');
            return d;
        })
        .then(d => {
            ppSetImage(d.url);
            // Refresh the selected-person card's picture too, if it shows one.
            document.querySelectorAll('#citizen-card img').forEach(i => { i.src = d.url; });
        })
        .catch(e => ppError(e.message))
        .finally(() => ppSaving(false));
}

// Uploaded picture: position it in the square frame, then save.
function ppFromFile(file) {
    if (!file) return;
    ppError('');
    PhotoCrop.open(file).then(blob => { if (blob) ppSave(blob); }).catch(e => ppError(e.message));
}

function ppStopCamera() {
    ppStream?.getTracks().forEach(t => t.stop());
    ppStream = null;
    document.getElementById('pp-camera-panel')?.classList.add('hidden');
}

// Live camera needs HTTPS (or localhost); otherwise use the phone camera via a file input.
function ppStartCamera() {
    ppError('');
    if (!navigator.mediaDevices?.getUserMedia) { document.getElementById('pp-file-camera').click(); return; }
    ppStopCamera();
    navigator.mediaDevices.getUserMedia({ video: { facingMode: ppFacing, width: { ideal: 1280 }, height: { ideal: 1280 } }, audio: false })
        .then(stream => {
            ppStream = stream;
            const v = document.getElementById('pp-video');
            v.srcObject = stream;
            v.style.transform = ppFacing === 'user' ? 'scaleX(-1)' : '';
            document.getElementById('pp-camera-panel').classList.remove('hidden');
            navigator.mediaDevices.enumerateDevices().then(ds => {
                document.getElementById('pp-camera-switch').classList.toggle('hidden', ds.filter(d => d.kind === 'videoinput').length < 2);
            }).catch(() => {});
        })
        .catch(err => ppError(err.name === 'NotAllowedError'
            ? 'Camera access was blocked. Allow the camera for this site in the browser, or use Upload.'
            : 'No camera found. Use Upload instead.'));
}

document.getElementById('pp-upload-btn').addEventListener('click', () => document.getElementById('pp-file').click());
['pp-file', 'pp-file-camera'].forEach(id => {
    document.getElementById(id).addEventListener('change', e => { ppFromFile(e.target.files[0]); e.target.value = ''; });
});
document.getElementById('pp-camera-btn').addEventListener('click', ppStartCamera);
document.getElementById('pp-camera-cancel').addEventListener('click', ppStopCamera);
document.getElementById('pp-camera-switch').addEventListener('click', () => { ppFacing = ppFacing === 'user' ? 'environment' : 'user'; ppStartCamera(); });
document.getElementById('pp-camera-capture').addEventListener('click', () => {
    const v = document.getElementById('pp-video');
    if (!v.videoWidth) return;
    // Freeze the frame, then position it in the square frame before saving.
    PhotoCrop.open(v, { mirror: ppFacing === 'user' }).then(blob => { if (blob) { ppStopCamera(); ppSave(blob); } });
});

// ── Details the chosen document needs from the chosen person ─────────
let personCheckSeq = 0;
async function checkPersonDetails() {
    const section = document.getElementById('person-section');
    const typeId  = document.getElementById('document_type_id').value;
    if (!selectedId || !typeId) { section.classList.add('hidden'); document.getElementById('person-fields').innerHTML = ''; return; }

    const mine = ++personCheckSeq;
    const res = await fetch(PERSON_CHECK_URL + '?' + new URLSearchParams({ citizen_id: selectedId, document_type_id: typeId }), { headers: { Accept: 'application/json' } })
        .then(r => r.json()).catch(() => null);
    if (!res || mine !== personCheckSeq) return;

    const fieldsBox = document.getElementById('person-fields');
    const alert = document.getElementById('person-alert');
    const note  = document.getElementById('person-note');
    fieldsBox.innerHTML = '';
    alert.className = 'hidden mb-3 p-3 rounded-lg text-sm';
    ppShow(res.walkin && !res.allowed ? null : res.photo);

    if (res.walkin && !res.allowed) {
        alert.className = 'mb-3 p-3 rounded-lg text-sm bg-danger/10 border border-danger/30 text-danger';
        alert.textContent = 'This document type is not available for walk-ins. The person needs to be registered as a resident first.';
        note.textContent = '';
        section.classList.remove('hidden');
        return;
    }

    if (!res.walkin) {
        // Registered citizen: only warn about gaps — their record is edited from the profile.
        if (!res.missing.length) {
            // Nothing missing: keep the section only for the photo.
            note.textContent = '';
            section.classList.toggle('hidden', !res.photo);
            return;
        }
        const labels = res.fields.filter(f => f.missing).map(f => f.label).join(', ');
        alert.className = 'mb-3 p-3 rounded-lg text-sm bg-warning/10 border border-warning/30 text-gray-800 dark:text-gray-100';
        alert.innerHTML = `<i class="mgc_alert_line text-warning"></i> This citizen's record has no <strong>${escHtml(labels)}</strong>, which this document prints — it will show blank.
            <a href="${CITIZEN_EDIT_BASE}/${selectedId}/edit" target="_blank" class="text-primary hover:underline">Update the profile</a>`;
        note.textContent = '';
        section.classList.remove('hidden');
        return;
    }

    // Walk-in: show what this document prints; fill the gaps (saved for next time).
    note.textContent = res.fields.length
        ? (res.missing.length ? 'Fill in the highlighted details — they are saved for the walk-in\'s next visit.' : 'All details this document needs are on file. You can correct them here.')
        : 'This document only prints the name and birthday.';
    if (res.qr) {
        alert.className = 'mb-3 p-3 rounded-lg text-sm bg-warning/10 border border-warning/30 text-gray-800 dark:text-gray-100';
        alert.innerHTML = '<i class="mgc_qrcode_line text-warning"></i> This document prints a QR code. Walk-ins have none, so it will be left blank.';
    }
    res.fields.forEach(f => {
        const name = `person[${f.key}]`, v = f.value ?? '';
        const ring = f.missing ? ' border-warning' : '';
        let input;
        if (f.input === 'select') {
            input = `<select name="${name}" class="form-select${ring}" ${f.missing ? 'required' : ''}><option value="">— Select —</option>
                ${f.options.map(o => `<option value="${o.value}" ${String(v) === String(o.value) ? 'selected' : ''}>${escHtml(o.label)}</option>`).join('')}</select>`;
        } else {
            input = `<input type="${f.input}" name="${name}" value="${escHtml(v)}" class="form-input${ring}" ${f.missing ? 'required' : ''}>`;
        }
        fieldsBox.insertAdjacentHTML('beforeend', `<div class="wi-6">
            <label class="form-label text-sm">${escHtml(f.label)} ${f.missing ? '<span class="text-danger">*</span>' : ''}</label>${input}</div>`);
    });
    section.classList.remove('hidden');
}
const oldValues    = @json(old());

let searchTimer   = null;
let selectedId    = '{{ old('citizen_id') }}';
let selectedHold  = null; // hold reason of the selected citizen, or null

// A citizen on hold cannot be issued documents: turn the card red, show the
// reason, and remember it so submission is blocked.
function showHold(reason) {
    selectedHold = reason || null;
    const card  = document.getElementById('citizen-card');
    const alert = document.getElementById('citizen-hold-alert');
    card.classList.toggle('border-success/40', !selectedHold);
    card.classList.toggle('bg-success/5', !selectedHold);
    card.classList.toggle('border-danger/40', !!selectedHold);
    card.classList.toggle('bg-danger/5', !!selectedHold);
    alert.classList.toggle('hidden', !selectedHold);
    document.getElementById('citizen-hold-reason').textContent = selectedHold || '';
}

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

let searchAbort = null; // cancels a previous in-flight search so stale results
                        // never overwrite the newest ones (fast typing / scanners).

function doSearch(q, autoSelect) {
    if (q.length < 2) { dropdown.classList.add('hidden'); return; }

    // Cancel any search still in flight.
    if (searchAbort) searchAbort.abort();
    searchAbort = new AbortController();
    const signal = searchAbort.signal;

    if (autoSelect) {
        // QR/scanner path: select instantly, but only on an exact QR match — never
        // guess. No exact match falls through to the normal results grid below.
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { signal })
            .then(r => r.json())
            .then(results => {
                const hit = results.find(c => c.qrcode === q);
                if (hit) {
                    dropdown.classList.add('hidden');
                    selectCitizen(hit.id, hit.name, hit.address, hit.contact, hit.qrcode, hit.profile ?? '', { voter: hit.voter, senior: hit.senior, pwd: hit.pwd, solo: hit.solo, hold: hit.hold ?? null });
                    document.getElementById('citizen-card').scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    return;
                }
                if (!results.length) {
                    document.getElementById('search-results-grid').innerHTML =
                        '<p class="col-span-2 text-xs text-danger py-1"><i class="mgc_alert_line"></i> No citizen found for this code. Check that the ID belongs to an active citizen, or search by name.</p>';
                    document.getElementById('search-results-label').textContent = 'Scan result';
                    dropdown.classList.remove('hidden');
                    searchInput.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    return;
                }
                doSearch(q, false);                       // partial matches: let staff pick
            })
            .catch(err => { if (err.name !== 'AbortError') console.error(err); });
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
    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { signal })
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
                        onclick='selectCitizen(${c.id}, ${JSON.stringify(c.name)}, ${JSON.stringify(c.address)}, ${JSON.stringify(c.contact)}, ${JSON.stringify(c.qrcode)}, ${JSON.stringify(c.profile ?? "")}, ${JSON.stringify({voter: !!c.voter, senior: !!c.senior, pwd: !!c.pwd, solo: !!c.solo, hold: c.hold ?? null})})'
                        class="flex items-center gap-2.5 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5 transition text-left w-full group">
                    <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">
                        ${avatar}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-primary">${c.hold ? '<i class="mgc_lock_line text-danger" title="On hold"></i> ' : ''}${escHtml(c.name)}</p>
                        <p class="text-[10px] text-gray-400 truncate">${escHtml(c.address) || '—'}</p>
                    </div>
                    ${c.qrcode ? '<i class="mgc_qrcode_line text-gray-300 text-xs shrink-0"></i>' : ''}
                </button>`;
            }).join('');
            dropdown.classList.remove('hidden');
        })
        .catch(err => { if (err.name !== 'AbortError') console.error(err); });
}

searchInput.addEventListener('keydown', function (e) {
    const now = Date.now();
    const gap = now - lastKeyTime;
    lastKeyTime = now;

    // A scan's first character arrives after a pause, so it looks like typing and
    // never reaches scannerBuffer. Always search the box's full value instead.
    if (e.key === 'Enter') {
        e.preventDefault();
        const q = searchInput.value.trim();
        const wasScan = scannerBuffer.length >= 2;
        clearTimeout(scannerTimer);
        scannerBuffer = '';
        scannerActive = false;
        clearTimeout(searchTimer);
        if (q.length >= 2) doSearch(q, wasScan || isQrLike(q));
        return;
    }

    if (e.key.length === 1 && gap < 50) {
        // Fast keystroke — scanner input
        scannerActive = true;
        scannerBuffer += e.key;
        clearTimeout(scannerTimer);
        // Some scanners don't send Enter; fire after 150ms silence
        scannerTimer = setTimeout(() => {
            const q = searchInput.value.trim();
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
    const qrLike = isQrLike(q);
    searchTimer = setTimeout(() => doSearch(q, qrLike), qrLike ? 150 : 300);
});

function isQrLike(q) {
    return q.length > 12 && !q.includes(' ');
}

// ── Scan anywhere on the page ─────────────────────────────────────────
// A scan made while the search box doesn't have focus still selects the citizen
// (see partials/scan-anywhere). The search box keeps its own handling above.
listenForScans({
    skip: searchInput,
    onScan: code => { searchInput.value = code; doSearch(code, true); },
});

document.addEventListener('click', function (e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

// Render status badges on the citizen card, computed from the citizen's own
// columns: Voter/Non-Voter, Senior (age >= 60), PWD, Solo Parent.
function renderCitizenTags(flags) {
    const wrap = document.getElementById('card-tags');
    if (!wrap) return;

    const pill = (text, cls) =>
        `<span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[11px] font-medium ${cls}">${text}</span>`;

    let html = flags.voter
        ? pill('✓ Voter', 'bg-green-100 text-green-800')
        : pill('Non-Voter', 'bg-gray-100 text-gray-600');

    if (flags.senior) html += pill('Senior', 'bg-amber-100 text-amber-800');
    if (flags.pwd)    html += pill('PWD', 'bg-blue-100 text-blue-800');
    if (flags.solo)   html += pill('Solo Parent', 'bg-purple-100 text-purple-800');

    wrap.innerHTML = html;
}

function selectCitizen(id, name, address, contact, qrcode, profileUrl, flags) {
    selectedId = id;
    selectedWalkin = null;
    showCareOf(false);
    document.getElementById('citizen_id').value = id;
    searchInput.value = '';
    dropdown.classList.add('hidden');
    document.getElementById('search-results-grid').innerHTML = '';

    document.getElementById('card-name').textContent    = name;
    document.getElementById('card-address').textContent = address || '—';
    document.getElementById('card-contact').textContent = contact ? ('📞 ' + contact) : '';
    renderCitizenTags(flags || {});
    showHold(flags && flags.hold);

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
    checkPersonDetails();
}

@if(!empty($selectedCitizen))
@php
    $preFlags = [
        'voter'  => (bool) $selectedCitizen->voters,
        'senior' => $selectedCitizen->age !== null && $selectedCitizen->age >= 60,
        'pwd'    => (bool) $selectedCitizen->is_pwd,
        'solo'   => (bool) $selectedCitizen->is_soloparents,
        'hold'   => $selectedCitizen->activeHold?->reason,
    ];
    $preProfile = ($selectedCitizen->profile_url ?? '');
@endphp
{{-- Deep-linked from the citizen list: preselect that person. --}}
document.addEventListener('DOMContentLoaded', () => {
    selectCitizen(
        {{ $selectedCitizen->id }},
        @json($selectedCitizen->full_name),
        @json($selectedCitizen->full_address),
        @json($selectedCitizen->contact ?? ''),
        @json($selectedCitizen->qrcode ?? ''),
        @json($preProfile),
        @json($preFlags)
    );
});
@endif

function clearCitizen() {
    selectedId = '';
    selectedWalkin = null;
    showCareOf(false);
    document.getElementById('person-section').classList.add('hidden');
    document.getElementById('person-fields').innerHTML = '';
    ppShow(null);
    document.getElementById('citizen_id').value = '';
    searchInput.value = '';
    document.getElementById('citizen-card').classList.add('hidden');
    showHold(null);
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

    // No selectable types: either none exist yet, or the user holds no
    // issue_document.* permission for any of them. Say so instead of leaving
    // a blank panel under the "Document Type" heading.
    if (options.length === 0) {
        grid.innerHTML = `
            <div class="sm:col-span-3 p-6 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-center">
                <i class="mgc_document_2_line text-4xl text-gray-300 dark:text-gray-600 block mb-2"></i>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No document types available to you.</p>
                <p class="text-xs text-gray-400 mt-1">
                    Ask an administrator to grant you access to a document type on the Roles page.
                </p>
            </div>`;
        pager.classList.add('hidden');
        return;
    }

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
        // Each type's own color: left stripe + icon always, full outline when selected.
        const color     = /^#[0-9a-f]{6}$/i.test(opt.dataset.color || '') ? opt.dataset.color : '#727cf5';
        const cardStyle = `border-inline-start: 6px solid ${color};` + (isChecked ? ` border-color: ${color}; background-color: ${color}14;` : '');

        return `
            <button type="button"
                    onclick="selectTypeCard('${opt.value}')"
                    class="type-card text-left p-3 rounded-lg border-2 transition ${isChecked ? '' : 'border-gray-200 dark:border-gray-700 hover:shadow-md'}"
                    style="${cardStyle}"
                    data-type-value="${opt.value}">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background-color: ${color}26; color: ${color};">
                        <i class="mgc_document_2_line text-sm"></i>
                    </span>
                    <span class="min-w-0">${escHtml(opt.text.split(' — ')[0])}</span>
                    ${isChecked ? `<i class="mgc_check_circle_fill ms-auto text-base" style="color: ${color};"></i>` : ''}
                </p>
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

    // Tint the summary with the type's own color.
    const color = /^#[0-9a-f]{6}$/i.test(opt.dataset.color || '') ? opt.dataset.color : '#727cf5';
    const card  = document.getElementById('selected-type-card');
    card.style.borderColor     = color + '66';
    card.style.backgroundColor = color + '0d';
    card.style.borderInlineStart = '6px solid ' + color;
    const icon = document.getElementById('selected-type-icon');
    icon.style.backgroundColor = color + '33';
    icon.style.color           = color;
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

    checkPersonDetails();   // what this document needs from the selected person

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

// ── Purpose searchable dropdown ─────────────────────────────────────────
function clearPurposeError() {
    document.getElementById('purpose-error')?.classList.add('hidden');
    document.getElementById('purpose-search')?.classList.remove('border-danger');
}

function purposeEls() {
    return {
        wrap:    document.getElementById('purpose-dd'),
        search:  document.getElementById('purpose-search'),
        hidden:  document.getElementById('purpose-input'),
        list:    document.getElementById('purpose-list'),
        chevron: document.getElementById('purpose-chevron'),
        addRow:  document.getElementById('purpose-add-new'),
        addLbl:  document.getElementById('purpose-add-label'),
        empty:   document.getElementById('purpose-empty'),
    };
}

function openPurposeDropdown() {
    const el = purposeEls();
    el.list.classList.remove('hidden');
    el.chevron.classList.replace('mgc_down_line', 'mgc_up_line');
    filterPurposeOptions();
}

function closePurposeDropdown() {
    const el = purposeEls();
    el.list.classList.add('hidden');
    el.chevron.classList.replace('mgc_up_line', 'mgc_down_line');
}

function togglePurposeDropdown() {
    const el = purposeEls();
    if (el.list.classList.contains('hidden')) { el.search.focus(); openPurposeDropdown(); }
    else closePurposeDropdown();
}

// Filter the options against the current search text; manage the "add new" row.
function filterPurposeOptions() {
    const el = purposeEls();
    const q  = el.search.value.trim().toLowerCase();
    let visible = 0, exact = false;

    document.querySelectorAll('.purpose-option').forEach(opt => {
        const name = opt.dataset.name;
        const match = !q || name.toLowerCase().includes(q);
        opt.classList.toggle('hidden', !match);
        if (match) visible++;
        if (name.toLowerCase() === q) exact = true;
    });

    // Offer "Use <typed>" only when there's text and no exact existing match.
    if (q && !exact) {
        el.addLbl.textContent = el.search.value.trim();
        el.addRow.classList.remove('hidden');
    } else {
        el.addRow.classList.add('hidden');
    }

    el.empty.classList.toggle('hidden', visible > 0 || (q && !exact));
}

function onPurposeSearch() {
    clearPurposeError();
    openPurposeDropdown();
    // While typing, the hidden value tracks the text (so a brand-new purpose is
    // captured even without clicking the "Use ..." row).
    purposeEls().hidden.value = purposeEls().search.value.trim();
}

// Pick an existing option: set both the display and the submitted value.
function selectPurpose(name) {
    const el = purposeEls();
    el.search.value = name;
    el.hidden.value = name;
    clearPurposeError();
    closePurposeDropdown();
}

// Commit the typed text as the purpose (new or existing — server dedupes).
function selectTypedPurpose() {
    selectPurpose(purposeEls().search.value.trim());
}

function onPurposeKeydown(e) {
    const el = purposeEls();
    if (e.key === 'Enter') {
        e.preventDefault();
        // First visible option, else the typed value.
        const first = document.querySelector('.purpose-option:not(.hidden)');
        if (first && first.dataset.name.toLowerCase() === el.search.value.trim().toLowerCase()) {
            selectPurpose(first.dataset.name);
        } else if (el.search.value.trim()) {
            selectTypedPurpose();
        }
    } else if (e.key === 'Escape') {
        closePurposeDropdown();
    }
}

// Close when clicking outside the dropdown.
document.addEventListener('click', function (e) {
    const wrap = document.getElementById('purpose-dd');
    if (wrap && !wrap.contains(e.target)) closePurposeDropdown();
});

function openRequestReview() {
    const form = document.getElementById('request-form');

    // Purpose is required.
    const purposeInput  = document.getElementById('purpose-input');
    const purposeSearch = document.getElementById('purpose-search');
    if (purposeInput && purposeInput.value.trim() === '') {
        document.getElementById('purpose-error')?.classList.remove('hidden');
        purposeSearch?.classList.add('border-danger');
        purposeSearch?.focus();
        return;
    }

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

    if (selectedWalkin && !document.getElementById('care_of_citizen_id').value) {
        const err = document.getElementById('careof-error');
        err.textContent = 'Choose the resident this walk-in is care of.';
        err.classList.remove('hidden');
        document.getElementById('careof-box').scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('careof-search').focus();
        return;
    }

    if (selectedHold) {
        Swal.fire({
            icon: 'error',
            title: 'Citizen is on hold',
            text: 'This citizen cannot be issued documents. Reason: ' + selectedHold,
            didOpen: () => {
                document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#727cf5', 'important');
            },
        });
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

            // Start the scroll at the top of the paper (header first).
            const scrollBody = document.querySelector('#request-review-modal .overflow-y-auto');
            if (scrollBody) scrollBody.scrollTop = 0;
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

    const sizes = {
        a4:          ['8.27in', '11.69in'],
        letter:      ['8.5in',  '11in'],
        half_letter: ['5.5in',  '8.5in'],
        long:        ['8.5in',  '13in'],
    };
    let [pw, ph] = sizes[data.paper_size] || sizes.letter;
    if (data.orientation === 'landscape') { [pw, ph] = [ph, pw]; }
    paper.style.width     = pw;
    paper.style.minWidth  = pw;
    // Render at the full sheet height; grow taller if content overflows.
    paper.style.height    = 'auto';
    paper.style.minHeight = ph;

    paper.style.padding = `${p.top}px ${p.right}px ${p.bottom}px ${p.left}px`;
    if (data.bg_url) {
        paper.style.backgroundImage    = `url('${data.bg_url}')`;
        paper.style.backgroundSize     = 'cover';
        paper.style.backgroundPosition = 'center';
        paper.style.backgroundRepeat   = 'no-repeat';
    } else {
        paper.style.backgroundImage = 'none';
    }

    // Only show the auto-generated barangay header when there is NO letterhead
    // background image (a letterhead already prints its own header).
    const header = data.bg_url ? '' : (data.header || '');

    if (data.allow_body_edit) {
        // Header stays fixed; the body is editable and captured on submit.
        paper.innerHTML = header +
            '<div id="editable-body" contenteditable="true" style="outline:none; min-height:60px;">' +
            (data.body || '') + '</div>';
        document.getElementById('body-edit-hint')?.classList.remove('hidden');
    } else {
        paper.innerHTML = header + (data.body || '');
        document.getElementById('body-edit-hint')?.classList.add('hidden');
    }
}

function renderReviewSummary(summary) {
    document.getElementById('review-citizen-name').textContent  = summary.citizen_name;
    const careOfEl = document.getElementById('review-care-of');
    careOfEl.textContent = summary.care_of ? 'Walk-in · care of ' + summary.care_of : '';
    careOfEl.classList.toggle('hidden', !summary.care_of);
    document.getElementById('review-document-type').textContent = summary.document_type;
    document.getElementById('review-purpose').textContent       = summary.purpose || '—';
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

    // If the type allowed editing, capture the edited body into the form.
    const editable = document.getElementById('editable-body');
    if (editable) {
        document.getElementById('body_override_input').value = editable.innerHTML;
    }

    const form = document.getElementById('request-form');
    const btn  = document.getElementById('review-confirm-btn');
    btn.disabled  = true;
    btn.innerHTML = `<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg> Submitting…`;

    // Submit via AJAX so we can catch errors in THIS tab and only open the new
    // tab on success.
    fetch(form.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: new FormData(form),
    })
        .then(async r => ({ ok: r.ok, status: r.status, data: await r.json().catch(() => ({})) }))
        .then(({ ok, data }) => {
            if (ok && data.show_url) {
                // Open the created request in a NEW TAB; current tab asks what next.
                window.open(data.show_url, '_blank');
                closeRequestReview();
                resetConfirmBtn();
                askAfterSubmit();
            } else {
                // Validation / permission error — show it in THIS tab.
                const msgs = Object.values(data.errors || { _: ['Something went wrong. Please review and try again.'] }).flat();
                closeRequestReview();
                resetConfirmBtn();
                Swal.fire({
                    title: 'Could not submit',
                    html: msgs.map(m => escHtml(m)).join('<br>'),
                    icon: 'error',
                    confirmButtonText: 'OK',
                    didOpen: () => document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#727cf5', 'important'),
                });
            }
        })
        .catch(() => {
            resetConfirmBtn();
            Swal.fire({
                title: 'Network error',
                text: 'Could not reach the server. Please try again.',
                icon: 'error',
                didOpen: () => document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#727cf5', 'important'),
            });
        });
}

function resetConfirmBtn() {
    const btn = document.getElementById('review-confirm-btn');
    btn.disabled  = false;
    btn.innerHTML = '<i class="mgc_check_circle_line"></i> Confirm &amp; Submit';
}

// After submitting: prompt to create another request or go back to the list.
function askAfterSubmit() {
    if (typeof Swal === 'undefined') { resetRequestForm(); return; }
    Swal.fire({
        title: 'Request submitted',
        html: 'The document opened in a new tab.<br>What would you like to do next?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Create another',
        cancelButtonText: 'Back to requests',
        allowOutsideClick: false,
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#727cf5', 'important');
            document.querySelector('.swal2-cancel')?.style.setProperty('background-color', '#6c757d', 'important');
        },
    }).then(r => {
        if (r.isConfirmed) {
            resetRequestForm();
        } else {
            window.location.href = '{{ route('documents.requests.index') }}';
        }
    });
}

// Clear the form so the current tab is ready for a fresh request.
function resetRequestForm() {
    const form = document.getElementById('request-form');
    form.target = '';           // back to same-tab for safety
    form.reset();

    // Reset app-specific state.
    clearCitizen();
    selectedId = '';
    document.getElementById('citizen_id').value = '';
    document.getElementById('body_override_input').value = '';

    // Reset document type selection + custom fields.
    const typeSelect = document.getElementById('document_type_id');
    if (typeSelect) { typeSelect.value = ''; }
    document.getElementById('selected-type-card')?.classList.add('hidden');
    document.getElementById('type-cards-picker')?.classList.remove('hidden');
    document.getElementById('custom-fields-section')?.classList.add('hidden');

    // Reset purpose dropdown.
    const ps = document.getElementById('purpose-search');
    if (ps) ps.value = '';
    const pi = document.getElementById('purpose-input');
    if (pi) pi.value = '';

    window.scrollTo({ top: 0, behavior: 'smooth' });
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
                if (c) selectCitizen(c.id, c.name, c.address, c.contact, c.qrcode, c.profile ?? '', { voter: c.voter, senior: c.senior, pwd: c.pwd, solo: c.solo, hold: c.hold ?? null });
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
