@extends('layouts.vertical', [
    'title'         => 'Edit Voucher',
    'sub_title'     => 'Vouchers',
    'sub_title_url' => route('budget.transactions.index'),
    'tagline'       => 'Edit Voucher',
])

@section('content')

<div class="max-w-3xl mx-auto">
    <div class="card mb-5">
        <div class="card-header"><h5 class="card-title">Edit Voucher</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('budget.transactions.update', $transaction) }}">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                        <select name="fiscal_year_id" class="form-select" required>
                            @foreach($fiscalYears as $fy)
                                <option value="{{ $fy->id }}" {{ $transaction->fiscal_year_id == $fy->id ? 'selected' : '' }}>
                                    {{ $fy->displayLabel() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Voucher Type <span class="text-danger">*</span></label>
                        <select name="voucher_type" class="form-select" required>
                            <option value="">— select —</option>
                            @foreach(\App\Models\BudgetTransaction::VOUCHER_TYPES as $k => $label)
                                <option value="{{ $k }}" {{ $transaction->voucher_type === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Voucher No.</label>
                        <input type="text" name="voucher_no" class="form-input" value="{{ old('voucher_no', $transaction->voucher_no) }}">
                    </div>
                    <div>
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" class="form-input" required
                            value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Models\BudgetTransaction::STATUSES as $k => $cfg)
                                <option value="{{ $k }}" {{ $transaction->status === $k ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Allocation Line</label>
                        <select name="allocation_id" class="form-select">
                            <option value="">— no specific line —</option>
                            @foreach($allocations as $a)
                                <option value="{{ $a->id }}" {{ $transaction->allocation_id == $a->id ? 'selected' : '' }}>
                                    [{{ $a->object_class }}] {{ $a->line_name }}
                                    (₱{{ number_format($a->balance(), 2) }} avail.)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="form-label">Payee</label>
                        <input type="text" name="payee" class="form-input" value="{{ old('payee', $transaction->payee) }}">
                    </div>
                    <div>
                        <label class="form-label">Mode of Payment</label>
                        <select name="mode_of_payment" class="form-select">
                            <option value="">—</option>
                            @foreach(\App\Models\BudgetTransaction::PAYMENT_MODES as $k => $label)
                                <option value="{{ $k }}" {{ $transaction->mode_of_payment === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-input" step="0.01" min="0.01" required
                            value="{{ old('amount', $transaction->amount) }}">
                    </div>
                    <div>
                        <label class="form-label">Tax Withheld (₱)</label>
                        <input type="number" name="tax_withheld" class="form-input" step="0.01" min="0"
                            value="{{ old('tax_withheld', $transaction->tax_withheld) }}">
                    </div>
                    <div>
                        <label class="form-label">Reference No.</label>
                        <input type="text" name="reference_no" class="form-input" value="{{ old('reference_no', $transaction->reference_no) }}">
                    </div>
                    <div>
                        <label class="form-label">Approved By</label>
                        <input type="text" name="approved_by" class="form-input" value="{{ old('approved_by', $transaction->approved_by) }}">
                    </div>
                    <div>
                        <label class="form-label">Check No.</label>
                        <input type="text" name="check_no" class="form-input" value="{{ old('check_no', $transaction->check_no) }}">
                    </div>
                    <div>
                        <label class="form-label">Check Date</label>
                        <input type="date" name="check_date" class="form-input"
                            value="{{ old('check_date', $transaction->check_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Description / Particulars</label>
                        <textarea name="description" class="form-input" rows="2">{{ old('description', $transaction->description) }}</textarea>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Voucher</button>
                    <a href="{{ route('budget.transactions.show', $transaction) }}" class="btn btn-light">Cancel</a>
                    <form method="POST" action="{{ route('budget.transactions.destroy', $transaction) }}" class="ms-auto"
                        onsubmit="return confirm('Delete this voucher?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger-light">Delete</button>
                    </form>
                </div>
            </form>
        </div>
    </div>

    {{-- Attachments --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title">Attachments</h5></div>
        <div class="card-body">
            @if($transaction->attachments->count())
            <div class="space-y-2 mb-4">
                @foreach($transaction->attachments as $att)
                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 rounded-lg px-3 py-2">
                    <div class="flex items-center gap-2">
                        <i class="{{ $att->iconClass() }} text-lg"></i>
                        <div>
                            <p class="text-sm font-medium">{{ $att->original_name }}</p>
                            <p class="text-xs text-gray-400">{{ $att->humanSize() }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('budget.attachments.download', $att) }}" class="btn btn-xs btn-light">Download</a>
                        <form method="POST" action="{{ route('budget.attachments.destroy', $att) }}"
                            onsubmit="return confirm('Remove this file?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger-light">Remove</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('budget.transactions.attachments.store', $transaction) }}"
                enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Upload Files</label>
                    <input type="file" name="files[]" multiple class="form-input"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    <p class="text-xs text-gray-400 mt-1">PDF, images, Word, Excel — max 10 MB each</p>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Upload</button>
            </form>
        </div>
    </div>
</div>
@endsection
