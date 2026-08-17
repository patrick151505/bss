@extends('layouts.vertical', ['title' => 'Document Request', 'sub_title' => 'Documents', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

@php
    $badges = [
        'pending'           => ['pill' => 'bg-yellow-100 text-yellow-800', 'label' => 'Pending'],
        'approved'          => ['pill' => 'bg-sky-100 text-sky-800',       'label' => 'Approved'],
        'ready_for_release' => ['pill' => 'bg-yellow-100 text-yellow-800', 'label' => 'Ready for Release'],
        'released'          => ['pill' => 'bg-green-100 text-green-800',   'label' => 'Released'],
        'rejected'          => ['pill' => 'bg-red-100 text-red-800',       'label' => 'Rejected'],
    ];
    $b = $badges[$documentRequest->status] ?? ['pill' => 'bg-gray-100 text-gray-700', 'label' => ucfirst(str_replace('_', ' ', $documentRequest->status))];
    // Both 'approved' and 'ready_for_release' are awaiting release.
    $awaitingRelease = in_array($documentRequest->status, ['approved', 'ready_for_release']);
@endphp

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('documents.requests.index') }}" class="text-gray-400 hover:text-primary">
            <i class="mgc_arrow_left_line text-lg"></i>
        </a>
        <div>
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Request #{{ str_pad($documentRequest->id, 4, '0', STR_PAD_LEFT) }}
            </h4>
            <p class="text-sm text-gray-400">{{ $documentRequest->documentType->name ?? '—' }}</p>
        </div>
        <span class="inline-flex items-center py-1.5 px-3 rounded-full text-sm font-medium {{ $b['pill'] }}">
            {{ $b['label'] }}
        </span>
    </div>

    {{-- Action buttons based on status --}}
    <div class="flex gap-2">
        @if($documentRequest->status === 'pending')
            <form action="{{ route('documents.requests.approve', $documentRequest) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit" class="btn bg-info text-white flex items-center gap-2">
                    <i class="mgc_shield_check_line"></i> Approve
                </button>
            </form>
            <button onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                    class="btn border-danger/40 text-danger flex items-center gap-2">
                <i class="mgc_close_circle_line"></i> Reject
            </button>
        @endif

        @if($awaitingRelease)
            <button onclick="document.getElementById('release-modal').classList.remove('hidden')"
                    class="btn bg-success text-white flex items-center gap-2">
                <i class="mgc_check_circle_line"></i> Release
            </button>
        @endif

        @if($documentRequest->status === 'released')
            <button onclick="requestPrint()"
                    class="btn bg-primary text-white flex items-center gap-2">
                <i class="mgc_print_line"></i> Print
                <span id="print-count-badge" class="ml-1 text-xs bg-white/25 rounded-full px-1.5 py-0.5 {{ $documentRequest->print_count > 0 ? '' : 'hidden' }}">
                    Printed <span id="print-count-num">{{ $documentRequest->print_count }}</span>×
                </span>
            </button>
        @endif
    </div>
</div>

