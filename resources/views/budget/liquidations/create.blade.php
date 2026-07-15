@extends('layouts.vertical', [
    'title'         => 'Submit Liquidation',
    'sub_title'     => 'Cash Advances',
    'sub_title_url' => route('budget.cash-advances.index'),
    'tagline'       => 'Liquidation Report — ' . $cashAdvance->ca_no,
])

@section('content')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2">
        <div class="card">
            <div class="card-header"><h5 class="card-title">Liquidation Report</h5></div>
            <div class="card-body">
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-gray-500">CA No.</p>
                        <p class="font-semibold">{{ $cashAdvance->ca_no }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Officer</p>
                        <p class="font-semibold">{{ $cashAdvance->officer->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Amount Granted</p>
                        <p class="font-bold text-primary">₱{{ number_format($cashAdvance->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Deadline</p>
                        <p class="{{ $cashAdvance->isOverdue() ? 'text-danger font-semibold' : '' }}">
                            {{ $cashAdvance->deadline_date->format('M d, Y') }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('budget.liquidations.store', $cashAdvance) }}" id="liq-form">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label class="form-label">Liquidation Date <span class="text-danger">*</span></label>
                            <input type="date" name="liquidation_date" class="form-input" required value="{{ old('liquidation_date', date('Y-m-d')) }}">
                        </div>
                        <div>
                            <label class="form-label">Amount Refunded (₱)</label>
                            <input type="number" name="refund_amount" id="refund-amount" class="form-input" step="0.01" min="0"
                                value="{{ old('refund_amount', 0) }}" oninput="updateBalance()">
                        </div>
                        <div>
                            <label class="form-label">Refund OR No.</label>
                            <input type="text" name="refund_or_no" class="form-input" placeholder="Official Receipt No." value="{{ old('refund_or_no') }}">
                        </div>
                    </div>

                    {{-- Expense Lines --}}
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label mb-0">Expense Lines <span class="text-danger">*</span></label>
                            <button type="button" onclick="addLine()" class="btn btn-xs btn-light">+ Add Line</button>
                        </div>

                        <table class="table table-sm mb-2" id="lines-table">
                            <thead>
                                <tr>
                                    <th>OR No.</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th class="text-end">Amount (₱)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="lines-body">
                                <tr id="line-0">
                                    <td><input type="text" name="lines[0][or_no]" class="form-input form-input-sm" placeholder="OR-0001"></td>
                                    <td><input type="date" name="lines[0][receipt_date]" class="form-input form-input-sm"></td>
                                    <td><input type="text" name="lines[0][particulars]" class="form-input form-input-sm" required placeholder="Description of expense"></td>
                                    <td><input type="number" name="lines[0][amount]" class="form-input form-input-sm text-end line-amount" step="0.01" min="0.01" required oninput="updateBalance()"></td>
                                    <td><button type="button" onclick="removeLine(0)" class="btn btn-xs btn-danger-light">×</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-xs text-gray-400">Total expenses + refund must equal ₱{{ number_format($cashAdvance->amount, 2) }} to close the liquidation.</p>
                    </div>

                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="btn btn-success">Save Liquidation Report</button>
                        <a href="{{ route('budget.cash-advances.show', $cashAdvance) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Running Balance --}}
    <div>
        <div class="card p-5 sticky top-6">
            <h6 class="font-semibold mb-4">Reconciliation</h6>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Amount Granted</span>
                    <span class="font-mono font-semibold">₱{{ number_format($cashAdvance->amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Expenses</span>
                    <span class="font-mono text-danger" id="display-expenses">₱0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Refund</span>
                    <span class="font-mono text-warning" id="display-refund">₱0.00</span>
                </div>
                <div class="flex justify-between font-semibold border-t pt-2">
                    <span>Balance</span>
                    <span class="font-mono" id="display-balance" style="color:inherit">₱{{ number_format($cashAdvance->amount, 2) }}</span>
                </div>
            </dl>
            <p class="text-xs text-gray-400 mt-3" id="balance-note">Balance must be ₱0.00 to close the liquidation.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const caAmount = {{ $cashAdvance->amount }};
let lineCount = 1;

function addLine() {
    const i = lineCount++;
    const row = document.createElement('tr');
    row.id = 'line-' + i;
    row.innerHTML = `
        <td><input type="text" name="lines[${i}][or_no]" class="form-input form-input-sm" placeholder="OR-0001"></td>
        <td><input type="date" name="lines[${i}][receipt_date]" class="form-input form-input-sm"></td>
        <td><input type="text" name="lines[${i}][particulars]" class="form-input form-input-sm" required placeholder="Description"></td>
        <td><input type="number" name="lines[${i}][amount]" class="form-input form-input-sm text-end line-amount" step="0.01" min="0.01" required oninput="updateBalance()"></td>
        <td><button type="button" onclick="removeLine(${i})" class="btn btn-xs btn-danger-light">×</button></td>
    `;
    document.getElementById('lines-body').appendChild(row);
}

function removeLine(i) {
    const row = document.getElementById('line-' + i);
    if (row && document.querySelectorAll('#lines-body tr').length > 1) {
        row.remove();
        updateBalance();
    }
}

function updateBalance() {
    const expenses = Array.from(document.querySelectorAll('.line-amount'))
        .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
    const refund = parseFloat(document.getElementById('refund-amount').value) || 0;
    const balance = caAmount - expenses - refund;
    const fmt = v => '₱' + Math.abs(v).toLocaleString('en-PH', {minimumFractionDigits:2});

    document.getElementById('display-expenses').textContent = fmt(expenses);
    document.getElementById('display-refund').textContent = fmt(refund);
    const balEl = document.getElementById('display-balance');
    balEl.textContent = (balance < 0 ? '-' : '') + fmt(balance);
    balEl.style.color = Math.abs(balance) < 0.01 ? 'var(--color-success)' : 'var(--color-danger)';
}
</script>
@endpush
