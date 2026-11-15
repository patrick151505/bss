@extends('layouts.vertical', [
    'title'       => 'Rental #' . $rental->id,
    'breadcrumbs' => [
        ['label' => 'Inventory', 'url' => route('inventory.items.index')],
        ['label' => 'Rentals',   'url' => route('inventory.rentals.index')],
        ['label' => '#' . $rental->id, 'url' => ''],
    ],
    'tagline'     => 'Rental details and returns',
])

@section('content')
@php
    // Release, return, payment and cancel need inventory_rentals.edit; others only see the record.
    $canEditRental = auth()->user()->can('inventory_rentals.edit');
    $returning     = $rental->isOut() && $canEditRental;
@endphp

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

{{-- Progress: scheduled → out → returned --}}
@if(!$rental->isCancelled())
@php
    $stepIndex = $rental->isReturned() ? 2 : ($rental->isOut() ? 1 : 0);
    $steps = [
        ['Scheduled', 'mgc_calendar_line',     $rental->created_at?->format('M d, Y')],
        ['Released',  'mgc_arrow_right_line',  $rental->released_at?->format('M d, Y')],
        ['Returned',  'mgc_check_circle_line', $rental->returned_on?->format('M d, Y')],
    ];
@endphp
<div class="card p-5 mb-5">
    <div class="flex items-center">
        @foreach($steps as $i => [$label, $icon, $date])
        @php $done = $i <= $stepIndex; @endphp
        <div class="flex flex-col items-center shrink-0 w-28">
            <div class="w-10 h-10 rounded-full flex items-center justify-center
                        {{ $done ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                <i class="{{ $icon }} text-lg"></i>
            </div>
            <p class="text-xs font-medium mt-2 {{ $done ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400' }}">{{ $label }}</p>
            <p class="text-[10px] text-gray-400">{{ $date ?? '—' }}</p>
        </div>
        @if($i < count($steps) - 1)
        <div class="flex-1 h-0.5 -mt-8 {{ $i < $stepIndex ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
        @endif
        @endforeach
    </div>
</div>
@endif

@if($rental->isScheduled())
<div class="mb-4 p-4 rounded-lg bg-info/10 border border-info/30 flex flex-wrap items-center justify-between gap-3">
    <div class="flex gap-3">
        <i class="mgc_calendar_line text-info text-xl mt-0.5 shrink-0"></i>
        <p class="text-sm text-gray-700 dark:text-gray-200">
            Booked for {{ $rental->borrowed_on?->format('M d, Y') }} — stock is reserved but not yet handed over.
            @if($rental->isMissedPickup())
            <span class="block text-warning font-medium mt-0.5">The borrow date has passed and the items were not collected.</span>
            @endif
        </p>
    </div>
    @if($canEditRental)
    <form action="{{ route('inventory.rentals.release', $rental) }}" method="POST">
        @csrf @method('PATCH')
        <button type="submit" class="btn bg-primary text-white">
            <i class="mgc_arrow_right_line"></i> Mark as Released
        </button>
    </form>
    @endif
</div>
@endif

@if($rental->isOverdue())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">
        Overdue by {{ $rental->daysOverdue() }} {{ \Illuminate\Support\Str::plural('day', $rental->daysOverdue()) }} —
        due {{ $rental->due_on->format('M d, Y') }}.
    </p>
</div>
@endif

<div class="grid grid-cols-12 gap-6">

    {{-- Left: items + return form --}}
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-5">

        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h4 class="card-title">Items</h4>
                    @if($rental->isOut())
                        <span class="text-xs text-gray-400">{{ $rental->outstandingQuantity() }} unit(s) still out</span>
                    @endif
                </div>
            </div>

            @if($returning)
            <form action="{{ route('inventory.rentals.return', $rental) }}" method="POST" id="return-form">
            @csrf
            @endif

            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Item</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-20">Out</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Returned</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-28">Condition</th>
                            @if($returning)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-64">Return now</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rental->items as $i => $line)
                        <tr class="border-t dark:border-gray-700">
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                <p class="font-medium">{{ $line->item?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $line->item?->category?->name }} ·
                                    out as {{ \App\Models\InventoryRentalItem::CONDITIONS_OUT[$line->condition_out] ?? $line->condition_out }}
                                </p>
                                @if($line->condition_notes)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $line->condition_notes }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center tabular-nums text-gray-700 dark:text-gray-300">
                                {{ $line->quantity }} {{ $line->item?->unit }}
                            </td>
                            <td class="px-5 py-3 text-center tabular-nums {{ $line->isFullyReturned() ? 'text-success font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $line->quantity_returned }}
                            </td>
                            <td class="px-5 py-3">
                                @if($line->condition_in)
                                    @php
                                        $badge = match($line->condition_in) {
                                            'good'    => 'bg-green-100 text-green-800',
                                            'fair'    => 'bg-yellow-100 text-yellow-800',
                                            'damaged' => 'bg-orange-100 text-orange-800',
                                            'lost'    => 'bg-red-100 text-red-800',
                                            default   => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center py-1 px-2.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ \App\Models\InventoryRentalItem::CONDITIONS_IN[$line->condition_in] ?? $line->condition_in }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            @if($returning)
                            <td class="px-5 py-3">
                                @if($line->isFullyReturned())
                                    <span class="text-xs text-success">All back</span>
                                @else
                                <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                                <div class="flex items-center gap-2">
                                    <input type="number" name="lines[{{ $i }}][qty]" value="{{ $line->outstanding() }}"
                                           min="0" max="{{ $line->outstanding() }}"
                                           class="form-input w-16 text-center text-sm tabular-nums">
                                    <select name="lines[{{ $i }}][condition]" class="form-select text-xs py-1 flex-1">
                                        @foreach(\App\Models\InventoryRentalItem::CONDITIONS_IN as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="text" name="lines[{{ $i }}][notes]" maxlength="500"
                                       class="form-input w-full text-xs mt-1.5" placeholder="Notes (optional)">
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($returning)
            <div class="p-4 border-t dark:border-gray-700 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date returned</label>
                    <input type="date" name="returned_on" required value="{{ now()->format('Y-m-d') }}"
                           class="form-input text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="confirmCancel()" class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white">
                        <i class="mgc_close_line"></i> Cancel Rental
                    </button>
                    <button type="submit" class="btn bg-primary text-white">
                        <i class="mgc_inbox_line"></i> Record Return
                    </button>
                </div>
            </div>
            </form>
            @endif
        </div>
    </div>

    {{-- Right: summary --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-5">

        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">Borrower</h6>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $rental->citizen?->full_name ?? '—' }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $rental->citizen?->full_address }}</p>
            @if($rental->citizen?->contact)
            <p class="text-xs text-gray-500">📞 {{ $rental->citizen->contact }}</p>
            @endif
        </div>

        <div class="card p-5 space-y-3">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Schedule</h6>
            @foreach([
                ['Borrowed',  $rental->borrowed_on?->format('M d, Y')],
                ['Due',       $rental->due_on?->format('M d, Y')],
                ['Returned',  $rental->returned_on?->format('M d, Y') ?? '—'],
            ] as [$label, $value])
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $label }}</span>
                <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $value }}</span>
            </div>
            @endforeach
            <div class="flex justify-between text-sm pt-2 border-t dark:border-gray-700">
                <span class="text-gray-500">Status</span>
                <span>
                    @if($rental->isReturned())
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Returned
                        </span>
                    @elseif($rental->isCancelled())
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>Cancelled
                        </span>
                    @elseif($rental->isOverdue())
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Overdue
                        </span>
                    @elseif($rental->isScheduled())
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                            <span class="w-1.5 h-1.5 inline-block bg-cyan-400 rounded-full"></span>Scheduled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-primary/25 text-sky-800">
                            <span class="w-1.5 h-1.5 inline-block bg-sky-400 rounded-full"></span>Out
                        </span>
                    @endif
                </span>
            </div>
        </div>

        @if($rental->rental_fee > 0 || $rental->deposit > 0)
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Fees</h6>
            <form action="{{ route('inventory.rentals.payment', $rental) }}" method="POST" class="space-y-3">
                @csrf @method('PATCH')
                @if($rental->rental_fee > 0)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Rental fee</span>
                    <span class="text-sm tabular-nums font-medium text-gray-800 dark:text-gray-200">₱{{ number_format($rental->rental_fee, 2) }}</span>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="fee_paid" value="1" class="form-checkbox" {{ $rental->fee_paid ? 'checked' : '' }} {{ $canEditRental ? '' : 'disabled' }}>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Fee paid</span>
                </label>
                @endif
                @if($rental->deposit > 0)
                <div class="flex items-center justify-between pt-2 border-t dark:border-gray-700">
                    <span class="text-sm text-gray-500">Deposit</span>
                    <span class="text-sm tabular-nums font-medium text-gray-800 dark:text-gray-200">₱{{ number_format($rental->deposit, 2) }}</span>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="deposit_refunded" value="1" class="form-checkbox" {{ $rental->deposit_refunded ? 'checked' : '' }} {{ $canEditRental ? '' : 'disabled' }}>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Deposit refunded</span>
                </label>
                @endif
                @if($canEditRental)
                <button type="submit" class="btn btn-sm bg-primary text-white w-full">Save</button>
                @endif
            </form>
        </div>
        @endif

        @if($rental->purpose || $rental->remarks)
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 mb-2">Notes</h6>
            @if($rental->purpose)<p class="text-sm text-gray-700 dark:text-gray-300">{{ $rental->purpose }}</p>@endif
            @if($rental->remarks)<p class="text-xs text-gray-500 mt-1">{{ $rental->remarks }}</p>@endif
        </div>
        @endif

        @if($rental->isScheduled() && $canEditRental)
        <button type="button" onclick="confirmCancel()" class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white w-full">
            <i class="mgc_close_line"></i> Cancel Booking
        </button>
        @endif

        <a href="{{ route('inventory.rentals.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white w-full">
            <i class="mgc_arrow_left_line"></i> Back to Rentals
        </a>
    </div>
</div>

{{-- Cancel form, submitted from the Swal confirm --}}
@if($rental->isActive())
<form id="cancel-form" action="{{ route('inventory.rentals.cancel', $rental) }}" method="POST" class="hidden">
    @csrf @method('PATCH')
    <input type="hidden" name="remarks" id="cancel-remarks">
</form>
@endif

@endsection

@push('inline-scripts')
<script>
function confirmCancel() {
    Swal.fire({
        title: 'Cancel this rental?',
        input: 'text',
        inputLabel: 'Reason (required)',
        inputPlaceholder: 'e.g. Borrower did not show up',
        text: 'All outstanding items will be returned to stock.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it',
        reverseButtons: true,
        inputValidator: value => (!value || !value.trim()) ? 'A reason is required.' : undefined,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important');
        },
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('cancel-remarks').value = result.value;
            document.getElementById('cancel-form').submit();
        }
    });
}
</script>
@endpush
