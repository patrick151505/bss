@extends('layouts.vertical', [
    'title'         => $transaction->voucher_no,
    'sub_title'     => 'Vouchers',
    'sub_title_url' => route('budget.transactions.index', ['fy' => $transaction->fiscal_year_id]),
    'tagline'       => App\Models\BudgetTransaction::VOUCHER_TYPES[$transaction->voucher_type] ?? $transaction->voucher_type,
])

@section('content')

@if(session('success'))
<div class="mb-4 p-3 rounded-lg bg-success/10 border border-success/30 text-success text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 rounded-lg bg-danger/10 border border-danger/30 text-danger text-sm">{{ session('error') }}</div>
@endif

@php
    $s      = App\Models\BudgetTransaction::STATUSES[$transaction->status] ?? ['label' => $transaction->status, 'color' => ''];
    $tax    = (float) $transaction->tax_withheld;
    $gross  = (float) $transaction->amount;
    $net    = $transaction->netAmount();

    $taxTypeLabels = [
        'nv_3'           => 'Sales to Govt – Non-VAT (NV) 3%',
        'vat_0'          => 'Sales to Govt – VAT Zero-Rated 0%',
        'ewt_goods_1'    => 'Expanded WT – Goods 1%',
        'ewt_services_2' => 'Expanded WT – Services 2%',
        'ewt_both_1'     => 'Expanded WT – Goods & Services 1%',
    ];
    $taxLabel = $taxTypeLabels[$transaction->tax_type ?? ''] ?? null;
@endphp

<div class="grid grid-cols-10 gap-5 items-start">

