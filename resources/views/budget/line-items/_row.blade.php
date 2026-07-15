@php
    $lines      = $item->transactionLines ?? collect();
    $hasLines   = $lines->isNotEmpty();
    $disbursed  = $lines->filter(fn($l) => in_array($l->transaction?->status, ['approved','paid']))->sum('amount');
    $approp     = (float)$item->appropriation;
    $balance    = $approp - (float)$disbursed;
    $pct        = $approp > 0 ? min(100, round(($disbursed / $approp) * 100, 1)) : 0;
    $barColor   = $pct >= 100 ? 'bg-danger' : ($pct >= 81 ? 'bg-orange-400' : ($pct >= 51 ? 'bg-warning' : 'bg-success'));
    $pctColor   = $pct >= 100 ? 'text-danger' : ($pct >= 81 ? 'text-orange-400' : ($pct >= 51 ? 'text-warning' : 'text-success'));
@endphp

{{--
    Column layout (all rows share the same fixed widths):
    [indent+code+name flex-1] [progress w-32] [approp w-28] [toggle w-8] [actions w-12]
--}}

{{-- Line item row --}}
<div id="item-row-{{ $item->id }}"
    class="flex items-center gap-0 px-4 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/20 group border-b border-gray-100 dark:border-gray-700/30"
    data-prog="{{ $item->program_id }}"
    data-cls="{{ $item->object_class }}"
    data-amount="{{ $item->appropriation }}">

    {{-- Name section: flex-1 --}}
    <div class="flex items-center gap-2 flex-1 min-w-0 pr-3">
        <span class="text-gray-300 dark:text-gray-600 shrink-0 select-none">└─</span>
        <span class="text-xs font-mono text-gray-400 shrink-0">{{ $item->object_code }}</span>
        <span class="truncate text-gray-700 dark:text-gray-300">{{ $item->name }}</span>
    </div>

    {{-- Progress bar: w-32 --}}
    <div class="w-32 shrink-0 flex flex-col gap-0.5 pr-3 relative group/bar">
        <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden cursor-help">
            <div class="{{ $barColor }} h-full rounded-full transition-all" style="width:{{ $pct }}%"></div>
        </div>
        <div class="flex justify-between text-[10px] tabular-nums">
            <span class="font-medium {{ $pctColor }}">{{ $pct }}%</span>
            <span class="{{ $balance < 0 ? 'text-danger font-semibold' : 'text-gray-400' }}">bal ₱{{ number_format($balance, 2) }}</span>
        </div>
        {{-- Tooltip --}}
        <div class="absolute bottom-full left-0 mb-2 hidden group-hover/bar:block z-50 pointer-events-none">
            <div class="bg-gray-900 text-white text-[10px] rounded-lg px-3 py-2 shadow-xl w-52 space-y-1.5">
                <div class="flex justify-between gap-2">
                    <span class="text-gray-400">Appropriated</span>
                    <span class="tabular-nums font-medium">₱{{ number_format($approp, 2) }}</span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-400">Disbursed</span>
                    <span class="tabular-nums font-medium text-danger">−₱{{ number_format($disbursed, 2) }}</span>
                </div>
                <div class="border-t border-gray-700 pt-1.5 flex justify-between gap-2">
                    <span class="text-gray-400">Balance</span>
                    <span class="tabular-nums font-semibold {{ $balance < 0 ? 'text-danger' : 'text-success' }}">₱{{ number_format($balance, 2) }}</span>
                </div>
                <div class="border-t border-gray-700 pt-1.5 text-gray-400 leading-relaxed">
                    <span class="{{ $pctColor }} font-semibold">{{ $pct }}%</span> of budget used.
                    @if($pct >= 100) Budget exhausted or overdrawn.
                    @elseif($pct >= 81) Nearly exhausted — less than 20% remaining.
                    @elseif($pct >= 51) Over half of budget consumed.
                    @else Healthy — more than half remaining.
                    @endif
                </div>
                {{-- Arrow --}}
                <div class="absolute top-full left-4 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
    </div>

    {{-- Appropriation: w-28, right-aligned --}}
    <div class="w-28 shrink-0 text-right tabular-nums font-medium text-xs text-gray-700 dark:text-gray-200 pr-2">
        ₱{{ number_format($approp, 2) }}
    </div>

    {{-- Toggle button: w-8 --}}
    <div class="w-8 shrink-0 flex justify-center">
        @if($hasLines)
        <button type="button"
            onclick="toggleVouchers({{ $item->id }}, this)"
            class="text-gray-400 hover:text-primary flex items-center gap-0.5"
            title="Show/hide vouchers">
            <i class="mgc_bill_2_line text-sm"></i>
            <span class="text-[10px]">{{ $lines->count() }}</span>
        </button>
        @endif
    </div>

    {{-- Actions: w-12 --}}
    <div class="w-12 shrink-0 flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button type="button"
            onclick='openEdit({{ $item->id }}, @json($item->name), {{ $item->appropriation }}, @json($item->object_code ?? ""))'
            class="p-1 text-gray-400 hover:text-primary rounded">
            <i class="mgc_edit_line"></i>
        </button>
        <button type="button"
            onclick='confirmDelete({{ $item->id }}, @json($item->name))'
            class="p-1 text-gray-400 hover:text-danger rounded">
            <i class="mgc_delete_line"></i>
        </button>
    </div>
</div>

{{-- Voucher sub-rows (visible by default) --}}
@if($hasLines)
<div id="vouchers-{{ $item->id }}" style="display:block" class="border-b border-gray-100 dark:border-gray-700/30">
    @foreach($lines as $line)
    @php
        $tx = $line->transaction;
        $s  = \App\Models\BudgetTransaction::STATUSES[$tx->status] ?? ['label' => $tx->status, 'color' => ''];
    @endphp
    <a href="{{ route('budget.transactions.show', $tx) }}"
        class="flex items-center gap-0 pl-8 pr-4 py-1.5 text-xs hover:bg-primary/5 dark:hover:bg-primary/10 transition-colors border-t border-gray-100 dark:border-gray-700/20">

        {{-- Name section: flex-1 (matches parent) --}}
        <div class="flex items-center gap-2 flex-1 min-w-0 pr-3">
            <span class="text-gray-200 dark:text-gray-700 shrink-0 select-none">└─</span>
            <span class="shrink-0 font-bold text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 w-10 text-center">
                {{ $tx->voucher_type }}
            </span>
            <span class="font-mono text-primary shrink-0">{{ $tx->voucher_no }}</span>
            <span class="truncate text-gray-400">{{ $tx->payee }}</span>
        </div>

        {{-- Progress column: w-32 — show date + status here --}}
        <div class="w-32 shrink-0 flex items-center justify-between pr-3">
            <span class="text-gray-400 text-[10px]">{{ $tx->transaction_date?->format('M d, Y') }}</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $s['color'] }}">{{ $s['label'] }}</span>
        </div>

        {{-- Amount: w-28, right-aligned — aligns with appropriation column --}}
        <div class="w-28 shrink-0 text-right tabular-nums font-semibold text-danger pr-2">
            −₱{{ number_format($line->amount, 2) }}
        </div>

        {{-- Toggle placeholder: w-8 --}}
        <div class="w-8 shrink-0"></div>

        {{-- Actions placeholder: w-12 --}}
        <div class="w-12 shrink-0"></div>
    </a>
    @endforeach
</div>
@endif
