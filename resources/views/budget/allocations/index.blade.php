@extends('layouts.vertical', [
    'title'         => 'Budget Matrix',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Appropriation overview by Program and Line Item',
])

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

{{-- FY Selector --}}
<div class="flex items-center gap-3 mb-6">
    <label class="font-semibold text-sm text-gray-600 dark:text-gray-400">Fiscal Year:</label>
    <form method="GET" action="{{ route('budget.allocations.index') }}">
        <select name="fy" class="form-select w-40" onchange="this.form.submit()">
            @foreach($fiscalYears as $fyOpt)
                <option value="{{ $fyOpt->id }}" {{ $fyOpt->id == $fy?->id ? 'selected' : '' }}>
                    {{ $fyOpt->displayLabel() }}{{ $fyOpt->is_active ? ' ★' : '' }}
                </option>
            @endforeach
        </select>
    </form>
</div>

@if(!$fy)
<div class="text-sm text-gray-500 p-4">
    No fiscal year found. <a href="{{ route('budget.index') }}" class="text-primary underline">Create one first →</a>
</div>
@else

@if($totalBudget <= 0)
<div class="mb-5 p-4 rounded-lg bg-warning/10 border border-warning/30 flex gap-3">
    <i class="mgc_alert_line text-warning text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-warning font-medium">
        No income estimates for {{ $fy->displayLabel() }}.
        <a href="{{ route('budget.income-estimates.index', ['fy' => $fy->id]) }}" class="underline font-semibold">Enter estimates first →</a>
    </p>
</div>
@endif

@if($lineItems->isEmpty())
<div class="mb-5 p-4 rounded-lg bg-primary/10 border border-primary/20 flex gap-3">
    <i class="mgc_information_line text-primary text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-primary font-medium">
        No line items defined yet.
        <a href="{{ route('budget.line-items.index', ['fy' => $fy->id]) }}" class="underline font-semibold">Set up line items first →</a>
    </p>
</div>
@endif

{{-- Summary strip --}}
<div class="flex gap-4 mb-6 flex-wrap">
    @foreach(['PS' => 'Personal Services', 'MOOE' => 'MOOE', 'CO' => 'Capital Outlay'] as $cls => $lbl)
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg px-4 py-3 min-w-[160px]">
        <div class="text-xs text-gray-400 mb-0.5">{{ $lbl }}</div>
        <div class="font-bold tabular-nums text-gray-800 dark:text-gray-100">₱{{ number_format($colTotals[$cls], 2) }}</div>
    </div>
    @endforeach
    <div class="bg-primary/10 border border-primary/20 rounded-lg px-4 py-3 min-w-[160px]">
        <div class="text-xs text-primary/70 mb-0.5">Grand Total</div>
        <div class="font-bold tabular-nums text-primary">₱{{ number_format($grandTotal, 2) }}</div>
    </div>
    @if($totalBudget > 0)
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg px-4 py-3 min-w-[160px]">
        <div class="text-xs text-gray-400 mb-0.5">PS Cap</div>
        <div class="font-bold tabular-nums {{ $psCap > 55 ? 'text-danger' : 'text-success' }}">
            {{ $psCap }}%
            @if($psCap > 55)<span class="text-xs font-normal"> ⚠ over 55%</span>@endif
        </div>
    </div>
    @endif
</div>

{{-- One card per program --}}
<div class="space-y-4">
@forelse($programs as $prog)
@php
    $progLineItems = $lineItems->get($prog->id) ?? collect();
    $progTotal     = $progLineItems->sum('appropriation');
    $progDisbursed = $progLineItems->sum(fn($i) => $i->disbursed());
    $progBalance   = $progTotal - $progDisbursed;
    $isMandatory   = $prog->isMandatory();
    $mandatoryAmt  = $isMandatory ? ($mandatory[$prog->special_fund] ?? 0) : null;
@endphp

