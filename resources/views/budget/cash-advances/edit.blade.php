@extends('layouts.vertical', [
    'title'         => 'Edit Cash Advance',
    'sub_title'     => 'Cash Advances',
    'sub_title_url' => route('budget.cash-advances.index'),
    'tagline'       => 'Edit ' . $cashAdvance->ca_no,
])

@section('content')
<div class="card max-w-2xl mx-auto">
    <div class="card-header"><h5 class="card-title">Edit Cash Advance — {{ $cashAdvance->ca_no }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('budget.cash-advances.update', $cashAdvance) }}">
            @csrf @method('PUT')

            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
                <p class="text-gray-500">Officer: <span class="font-medium">{{ $cashAdvance->officer->name }}</span></p>
                <p class="text-gray-500">Amount: <span class="font-medium text-primary">₱{{ number_format($cashAdvance->amount, 2) }}</span></p>
                <p class="text-xs text-gray-400 mt-1">Officer and amount cannot be changed after creation.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="form-label">Purpose <span class="text-danger">*</span></label>
                    <input type="text" name="purpose" class="form-input" required value="{{ old('purpose', $cashAdvance->purpose) }}">
                </div>
                <div>
                    <label class="form-label">Liquidation Deadline <span class="text-danger">*</span></label>
                    <input type="date" name="deadline_date" class="form-input" required
                        value="{{ old('deadline_date', $cashAdvance->deadline_date->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="form-label">DV Reference No.</label>
                    <input type="text" name="reference_no" class="form-input"
                        value="{{ old('reference_no', $cashAdvance->reference_no) }}">
                </div>
                <div>
                    <label class="form-label">Approved By</label>
                    <input type="text" name="approved_by" class="form-input"
                        value="{{ old('approved_by', $cashAdvance->approved_by) }}">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2">{{ old('notes', $cashAdvance->notes) }}</textarea>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('budget.cash-advances.show', $cashAdvance) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
