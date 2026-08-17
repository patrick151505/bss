@extends('layouts.vertical', [
    'title'         => 'Grant Cash Advance',
    'sub_title'     => 'Cash Advances',
    'sub_title_url' => route('budget.cash-advances.index'),
    'tagline'       => 'Grant Cash Advance',
])

@section('content')

<div class="card max-w-2xl mx-auto">
    <div class="card-header"><h5 class="card-title">Cash Advance Request</h5></div>
    <div class="p-6">
        @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
            <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
            <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
            <div>
                <p class="text-sm font-medium text-danger mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('budget.cash-advances.store') }}">
            @csrf

            <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYear?->id }}">

            @if(!$fiscalYear)
                <div class="mb-4 p-4 rounded-lg bg-warning/10 border border-warning/30 flex gap-3">
                    <i class="mgc_alert_line text-warning text-xl mt-0.5 shrink-0"></i>
                    <p class="text-sm text-warning font-medium">No active fiscal year. Cannot grant a cash advance.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="form-label">Accountable Officer <span class="text-danger">*</span></label>
                    <select name="officer_id" class="form-select" required>
                        <option value="">— select officer —</option>
                        @foreach($officers as $officer)
                            <option value="{{ $officer->id }}" {{ old('officer_id') == $officer->id ? 'selected' : '' }}>
                                {{ $officer->name }}
                                @if($officer->position) ({{ $officer->position }}) @endif
                                @if($officer->hasOpenAdvance()) ⚠ has open CA @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Officers with ⚠ cannot receive a new advance until the open one is liquidated.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Purpose <span class="text-danger">*</span></label>
                    <input type="text" name="purpose" class="form-input" required value="{{ old('purpose') }}"
                        placeholder="e.g. Travel expenses for municipal meeting">
                </div>

                <div>
                    <label class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-input" step="0.01" min="0.01" required
                        value="{{ old('amount') }}">
                </div>

                <div>
                    <label class="form-label">Allocation Line</label>
                    <select name="allocation_id" class="form-select">
                        <option value="">— no specific line —</option>
                        @foreach($allocations as $alloc)
                            <option value="{{ $alloc->id }}" {{ old('allocation_id') == $alloc->id ? 'selected' : '' }}>
                                [{{ $alloc->object_class }}] {{ $alloc->program?->name ?? 'Unassigned' }}
                                (Balance: ₱{{ number_format($alloc->balance(), 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Date Granted <span class="text-danger">*</span></label>
                    <input type="date" name="date_granted" class="form-input" required value="{{ old('date_granted', date('Y-m-d')) }}">
                </div>

                <div>
                    <label class="form-label">Liquidation Deadline <span class="text-danger">*</span></label>
                    <input type="date" name="deadline_date" class="form-input" required value="{{ old('deadline_date') }}">
                    <p class="text-xs text-gray-400 mt-1">COA: 60 days in-station, 90 days for travel</p>
                </div>

                <div>
                    <label class="form-label">DV Reference No.</label>
                    <input type="text" name="reference_no" class="form-input" placeholder="DV No." value="{{ old('reference_no') }}">
                </div>

                <div>
                    <label class="form-label">Approved By</label>
                    <input type="text" name="approved_by" class="form-input" value="{{ old('approved_by') }}">
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn bg-primary text-white {{ !$fiscalYear ? 'opacity-50 pointer-events-none' : '' }}" @if(!$fiscalYear) disabled @endif>
                    <i class="mgc_wallet_line me-1"></i> Grant Cash Advance
                </button>
                <a href="{{ route('budget.cash-advances.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
