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
    $colors = ['pending'=>'warning','approved'=>'info','released'=>'success','rejected'=>'danger'];
    $color  = $colors[$documentRequest->status] ?? 'secondary';
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
        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $color }}/15 text-{{ $color }}">
            {{ ucfirst($documentRequest->status) }}
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

        @if($documentRequest->status === 'approved')
            <button onclick="document.getElementById('release-modal').classList.remove('hidden')"
                    class="btn bg-success text-white flex items-center gap-2">
                <i class="mgc_check_circle_line"></i> Release
            </button>
        @endif

        @if($documentRequest->status === 'released')
            <button onclick="window.print()"
                    class="btn bg-primary text-white flex items-center gap-2">
                <i class="mgc_print_line"></i> Print
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
                @if($documentRequest->or_number)
                <div>
                    <p class="text-xs text-gray-400">OR Number</p>
                    <p class="font-mono text-gray-700 dark:text-gray-300">{{ $documentRequest->or_number }}</p>
                </div>
                @endif
                <div>
                    <p class="text-xs text-gray-400">Requested</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $documentRequest->created_at->format('M d, Y g:i A') }}</p>
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
                <button onclick="window.print()"
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
            @endphp
            <div style="background:#e5e7eb; padding:24px; border-radius:0.5rem; overflow-x:auto;">
                <div id="certificate-body"
                     style="
                        width: 8.5in;
                        min-width: 8.5in;
                        min-height: 11in;
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
        <form action="{{ route('documents.requests.release', $documentRequest) }}" method="POST">
            @csrf @method('PATCH')
            @if($documentRequest->is_paid)
            <div class="mb-4">
                <label class="form-label text-sm">Official Receipt (OR) Number <span class="text-danger">*</span></label>
                <input type="text" name="or_number" class="form-input" placeholder="e.g. 00123456" required>
                <p class="text-xs text-gray-400 mt-1">Fee amount: ₱ {{ number_format($documentRequest->fee, 2) }}</p>
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
table { border-collapse: collapse; width: 100%; margin: 8px 0; }
td, th { border: 1px solid #d1d5db; padding: 6px 10px; vertical-align: top; }
th { background: #f3f4f6; font-weight: 600; }

@media print {
    body * { visibility: hidden; }
    #certificate-body, #certificate-body * { visibility: visible; }
    #certificate-body {
        position: fixed !important;
        top: 0 !important; left: 0 !important;
        width: 8.5in !important;
        min-height: 11in !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
@endsection
