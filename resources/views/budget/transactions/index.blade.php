@extends('layouts.vertical', [
    'title'         => 'Vouchers',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Disbursement vouchers — ' . ($fy ? $fy->displayLabel() : 'No fiscal year'),
])

@section('content')

@if(session('success'))
<div class="mb-4 p-3 rounded-lg bg-success/10 border border-success/30 text-success text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 rounded-lg bg-danger/10 border border-danger/30 text-danger text-sm">{{ session('error') }}</div>
@endif

{{-- Toolbar --}}
<div class="flex items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        {{-- FY selector --}}
        <form method="GET" action="{{ route('budget.transactions.index') }}">
            <select name="fy" onchange="this.form.submit()" class="form-select text-sm py-1.5">
                @foreach($fiscalYears as $y)
                <option value="{{ $y->id }}" {{ $fy && $fy->id === $y->id ? 'selected' : '' }}>{{ $y->displayLabel() }}</option>
                @endforeach
            </select>
        </form>

        {{-- Type filter tabs --}}
        <div class="flex items-center rounded-lg border dark:border-gray-600 overflow-hidden text-xs font-medium">
            @foreach(['All' => '', 'DV' => 'DV', 'PCV' => 'PCV', 'Payroll' => 'Payroll'] as $lbl => $val)
            <a href="{{ route('budget.transactions.index', ['fy' => $fy?->id, 'type' => $val]) }}"
                class="px-3 py-1.5 {{ request('type') === $val ? 'bg-primary text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ $lbl }}
            </a>
            @endforeach
        </div>
    </div>

    @if($fy)
    <a href="{{ route('budget.transactions.create', ['fy' => $fy->id]) }}"
        class="px-4 py-2 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary/90 flex items-center gap-1.5">
        <i class="mgc_add_line"></i> New Voucher
    </a>
    @endif
</div>

{{-- Table --}}
<div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">

    @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator ? $transactions->isEmpty() : $transactions->isEmpty())
    <div class="py-16 text-center text-sm text-gray-400">
        No vouchers yet.
        @if($fy)
        <a href="{{ route('budget.transactions.create', ['fy' => $fy->id]) }}" class="text-primary underline ml-1">Create the first one →</a>
        @endif
    </div>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b dark:border-gray-700 text-xs text-gray-400 bg-gray-50 dark:bg-gray-700/30">
                <th class="px-4 py-3 text-left">Voucher No.</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Payee</th>
                <th class="px-4 py-3 text-left">Type</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
            @foreach($transactions as $tx)
            @php $s = App\Models\BudgetTransaction::STATUSES[$tx->status] ?? ['label'=>$tx->status,'color'=>'']; @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                <td class="px-4 py-3 font-mono text-xs text-primary">
                    <a href="{{ route('budget.transactions.show', $tx) }}">{{ $tx->voucher_no }}</a>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $tx->transaction_date?->format('M d, Y') }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-200 max-w-48 truncate">{{ $tx->payee }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $tx->voucher_type }}</span>
                </td>
                <td class="px-4 py-3 text-right tabular-nums font-medium text-gray-700 dark:text-gray-200">
                    ₱{{ number_format($tx->amount, 2) }}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $s['color'] }}">
                        {{ $s['label'] }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('budget.transactions.show', $tx) }}" class="text-xs text-gray-400 hover:text-primary">View →</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(method_exists($transactions, 'links'))
    <div class="px-4 py-3 border-t dark:border-gray-700">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
    @endif

</div>

@endsection