<div class="grid grid-cols-12 gap-6">

    {{-- Left: Details --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-5">

        {{-- Citizen Info --}}
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                <i class="mgc_user_3_line text-primary"></i> Citizen
            </h6>
            <div class="space-y-2 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Full Name</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $documentRequest->citizen->full_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Age</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->citizen->age ?? '—' }} years old</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Address</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->citizen->complete_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Civil Status</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ optional($documentRequest->citizen->civilStatus)->name ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Request Info --}}
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                <i class="mgc_document_2_line text-primary"></i> Request Info
            </h6>
            <div class="space-y-2 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Document Type</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $documentRequest->documentType->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Fee</p>
                    @if($documentRequest->is_paid)
                        <p class="font-semibold text-warning">₱ {{ number_format($documentRequest->fee, 2) }}
                            @if($documentRequest->fee_paid)
                                <span class="text-success text-xs ml-1">✓ Paid</span>
                            @else
                                <span class="text-danger text-xs ml-1">Unpaid</span>
                            @endif
                        </p>
                    @else
                        <p class="font-semibold text-success">FREE</p>
                    @endif
                </div>
                @if($documentRequest->is_paid && $documentRequest->amount_paid !== null)
                @php $change = (float) $documentRequest->amount_paid - (float) $documentRequest->fee; @endphp
                <div>
                    <p class="text-xs text-gray-400">Amount Paid</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">₱ {{ number_format($documentRequest->amount_paid, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Change</p>
                    <p class="font-semibold {{ $change < 0 ? 'text-danger' : 'text-gray-800 dark:text-gray-100' }}">
                        ₱ {{ number_format(max($change, 0), 2) }}
                        @if($change < 0)<span class="text-danger text-xs ml-1">(short ₱ {{ number_format(abs($change), 2) }})</span>@endif
                    </p>
                </div>
                @endif
                @if($documentRequest->or_number)
                <div>
                    <p class="text-xs text-gray-400">OR Number</p>
                    <p class="font-mono text-gray-700 dark:text-gray-300">{{ $documentRequest->or_number }}</p>
                </div>
                @endif
                @if($documentRequest->purpose)
                <div>
                    <p class="text-xs text-gray-400">Purpose</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->purpose }}</p>
                </div>
                @endif
                <div>
                    <p class="text-xs text-gray-400">Requested</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->created_at->format('M d, Y g:i A') }}</p>
                    @if($documentRequest->createdBy)
                    <p class="text-xs text-gray-400">by {{ $documentRequest->createdBy->name }}</p>
                    @endif
                </div>
                @if($documentRequest->approved_at)
                <div>
                    <p class="text-xs text-gray-400">Approved</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->approved_at->format('M d, Y g:i A') }}</p>
                    @if($documentRequest->approvedBy)
                    <p class="text-xs text-gray-400">by {{ $documentRequest->approvedBy->name }}</p>
                    @endif
                </div>
                @endif
                @if($documentRequest->released_at)
                <div>
                    <p class="text-xs text-gray-400">Released</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->released_at->format('M d, Y g:i A') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Times Printed</p>
                    <p class="text-gray-700 dark:text-gray-300"><span id="print-count-info">{{ $documentRequest->print_count }}</span>×</p>
                </div>
                @endif
                @if($documentRequest->remarks)
                <div>
                    <p class="text-xs text-gray-400">Remarks</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->remarks }}</p>
                </div>
                @endif
            </div>

            {{-- Custom Fields --}}
            @if(!empty($documentRequest->custom_fields))
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Custom Fields</p>
                @foreach($documentRequest->documentType->fields as $field)
                <div>
                    <p class="text-xs text-gray-400">{{ $field->field_label }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $documentRequest->custom_fields[$field->field_key] ?? '—' }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Right: Certificate Preview --}}
    <div class="col-span-12 lg:col-span-8">
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_quill_pen_line text-primary"></i> Certificate Preview
                </h6>
                <button onclick="requestPrint()"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3 flex items-center gap-1">
                    <i class="mgc_print_line"></i> Print
                </button>
            </div>

            @if($preview)
            @php
                $ver       = $documentRequest->templateVersion;
                $bgUrl     = $ver?->paper_bg ? asset('storage/' . $ver->paper_bg) : null;
                $padTop    = $ver?->padding_top    ?? 50;
                $padBottom = $ver?->padding_bottom ?? 20;
                $padLeft   = $ver?->padding_left   ?? 50;
                $padRight  = $ver?->padding_right  ?? 50;
                [$paperW, $paperH] = match ($ver?->paper_size) {
                    'a4'          => ['8.27in', '11.69in'],
                    'half_letter' => ['5.5in',  '8.5in'],
                    'long'        => ['8.5in',  '13in'],
                    default       => ['8.5in',  '11in'],
                };
                if (($ver?->orientation ?? 'portrait') === 'landscape') { [$paperW, $paperH] = [$paperH, $paperW]; }
            @endphp
            <div style="background:#e5e7eb; padding:24px; border-radius:0.5rem; overflow-x:auto;">
                <div id="certificate-body"
                     style="
                        width: {{ $paperW }};
                        min-width: {{ $paperW }};
                        min-height: {{ $paperH }};
                        background-color: #fff;
                        border: 1px solid #ccc;
                        padding: {{ $padTop }}px {{ $padRight }}px {{ $padBottom }}px {{ $padLeft }}px;
                        box-sizing: border-box;
                        box-shadow: 0 0 10px rgba(0,0,0,0.1);
                        position: relative;
                        margin: 0 auto;
                        font-family: 'Times New Roman', Times, serif;
                        font-size: 14px;
                        line-height: 1.8;
                        color: #111827;
                        {{ $bgUrl ? "background-image: url('{$bgUrl}'); background-size: cover; background-position: center; background-repeat: no-repeat;" : '' }}
                     ">
                    {{-- Diagonal "NOT YET RELEASED" watermark until the document is released --}}
                    @if(!in_array($documentRequest->status, ['released', 'rejected']))
                    <div class="cert-watermark">{{ strtoupper($documentRequest->status) }} — NOT YET RELEASED</div>
                    @endif
                    {{-- Auto barangay header only when there's no letterhead background --}}
                    @unless($bgUrl)
                        {!! $header !!}
                    @endunless
                    {!! $preview !!}
                </div>
            </div>
            @else
            <div class="bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center text-gray-400">
                <i class="mgc_document_2_line text-3xl block mb-2"></i>
                <p class="text-sm">No template set for this document type.</p>
                <a href="{{ route('documents.types.edit', $documentRequest->document_type_id) }}"
                   class="text-primary text-xs hover:underline mt-1 inline-block">Edit Template →</a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Release Modal --}}