<div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">

    {{-- Program header --}}
    <div class="flex items-center justify-between px-5 py-3 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ $prog->name }}</span>
            @if($isMandatory)
            <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-400 inline-block"></span>
                Mandatory
            </span>
            @endif
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
            @if($isMandatory && $mandatoryAmt)
            <span>Ceiling: <span class="tabular-nums font-medium text-orange-600">₱{{ number_format($mandatoryAmt, 2) }}</span></span>
            @endif
            <span>Appropriated: <span class="tabular-nums font-semibold text-gray-700 dark:text-gray-200">₱{{ number_format($progTotal, 2) }}</span></span>
            @if($progDisbursed > 0)
            <span>Spent: <span class="tabular-nums font-semibold text-danger">₱{{ number_format($progDisbursed, 2) }}</span></span>
            <span>Balance: <span class="tabular-nums font-semibold text-success">₱{{ number_format($progBalance, 2) }}</span></span>
            @endif
        </div>
    </div>

    {{-- Line items --}}
    @if($progLineItems->isEmpty())
    <div class="px-5 py-4 text-sm text-gray-400 italic">
        No line items — <a href="{{ route('budget.line-items.index', ['fy' => $fy->id]) }}" class="text-primary underline">add line items →</a>
    </div>
    @else
    <div class="divide-y divide-gray-100 dark:divide-gray-700/40">
        {{-- Column headers --}}
        <div class="grid grid-cols-12 gap-2 px-5 py-1.5 text-xs text-gray-400 uppercase tracking-wide">
            <div class="col-span-1"></div>
            <div class="col-span-5">Line Item</div>
            <div class="col-span-2 text-right">Appropriated</div>
            <div class="col-span-2 text-right">Spent</div>
            <div class="col-span-2 text-right">Balance</div>
        </div>

        @foreach($progLineItems as $item)
        @php
            $disbursed = $item->disbursed();
            $caTotal   = $item->cashAdvanceTotal();
            $balance   = (float)$item->appropriation - $disbursed - $caTotal;
            $badgeClass = match($item->object_class) {
                'PS'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'MOOE' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'CO'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                default => 'bg-gray-100 text-gray-600',
            };
        @endphp
        <div class="grid grid-cols-12 gap-2 px-5 py-2.5 items-center hover:bg-gray-50 dark:hover:bg-gray-700/20">
            <div class="col-span-1">
                <span class="inline-flex items-center py-0.5 px-1.5 rounded text-xs font-semibold {{ $badgeClass }}">
                    {{ $item->object_class }}
                </span>
            </div>
            <div class="col-span-5 text-sm text-gray-700 dark:text-gray-300">{{ $item->name }}</div>
            <div class="col-span-2 text-right tabular-nums text-sm text-gray-700 dark:text-gray-200">
                ₱{{ number_format($item->appropriation, 2) }}
            </div>
            <div class="col-span-2 text-right tabular-nums text-sm {{ ($disbursed + $caTotal) > 0 ? 'text-danger' : 'text-gray-400' }}">
                {{ ($disbursed + $caTotal) > 0 ? '₱'.number_format($disbursed + $caTotal, 2) : '—' }}
            </div>
            <div class="col-span-2 text-right tabular-nums text-sm font-medium {{ $balance < 0 ? 'text-danger' : ($balance == 0 && (float)$item->appropriation > 0 ? 'text-gray-400' : 'text-success') }}">
                {{ (float)$item->appropriation > 0 ? '₱'.number_format($balance, 2) : '—' }}
            </div>
        </div>
        @endforeach

        {{-- Program subtotal --}}
        <div class="grid grid-cols-12 gap-2 px-5 py-2 bg-gray-50/80 dark:bg-gray-700/20 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <div class="col-span-6 uppercase tracking-wide">Program Total</div>
            <div class="col-span-2 text-right tabular-nums text-gray-700 dark:text-gray-200">₱{{ number_format($progTotal, 2) }}</div>
            <div class="col-span-2 text-right tabular-nums text-danger">
                {{ $progDisbursed > 0 ? '₱'.number_format($progDisbursed, 2) : '—' }}
            </div>
            <div class="col-span-2 text-right tabular-nums {{ $progBalance < $progTotal ? 'text-success' : 'text-gray-400' }}">
                {{ $progTotal > 0 ? '₱'.number_format($progBalance, 2) : '—' }}
            </div>
        </div>
    </div>
    @endif

</div>
@empty
<div class="text-sm text-gray-400 p-4">
    No active programs found.
</div>
@endforelse
</div>

{{-- Grand total footer --}}
@if(!$lineItems->isEmpty())
<div class="mt-4 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl px-5 py-3 flex items-center justify-between">
    <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">Grand Total</span>
    <div class="flex items-center gap-8 text-sm">
        <div class="text-right">
            <div class="text-xs text-gray-400 mb-0.5">Appropriated</div>
            <div class="tabular-nums font-bold text-primary">₱{{ number_format($grandTotal, 2) }}</div>
        </div>
        @if($totalBudget > 0)
        <div class="text-right">
            <div class="text-xs text-gray-400 mb-0.5">Income Ceiling</div>
            <div class="tabular-nums font-bold text-gray-600 dark:text-gray-300">₱{{ number_format($totalBudget, 2) }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400 mb-0.5">Difference</div>
            @php $diff = $totalBudget - $grandTotal; @endphp
            <div class="tabular-nums font-bold {{ $diff < 0 ? 'text-danger' : 'text-success' }}">
                {{ $diff >= 0 ? '+' : '' }}₱{{ number_format($diff, 2) }}
            </div>
        </div>
        @endif
    </div>
</div>
@endif

@endif
@endsection