{{-- LEFT 70% --}}
<div class="col-span-7 space-y-4">

    {{-- ── Voucher Header ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-mono font-bold text-primary">{{ $transaction->voucher_no }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $s['color'] }}">{{ $s['label'] }}</span>
            </div>
            <span class="text-xs text-gray-400">{{ $transaction->fiscalYear?->displayLabel() }}</span>
        </div>
        <div class="p-5 grid grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Type</div>
                <div class="font-medium text-gray-800 dark:text-gray-100">
                    {{ App\Models\BudgetTransaction::VOUCHER_TYPES[$transaction->voucher_type] ?? $transaction->voucher_type }}
                </div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Date</div>
                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $transaction->transaction_date?->format('F d, Y') }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Mode of Payment</div>
                <div class="font-medium text-gray-800 dark:text-gray-100">
                    {{ App\Models\BudgetTransaction::PAYMENT_MODES[$transaction->mode_of_payment] ?? $transaction->mode_of_payment }}
                </div>
            </div>
            @if($transaction->check_no || $transaction->bank_name)
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Check No.</div>
                <div class="font-mono text-gray-800 dark:text-gray-100">{{ $transaction->check_no ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Check Date</div>
                <div class="text-gray-700 dark:text-gray-200">{{ $transaction->check_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Bank Name</div>
                <div class="text-gray-700 dark:text-gray-200">{{ $transaction->bank_name ?? '—' }}</div>
            </div>
            @endif
            @if($transaction->or_number || $transaction->or_date)
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">OR Number</div>
                <div class="font-mono text-gray-800 dark:text-gray-100">{{ $transaction->or_number ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">OR Date</div>
                <div class="text-gray-700 dark:text-gray-200">{{ $transaction->or_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Payee / Supplier ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-2.5 border-b dark:border-gray-700">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Payee / Supplier</span>
        </div>
        <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div class="col-span-2">
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Name</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100 text-base">{{ $transaction->payee }}</div>
            </div>
            @if($transaction->payee_tin)
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">TIN</div>
                <div class="font-mono text-gray-700 dark:text-gray-200">{{ $transaction->payee_tin }}</div>
            </div>
            @endif
            @if($transaction->payee_address)
            <div class="{{ $transaction->payee_tin ? '' : 'col-span-2' }}">
                <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Address</div>
                <div class="text-gray-600 dark:text-gray-300">{{ $transaction->payee_address }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Purpose ── --}}
    @if($transaction->description)
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-2.5 border-b dark:border-gray-700">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Particulars / Purpose</span>
        </div>
        <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200 leading-relaxed">
            {{ $transaction->description }}
        </div>
    </div>
    @endif

    {{-- ── Charge Lines ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-2.5 border-b dark:border-gray-700">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Charge to Budget Line Items</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b dark:border-gray-700 text-[10px] uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/30">
                    <th class="px-4 py-2 text-left font-medium">Program</th>
                    <th class="px-4 py-2 text-left font-medium">Class</th>
                    <th class="px-4 py-2 text-left font-medium">Line Item</th>
                    <th class="px-4 py-2 text-right font-medium">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach($transaction->lines as $line)
                @php $cls = $line->lineItem?->object_class; @endphp
                <tr>
                    <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400 text-xs">{{ $line->lineItem?->program?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5">
                        @if($cls)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold
                            {{ $cls==='PS'   ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
                             : ($cls==='MOOE' ? 'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400'
                             : 'bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400') }}">
                            {{ $cls }}
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-200">
                        @if($line->lineItem?->object_code)
                        <span class="font-mono text-xs text-gray-400 mr-1">{{ $line->lineItem->object_code }}</span>
                        @endif
                        {{ $line->lineItem?->name ?? '—' }}
                        @if($line->description)
                        <div class="text-xs text-gray-400 mt-0.5">{{ $line->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-700 dark:text-gray-200">
                        ₱{{ number_format($line->amount, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/20">
                    <td colspan="3" class="px-4 py-2 text-xs text-right font-semibold text-gray-500 dark:text-gray-400">Total Charged</td>
                    <td class="px-4 py-2 text-right tabular-nums font-bold text-gray-800 dark:text-gray-100">
                        ₱{{ number_format($transaction->lines->sum('amount'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── Amount Summary ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-2.5 border-b dark:border-gray-700">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Amount</span>
        </div>
        <div class="p-5 space-y-2 text-sm">
            <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">Gross Amount</span>
                <span class="tabular-nums font-semibold text-gray-800 dark:text-gray-100">₱{{ number_format($gross, 2) }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Tax Withheld</span>
                    @if($taxLabel)
                    <div class="text-[10px] text-warning mt-0.5 flex items-center gap-1">
                        <i class="mgc_information_line"></i> {{ $taxLabel }}
                    </div>
                    @endif
                </div>
                <span class="tabular-nums text-gray-700 dark:text-gray-200">₱{{ number_format($tax, 2) }}</span>
            </div>
            <div class="flex items-center justify-between py-2">
                <span class="font-semibold text-gray-700 dark:text-gray-200">Net Amount</span>
                <span class="tabular-nums font-bold text-primary text-lg">₱{{ number_format($net, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Attachments ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-2.5 border-b dark:border-gray-700 flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Attachments</span>
            @if($transaction->status === 'draft')
            <button onclick="document.getElementById('attach-form').classList.toggle('hidden')"
                class="text-xs text-primary hover:text-primary/70 font-medium flex items-center gap-1">
                <i class="mgc_attachment_line"></i> Add
            </button>
            @endif
        </div>

        <div id="attach-form" class="hidden p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
            <form method="POST" action="{{ route('budget.transactions.attachments.store', $transaction) }}" enctype="multipart/form-data" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="text-xs text-gray-400 mb-1 block">Document</label>
                    <input type="file" name="file" class="form-input w-full text-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-xs rounded-lg hover:bg-primary/90 shrink-0">Upload</button>
            </form>
        </div>

        @if($transaction->attachments->isEmpty())
        <div class="py-8 text-center text-xs text-gray-400">No attachments yet.</div>
        @else
        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
            @foreach($transaction->attachments as $att)
            <div class="flex items-center gap-3 px-5 py-3 text-sm">
                <i class="mgc_file_line text-gray-400 text-lg"></i>
                <span class="flex-1 text-gray-700 dark:text-gray-200 text-xs">{{ $att->original_name }}</span>
                <span class="text-xs text-gray-400">{{ $att->created_at->format('M d, Y') }}</span>
                <a href="{{ route('budget.attachments.download', $att) }}"
                    class="text-xs text-primary hover:underline">Download</a>
                @if($transaction->status === 'draft')
                <form class="att-del-form" method="POST" action="{{ route('budget.attachments.destroy', $att) }}">
                    @csrf @method('DELETE')
                    <button type="button" class="text-xs text-gray-400 hover:text-danger"
                        onclick="swalDeleteAttachment(this)">
                        <i class="mgc_delete_line"></i>
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Meta ── --}}
    <div class="text-xs text-gray-400 px-1">
        Recorded by <span class="text-gray-500 dark:text-gray-400">{{ $transaction->recorder?->name ?? '—' }}</span>
        · {{ $transaction->created_at->format('M d, Y H:i') }}
    </div>

</div>

{{-- RIGHT 30% — Actions sidebar --}}
<div class="col-span-3 space-y-3">

    {{-- Status actions --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-4 space-y-2">
        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-3">Actions</p>

        <form id="form-approve" method="POST" action="{{ route('budget.transactions.status', $transaction) }}">
            @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
        </form>
        <form id="form-cancel" method="POST" action="{{ route('budget.transactions.status', $transaction) }}">
            @csrf @method('PATCH') <input type="hidden" name="status" value="cancelled">
        </form>
        <form id="form-paid" method="POST" action="{{ route('budget.transactions.status', $transaction) }}">
            @csrf @method('PATCH') <input type="hidden" name="status" value="paid">
        </form>
        <form id="form-delete" method="POST" action="{{ route('budget.transactions.destroy', $transaction) }}">
            @csrf @method('DELETE')
        </form>

        @if($transaction->status === 'draft')
        <button type="button" onclick="swalSubmit('form-approve','Mark as Approved?','This voucher will be marked approved.','question','Mark Approved','#727cf5')"
            class="w-full py-2 rounded-lg !bg-primary text-white text-xs font-semibold hover:!bg-primary/90 transition-colors">
            <i class="mgc_check_line mr-1"></i> Mark Approved
        </button>
        <button type="button" onclick="swalSubmit('form-cancel','Cancel Voucher?','This cannot be undone.','warning','Yes, Cancel','#fa5c7c')"
            class="w-full py-2 rounded-lg border border-danger/40 text-danger text-xs font-semibold hover:!bg-danger hover:text-white transition-colors">
            Cancel Voucher
        </button>

        @elseif($transaction->status === 'approved')
        <button type="button" onclick="swalSubmit('form-paid','Mark as Paid?','This voucher will be marked paid.','question','Mark Paid','#0acf97')"
            class="w-full py-2 rounded-lg !bg-success text-white text-xs font-semibold hover:!bg-success/90 transition-colors">
            <i class="mgc_currency_dollar_line mr-1"></i> Mark Paid
        </button>
        @endif

        @if($transaction->status === 'draft')
        <div class="pt-2 border-t dark:border-gray-700">
            <button type="button" onclick="swalSubmit('form-delete','Delete Draft?','This voucher will be permanently deleted.','warning','Delete','#fa5c7c')"
                class="w-full py-2 rounded-lg !bg-gray-100 dark:!bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-medium hover:!bg-danger hover:text-white transition-colors">
                Delete Draft
            </button>
        </div>
        @endif
    </div>

    {{-- Print --}}
    <a href="{{ route('budget.transactions.print', $transaction) }}" target="_blank"
        class="w-full py-2.5 rounded-xl flex items-center justify-center gap-2 text-sm font-semibold text-white !bg-gray-700 hover:!bg-gray-800 transition-colors">
        <i class="mgc_print_line"></i> Print Voucher
    </a>

    {{-- Summary card --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-4 space-y-3">
        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Summary</p>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-400 text-xs">Gross</span>
                <span class="tabular-nums text-gray-700 dark:text-gray-200">₱{{ number_format($gross, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400 text-xs">Tax</span>
                <span class="tabular-nums text-gray-600 dark:text-gray-300">− ₱{{ number_format($tax, 2) }}</span>
            </div>
            <div class="flex justify-between border-t dark:border-gray-700 pt-2">
                <span class="font-semibold text-xs text-gray-600 dark:text-gray-300">Net</span>
                <span class="tabular-nums font-bold text-primary">₱{{ number_format($net, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Back --}}
    <a href="{{ route('budget.transactions.index', ['fy' => $transaction->fiscal_year_id]) }}"
        class="w-full py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center justify-center gap-2">
        ← All Vouchers
    </a>

</div>

</div>

@endsection

@push('inline-scripts')
<style>
.swal2-cancel.swal2-styled { background-color: #6c757d !important; }
</style>
<script>
function swalSubmit(formId, title, text, icon, confirmText, confirmColor) {
    Swal.fire({
        title, text, icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', confirmColor, 'important');
            document.querySelector('.swal2-cancel').style.setProperty('background-color', '#6c757d', 'important');
        },
    }).then(r => { if (r.isConfirmed) document.getElementById(formId).submit(); });
}

function swalDeleteAttachment(btn) {
    Swal.fire({
        title: 'Delete attachment?',
        text: 'This file will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important');
            document.querySelector('.swal2-cancel').style.setProperty('background-color', '#6c757d', 'important');
        },
    }).then(r => { if (r.isConfirmed) btn.closest('form').submit(); });
}
</script>
@endpush