<div id="release-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h6 class="font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
            <i class="mgc_check_circle_line text-success"></i> Release Document
        </h6>
        <form action="{{ route('documents.requests.release', $documentRequest) }}" method="POST" id="release-form">
            @csrf @method('PATCH')
            @if($documentRequest->is_paid)
            {{-- Fee + payment calculator (Amount Paid / Change are display-only helpers) --}}
            <div class="mb-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Fee to collect</span>
                    <span class="font-semibold text-warning" id="release-fee" data-fee="{{ (float) $documentRequest->fee }}">
                        ₱ {{ number_format($documentRequest->fee, 2) }}
                    </span>
                </div>
                <div>
                    <label class="form-label text-sm mb-1">Amount Paid</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">₱</span>
                        <input type="number" name="amount_paid" id="amount-paid" class="form-input pl-7" min="0" step="0.01"
                               placeholder="0.00" oninput="computeChange()">
                    </div>
                </div>
                <div class="flex items-center justify-between text-sm pt-1 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-gray-500 dark:text-gray-400">Change</span>
                    <span class="font-bold text-lg" id="release-change">₱ 0.00</span>
                </div>
                <p id="short-warning" class="hidden text-xs text-danger">Amount paid is less than the fee.</p>
            </div>

            {{-- OR number --}}
            <div class="mb-3">
                <label class="form-label text-sm">Official Receipt (OR) Number <span class="text-danger" id="or-required-star">*</span></label>
                <input type="text" name="or_number" id="or-input" class="form-input" placeholder="e.g. 00123456" required>
            </div>

            {{-- No OR toggle --}}
            <label class="flex items-center gap-2 mb-3 cursor-pointer">
                <input type="checkbox" name="no_or" value="1" id="no-or-toggle" class="form-checkbox" onchange="toggleNoOr(this)">
                <span class="text-sm text-gray-600 dark:text-gray-300">No OR (Official Receipt) available</span>
            </label>

            {{-- Reason note (required only when No OR is ticked) --}}
            <div id="no-or-reason-wrap" class="hidden mb-4">
                <label class="form-label text-sm">Reason <span class="text-danger">*</span></label>
                <input type="text" name="no_or_reason" id="no-or-reason" class="form-input"
                       placeholder="e.g. fee waived, paid earlier" maxlength="255">
                <p class="text-xs text-gray-400 mt-1">Recorded in place of the OR number for the audit trail.</p>
            </div>
            @else
            <input type="hidden" name="or_number" value="">
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">This document is FREE. Confirm release to the citizen.</p>
            @endif
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('release-modal').classList.add('hidden')"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">Cancel</button>
                <button type="submit" class="btn bg-success text-white">Confirm Release</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h6 class="font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
            <i class="mgc_close_circle_line text-danger"></i> Reject Request
        </h6>
        <form action="{{ route('documents.requests.reject', $documentRequest) }}" method="POST">
            @csrf @method('PATCH')
            <div class="mb-4">
                <label class="form-label text-sm">Reason for Rejection <span class="text-danger">*</span></label>
                <textarea name="remarks" rows="3" class="form-input" required
                          placeholder="State the reason for rejection…"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">Cancel</button>
                <button type="submit" class="btn bg-danger text-white">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<style>
