@extends('layouts.vertical', [
    'title'         => 'Liquidation Report',
    'sub_title'     => 'Cash Advances',
    'sub_title_url' => route('budget.cash-advances.index'),
    'tagline'       => 'Liquidation — ' . $liquidation->cashAdvance->ca_no,
])

@section('content')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title">Liquidation Report</h5>
                <div class="flex items-center gap-2">
                    <span class="badge {{ $liquidation->status === 'closed' ? 'bg-success/15 text-success' : 'bg-warning/15 text-warning' }}">
                        {{ ucfirst($liquidation->status) }}
                    </span>
                    @if($liquidation->status !== 'closed')
                        <a href="{{ route('budget.liquidations.edit', $liquidation) }}" class="btn btn-xs btn-light">Edit</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-gray-500">CA No.</p>
                        <p class="font-semibold">{{ $liquidation->cashAdvance->ca_no }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Liquidation Date</p>
                        <p class="font-semibold">{{ $liquidation->liquidation_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Amount Granted</p>
                        <p class="font-bold text-primary">₱{{ number_format($liquidation->cashAdvance->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Created</p>
                        <p>{{ $liquidation->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>

                {{-- Expense Lines --}}
                <table class="table table-sm mb-4">
                    <thead>
                        <tr>
                            <th>OR No.</th>
                            <th>Date</th>
                            <th>Particulars</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($liquidation->lines as $line)
                        <tr>
                            <td class="font-mono text-sm">{{ $line->or_no ?: '—' }}</td>
                            <td>{{ $line->receipt_date ? $line->receipt_date->format('M d, Y') : '—' }}</td>
                            <td>{{ $line->particulars }}</td>
                            <td class="text-end font-mono">₱{{ number_format($line->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-semibold bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <td colspan="3">Total Expenses</td>
                            <td class="text-end font-mono text-danger">₱{{ number_format($liquidation->total_expenses, 2) }}</td>
                        </tr>
                        @if($liquidation->refund_amount > 0)
                        <tr>
                            <td colspan="2">Refund (OR: {{ $liquidation->refund_or_no ?: '—' }})</td>
                            <td></td>
                            <td class="text-end font-mono text-warning">₱{{ number_format($liquidation->refund_amount, 2) }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>

                @if($liquidation->notes)
                <p class="text-sm text-gray-500 mt-2"><span class="font-medium">Notes:</span> {{ $liquidation->notes }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Reconciliation --}}
    <div>
        <div class="card p-5">
            <h6 class="font-semibold mb-4">Reconciliation</h6>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Amount Granted</span>
                    <span class="font-mono font-semibold">₱{{ number_format($liquidation->cashAdvance->amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Expenses</span>
                    <span class="font-mono text-danger">₱{{ number_format($liquidation->total_expenses, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Refund</span>
                    <span class="font-mono text-warning">₱{{ number_format($liquidation->refund_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold border-t pt-2">
                    <span>Balance</span>
                    @php $bal = $liquidation->reconciliationBalance(); @endphp
                    <span class="font-mono {{ abs($bal) < 0.01 ? 'text-success' : 'text-danger' }}">
                        ₱{{ number_format($bal, 2) }}
                    </span>
                </div>
            </dl>

            @if($liquidation->status !== 'closed')
            <div class="mt-4 border-t pt-4">
                @if($liquidation->canClose())
                    <form method="POST" action="{{ route('budget.liquidations.close', $liquidation) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success w-full"
                            onclick="return confirm('Close this liquidation? This will mark the cash advance as liquidated and cannot be undone.')">
                            <i class="mgc_check_circle_line me-1"></i> Close Liquidation
                        </button>
                    </form>
                    <p class="text-xs text-gray-400 mt-2 text-center">Balance is ₱0.00 — ready to close.</p>
                @else
                    <div class="alert alert-warning text-sm">
                        Cannot close: balance must be ₱0.00.
                        Current balance: <strong>₱{{ number_format(abs($bal), 2) }}</strong>
                        {{ $bal > 0 ? 'unaccounted' : 'over-claimed' }}.
                    </div>
                @endif
            </div>
            @else
                <div class="mt-4 alert alert-success text-sm">
                    <i class="mgc_check_circle_line me-1"></i> Liquidation closed.
                </div>
            @endif
        </div>

        <div class="mt-3">
            <a href="{{ route('budget.cash-advances.show', $liquidation->cashAdvance) }}"
                class="btn btn-light w-full">← Back to Cash Advance</a>
        </div>
    </div>
</div>
@endsection
