@extends('layouts.vertical', [
    'title'         => 'Cash Advances',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Cash Advances & Liquidations',
])

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <form method="GET" class="flex gap-2">
        @if($fiscalYear)
            <span class="badge bg-primary/15 text-primary px-3 py-1.5 text-sm font-semibold">
                {{ $fiscalYear->displayLabel() }}
            </span>
        @endif
    </form>
    <a href="{{ route('budget.cash-advances.create') }}" class="btn btn-sm btn-primary">
        <i class="mgc_add_line me-1"></i> Grant Cash Advance
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>CA No.</th>
                        <th>Officer</th>
                        <th>Purpose</th>
                        <th class="text-end">Amount</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAdvances as $ca)
                    @php
                        $overdue = $ca->isOpen() && $ca->deadline_date->isPast();
                        $badge = match($ca->status) {
                            'open'       => $overdue ? 'bg-danger/15 text-danger' : 'bg-warning/15 text-warning',
                            'liquidated' => 'bg-success/15 text-success',
                            'cancelled'  => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <tr class="{{ $overdue ? 'bg-danger/5' : '' }}">
                        <td class="font-mono text-sm font-semibold">{{ $ca->ca_no }}</td>
                        <td>{{ $ca->officer->name }}</td>
                        <td class="max-w-xs">
                            <p class="truncate text-sm">{{ $ca->purpose }}</p>
                            @if($ca->allocation)
                                <p class="text-xs text-gray-400">{{ $ca->allocation->line_name }}</p>
                            @endif
                        </td>
                        <td class="text-end font-mono font-semibold">₱{{ number_format($ca->amount, 2) }}</td>
                        <td>
                            <span class="{{ $overdue ? 'text-danger font-semibold' : 'text-sm' }}">
                                {{ $ca->deadline_date->format('M d, Y') }}
                            </span>
                            @if($overdue)
                                <br><span class="text-xs text-danger">{{ $ca->daysOverdue() }}d overdue</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $badge }} text-xs">{{ ucfirst($ca->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('budget.cash-advances.show', $ca) }}" class="btn btn-xs btn-light">View</a>
                            @if($ca->isOpen())
                                <a href="{{ route('budget.liquidations.create', $ca) }}" class="btn btn-xs btn-success-light">Liquidate</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-10">
                            No cash advances for this fiscal year.
                            <a href="{{ route('budget.cash-advances.create') }}" class="text-primary">Grant one →</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cashAdvances->hasPages())
            <div class="px-4 py-3 border-t">{{ $cashAdvances->links() }}</div>
        @endif
    </div>
</div>
@endsection