/* Diagonal watermark shown across the paper while the document isn't released,
   so staff are visually pushed to release it. Non-interactive; sits above the
   content but doesn't block clicks. */
.cert-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-family: Arial, sans-serif;
    font-size: 46px;
    font-weight: 800;
    letter-spacing: 3px;
    color: rgba(220, 38, 38, 0.18);
    border: 4px solid rgba(220, 38, 38, 0.18);
    border-radius: 8px;
    padding: 10px 28px;
    white-space: nowrap;
    text-align: center;
    pointer-events: none;
    user-select: none;
    z-index: 5;
}

/* Document typography reset — restore normal defaults inside the certificate
   only, since the app's Tailwind Preflight zeroes p/heading margins and list
   bullets globally. Keeps the rendered/printed document matching the editor. */
#certificate-body p { margin: 0 0 5px 0; line-height: 1.4; }
#certificate-body h1 { font-size: 2em;    font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#certificate-body h2 { font-size: 1.5em;  font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#certificate-body h3 { font-size: 1.17em; font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#certificate-body h4 { font-size: 1em;    font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
#certificate-body h5 { font-size: 0.83em; font-weight: bold; margin: 0.4em 0; }
#certificate-body h6 { font-size: 0.75em; font-weight: bold; margin: 0.4em 0; }
#certificate-body ul { list-style: disc;    margin: 0 0 10px 0; padding-left: 40px; }
#certificate-body ol { list-style: decimal; margin: 0 0 10px 0; padding-left: 40px; }
#certificate-body li { margin: 0; }
#certificate-body blockquote { margin: 0 0 10px 40px; }
#certificate-body strong, #certificate-body b { font-weight: bold; }
#certificate-body em, #certificate-body i { font-style: italic; }
#certificate-body u { text-decoration: underline; }
#certificate-body a { color: inherit; text-decoration: underline; }
#certificate-body hr { border: 0; border-top: 1px solid #999; margin: 12px 0; }

#certificate-body table { border-collapse: collapse; width: 100%; margin: 8px 0; }
#certificate-body td, #certificate-body th { border: 1px solid #d1d5db; padding: 3px 0px; vertical-align: top; }
#certificate-body th { background: #f3f4f6; font-weight: 600; }
#certificate-body table.no-border,
#certificate-body table.no-border td,
#certificate-body table.no-border th { border: none; background: transparent; }
#certificate-body table.no-border td.border,
#certificate-body table.no-border th.border,
#certificate-body td.border,
#certificate-body th.border { border-bottom: 1px solid #000; }

@page { size: {{ $paperW ?? '8.5in' }} {{ $paperH ?? '11in' }}; margin: 0; }

