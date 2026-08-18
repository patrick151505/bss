@extends('layouts.vertical', [
    'title'         => 'Cash Advances',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Cash Advances & Liquidations',
])

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    @if($fiscalYear)
        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-sky-800">
            <span class="w-1.5 h-1.5 inline-block bg-sky-400 rounded-full"></span>{{ $fiscalYear->displayLabel() }}
        </span>
    @endif
    <a href="{{ route('budget.cash-advances.create') }}" class="btn bg-primary text-white ms-auto">
        <i class="mgc_add_line"></i> Grant Cash Advance
    </a>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">CA No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Officer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Purpose</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Deadline</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($cashAdvances as $ca)
                @php
                    $overdue = $ca->isOpen() && $ca->deadline_date->isPast();
                    [$badgeBg, $badgeText, $badgeDot] = match($ca->status) {
                        'open'       => $overdue
                                            ? ['bg-red-100', 'text-red-800', 'bg-red-400']
                                            : ['bg-yellow-100', 'text-yellow-800', 'bg-yellow-400'],
                        'liquidated' => ['bg-green-100', 'text-green-800', 'bg-green-400'],
                        'cancelled'  => ['bg-gray-100', 'text-gray-500', 'bg-gray-400'],
                        default      => ['bg-gray-100', 'text-gray-500', 'bg-gray-400'],
                    };
                @endphp
                <tr class="{{ $overdue ? 'bg-danger/5' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-3 font-mono text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $ca->ca_no }}</td>
                    <td class="px-6 py-3 text-gray-800 dark:text-gray-200">{{ $ca->officer->name }}</td>
                    <td class="px-6 py-3 max-w-xs">
                        <p class="truncate text-sm text-gray-800 dark:text-gray-200">{{ $ca->purpose }}</p>
                        @if($ca->allocation)
                            <p class="text-xs text-gray-400">{{ $ca->allocation->program?->name ?? '—' }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right font-mono font-semibold text-gray-800 dark:text-gray-200">₱{{ number_format($ca->amount, 2) }}</td>
                    <td class="px-6 py-3">
                        <span class="{{ $overdue ? 'text-danger font-semibold' : 'text-sm text-gray-800 dark:text-gray-200' }}">
                            {{ $ca->deadline_date->format('M d, Y') }}
                        </span>
                        @if($overdue)
                            <br><span class="text-xs text-danger">{{ $ca->daysOverdue() }}d overdue</span>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $badgeBg }} {{ $badgeText }}">
                            <span class="w-1.5 h-1.5 inline-block {{ $badgeDot }} rounded-full"></span>{{ ucfirst($ca->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('budget.cash-advances.show', $ca) }}" class="btn btn-sm bg-dark/25 text-slate-900 hover:bg-dark hover:text-white dark:text-slate-200">View</a>
                        @if($ca->isOpen())
                            <a href="{{ route('budget.liquidations.create', $ca) }}" class="btn btn-sm bg-success/25 text-success hover:bg-success hover:text-white">Liquidate</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-400">
                        <i class="mgc_wallet_line text-4xl mb-2 block opacity-30"></i>
                        No cash advances for this fiscal year.
                        <a href="{{ route('budget.cash-advances.create') }}" class="text-primary">Grant one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cashAdvances->hasPages())
        <div class="px-6 py-3 border-t dark:border-gray-700">{{ $cashAdvances->links() }}</div>
    @endif
</div>
@endsection
