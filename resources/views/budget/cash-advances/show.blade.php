@extends('layouts.vertical', [
    'title'         => $cashAdvance->ca_no,
    'sub_title'     => 'Cash Advances',
    'sub_title_url' => route('budget.cash-advances.index'),
    'tagline'       => $cashAdvance->ca_no,
])

@section('content')

@php
    $overdue = $cashAdvance->isOverdue();
    $badge = match($cashAdvance->status) {
        'open'       => $overdue ? 'bg-danger/15 text-danger' : 'bg-warning/15 text-warning',
        'liquidated' => 'bg-success/15 text-success',
        'cancelled'  => 'bg-gray-100 text-gray-500',
    };
@endphp

@if($overdue)
    <div class="alert alert-danger mb-4">
        <i class="mgc_warning_line me-2"></i>
        This cash advance is <strong>{{ $cashAdvance->daysOverdue() }} days overdue</strong>.
        Liquidation was due {{ $cashAdvance->deadline_date->format('M d, Y') }}.
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Details --}}
    <div class="xl:col-span-2 card">
        <div class="card-header flex items-center justify-between">
            <h5 class="card-title">{{ $cashAdvance->ca_no }}</h5>
            <div class="flex items-center gap-2">
                <span class="badge {{ $badge }}">{{ ucfirst($cashAdvance->status) }}</span>
                @if($cashAdvance->isOpen())
                    <a href="{{ route('budget.cash-advances.edit', $cashAdvance) }}" class="btn btn-xs btn-light">Edit</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-500 mb-0.5">Officer</dt>
                    <dd class="font-medium">{{ $cashAdvance->officer->name }}</dd>
                    @if($cashAdvance->officer->position)
                        <dd class="text-xs text-gray-400">{{ $cashAdvance->officer->position }}</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-gray-500 mb-0.5">Fiscal Year</dt>
                    <dd class="font-medium">{{ $cashAdvance->fiscalYear->displayLabel() }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 mb-0.5">Purpose</dt>
                    <dd class="font-medium">{{ $cashAdvance->purpose }}</dd>
                </div>
                @if($cashAdvance->allocation)
                <div>
                    <dt class="text-gray-500 mb-0.5">Allocation Line</dt>
                    <dd class="font-medium">[{{ $cashAdvance->allocation->object_class }}] {{ $cashAdvance->allocation->program?->name ?? '—' }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500 mb-0.5">Amount Granted</dt>
                    <dd class="font-bold text-lg text-primary">₱{{ number_format($cashAdvance->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 mb-0.5">Date Granted</dt>
                    <dd>{{ $cashAdvance->date_granted->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 mb-0.5">Liquidation Deadline</dt>
                    <dd class="{{ $overdue ? 'text-danger font-semibold' : '' }}">
                        {{ $cashAdvance->deadline_date->format('M d, Y') }}
                        @if($overdue) <span class="text-xs">({{ $cashAdvance->daysOverdue() }}d past due)</span> @endif
                    </dd>
                </div>
                @if($cashAdvance->reference_no)
                <div>
                    <dt class="text-gray-500 mb-0.5">DV Reference No.</dt>
                    <dd>{{ $cashAdvance->reference_no }}</dd>
                </div>
                @endif
                @if($cashAdvance->approved_by)
                <div>
                    <dt class="text-gray-500 mb-0.5">Approved By</dt>
                    <dd>{{ $cashAdvance->approved_by }}</dd>
                </div>
                @endif
                @if($cashAdvance->notes)
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 mb-0.5">Notes</dt>
                    <dd class="text-gray-700 dark:text-gray-300">{{ $cashAdvance->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Actions / Status --}}
    <div class="space-y-4">
        @if($cashAdvance->isOpen())
        <div class="card p-5">
            <h6 class="font-semibold mb-3">Actions</h6>
            <a href="{{ route('budget.liquidations.create', $cashAdvance) }}"
                class="btn btn-success w-full mb-2">
                <i class="mgc_file_check_line me-1"></i> Submit Liquidation
            </a>
            <a href="{{ route('budget.cash-advances.edit', $cashAdvance) }}"
                class="btn btn-light w-full">Edit Details</a>
        </div>
        @endif

        @if($cashAdvance->liquidationReport)
        @php $liq = $cashAdvance->liquidationReport; @endphp
        <div class="card p-5">
            <h6 class="font-semibold mb-3">Liquidation Report</h6>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="badge {{ $liq->status === 'closed' ? 'bg-success/15 text-success' : 'bg-warning/15 text-warning' }}">
                        {{ ucfirst($liq->status) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Expenses</span>
                    <span class="font-mono">₱{{ number_format($liq->total_expenses, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Refund</span>
                    <span class="font-mono">₱{{ number_format($liq->refund_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold border-t pt-1">
                    <span>Balance</span>
                    <span class="font-mono {{ abs($liq->reconciliationBalance()) < 0.01 ? 'text-success' : 'text-danger' }}">
                        ₱{{ number_format($liq->reconciliationBalance(), 2) }}
                    </span>
                </div>
            </dl>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('budget.liquidations.show', $liq) }}" class="btn btn-sm btn-light flex-1">View</a>
                @if($liq->status !== 'closed')
                    <a href="{{ route('budget.liquidations.edit', $liq) }}" class="btn btn-sm btn-light flex-1">Edit</a>
                @endif
            </div>
            @if($liq->status !== 'closed' && $liq->canClose())
            <form method="POST" action="{{ route('budget.liquidations.close', $liq) }}" class="mt-2">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-success w-full"
                    onclick="return confirm('Close this liquidation? This cannot be undone.')">
                    Close Liquidation
                </button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