@media print {
    body * { visibility: hidden; }
    #certificate-body, #certificate-body * { visibility: visible; }
    #certificate-body {
        position: fixed !important;
        top: 0 !important; left: 0 !important;
        width: {{ $paperW ?? '8.5in' }} !important;
        min-height: {{ $paperH ?? '11in' }} !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

<script>
// ── Release modal: payment calculator + No-OR toggle ──────────────────
function computeChange() {
    const feeEl  = document.getElementById('release-fee');
    if (!feeEl) return;
    const fee    = parseFloat(feeEl.dataset.fee) || 0;
    const paid   = parseFloat(document.getElementById('amount-paid').value) || 0;
    const change = paid - fee;

    const changeEl = document.getElementById('release-change');
    const warnEl   = document.getElementById('short-warning');
    const short    = paid > 0 && change < 0;

    changeEl.textContent = '₱ ' + (change < 0 ? 0 : change).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    changeEl.classList.toggle('text-danger', short);
    changeEl.classList.toggle('text-success', !short && paid > 0);
    warnEl.classList.toggle('hidden', !short);
}

// On submit, warn + confirm if the amount paid is short of the fee.
(function () {
    const form = document.getElementById('release-form');
    if (!form) return;
    let confirmedShort = false;

    form.addEventListener('submit', function (e) {
        if (confirmedShort) return; // already confirmed — let it through

        const feeEl = document.getElementById('release-fee');
        const paidEl = document.getElementById('amount-paid');
        if (!feeEl || !paidEl) return; // not a paid document

        const fee  = parseFloat(feeEl.dataset.fee) || 0;
        const paid = parseFloat(paidEl.value);

        // Only warn when an amount was entered and it's short.
        if (!isNaN(paid) && paid < fee) {
            e.preventDefault();
            const shortBy = (fee - paid).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            Swal.fire({
                title: 'Amount is short',
                html: `The amount paid (₱ ${paid.toLocaleString('en-PH', { minimumFractionDigits: 2 })}) is <strong>₱ ${shortBy}</strong> less than the fee. Release anyway?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, release anyway',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                didOpen: () => {
                    document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#fa5c7c', 'important');
                },
            }).then(r => {
                if (r.isConfirmed) { confirmedShort = true; form.submit(); }
            });
        }
    });
})();

function toggleNoOr(cb) {
    const orInput  = document.getElementById('or-input');
    const orStar   = document.getElementById('or-required-star');
    const reasonWrap = document.getElementById('no-or-reason-wrap');
    const reason   = document.getElementById('no-or-reason');

    if (cb.checked) {
        // OR not required; capture a reason instead.
        orInput.value = '';
        orInput.required = false;
        orInput.disabled = true;
        orInput.classList.add('opacity-50');
        orStar.classList.add('hidden');
        reasonWrap.classList.remove('hidden');
        reason.required = true;
    } else {
        orInput.disabled = false;
        orInput.required = true;
        orInput.classList.remove('opacity-50');
        orStar.classList.remove('hidden');
        reasonWrap.classList.add('hidden');
        reason.required = false;
        reason.value = '';
    }
}

// ── Print counter ───────────────────────────────────────────────────────
const PRINT_URL   = '{{ route('documents.requests.print', $documentRequest) }}';
const PRINT_CSRF  = document.querySelector('meta[name="csrf-token"]')?.content;

// Update both count displays from a value.
function setPrintCount(n) {
    if (n === undefined) return;
    const num  = document.getElementById('print-count-num');
    const info = document.getElementById('print-count-info');
    if (num)  num.textContent  = n;
    if (info) info.textContent = n;
    document.getElementById('print-count-badge')?.classList.toggle('hidden', n <= 0);
}

// Adjust the server counter (+1 print, -1 undo). Returns a promise of the count.
function adjustPrintCount(step) {
    return fetch(PRINT_URL, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': PRINT_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ step: step }),
    }).then(r => r.json());
}

// Bump the counter (+1), then open the print dialog. No undo.
function doCountedPrint() {
    adjustPrintCount(1)
        .then(data => { setPrintCount(data.print_count); })
        .catch(() => {})
        .finally(() => { window.print(); });
}

// Print button → confirm first, then count + print.
function requestPrint() {
    if (typeof Swal === 'undefined') { doCountedPrint(); return; }
    Swal.fire({
        title: 'Print this document?',
        text: 'This adds +1 to the print count (this cannot be undone) and opens the print dialog.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, print',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm')?.style.setProperty('background-color', '#727cf5', 'important');
            document.querySelector('.swal2-cancel')?.style.setProperty('background-color', '#6c757d', 'important');
        },
    }).then(r => { if (r.isConfirmed) doCountedPrint(); });
}

// Block the browser's native print (Ctrl/Cmd + P) so all prints go through
// the button and get counted.
document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'info',
                title: 'Use the Print button so the print is recorded.',
                showConfirmButton: false, timer: 2200, timerProgressBar: true,
            });
        }
        return false;
    }
});

// Auto-print once when a document was just issued/released. This counts too.
// Triggered by a one-time session flash (normal flow) or ?auto_print=1 (the
// create page's AJAX new-tab flow). Either way it fires only on this load.
@if(session('auto_print') || request()->boolean('auto_print'))
window.addEventListener('load', function () {
    setTimeout(doCountedPrint, 400);
});
@endif
</script>
@endsection
