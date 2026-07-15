@extends('layouts.vertical', [
    'title'         => 'Manage Item',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => 'Manage Item',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
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

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $item->name }}</h2>
        @if($item->sku)<p class="text-xs font-mono text-gray-400">{{ $item->sku }}</p>@endif
    </div>
    <a href="{{ route('inventory.items.index') }}" class="btn btn-sm rounded-full bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
        <i class="mgc_arrow_left_line"></i> Back
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left column --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Item Details accordion --}}
        @php $hasErrors = $errors->any(); @endphp
        <div class="card" id="accordion-details">
            <button type="button"
                    class="w-full flex items-center justify-between px-5 py-4 text-left"
                    onclick="toggleAccordion('details-body', 'details-chevron')">
                <span class="flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wide">
                    <i class="mgc_edit_line text-primary"></i> Item Details
                </span>
                <i id="details-chevron" class="mgc_{{ $hasErrors ? 'up' : 'down' }}_line text-gray-400 transition-transform"></i>
            </button>
            <div id="details-body" class="{{ $hasErrors ? '' : 'hidden' }} border-t border-gray-100 dark:border-gray-700 px-5 pb-5 pt-4">
                <form action="{{ route('inventory.items.update', $item) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    @include('inventory.items._form')
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="submit" class="btn bg-primary text-white">Update Item</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stock History --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Stock History</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Type</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Details / Note</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockIns as $si)
                        <tr>
                            <td class="px-5 py-3 text-gray-500 text-xs">
                                {{ $si->created_at->format('M d, Y') }}<br>
                                <span class="text-gray-300">{{ $si->created_at->format('g:i A') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($si->type === 'adjustment')
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-yellow-400 rounded-full"></span>Adjustment
                                    </span>
                                @elseif($si->type === 'release')
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Release
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Stock In
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php $diff = $si->quantity; @endphp
                                <span class="font-semibold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $diff >= 0 ? '+' . number_format($diff) : number_format($diff) }}
                                </span>
                                @if(!is_null($si->quantity_before))
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ number_format($si->quantity_before) }} → {{ number_format($si->quantity_before + $diff) }}
                                </p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500 max-w-xs">
                                @if($si->reference)
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $si->reference }}</p>
                                @endif
                                @if($si->source)
                                <p class="text-xs text-gray-500">{{ $si->source }}</p>
                                @endif
                                @if($si->expiry_date)
                                <p class="text-xs text-warning">Exp: {{ $si->expiry_date->format('M d, Y') }}</p>
                                @endif
                                @if($si->notes)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $si->notes }}</p>
                                @endif
                                @if(!$si->reference && !$si->source && !$si->expiry_date && !$si->notes) —@endif
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-sm">{{ $si->createdBy->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-6 text-gray-400">No stock entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="space-y-4">

        {{-- Current stock card --}}
        <div class="card p-6 text-center">
            @if($item->image)
            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}"
                 class="w-full max-h-48 object-cover rounded-lg mb-4 border border-gray-200 dark:border-gray-600">
            @endif
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Current Stock</p>
            <p class="text-5xl font-bold {{ $item->stock == 0 ? 'text-danger' : ($item->isLowStock() ? 'text-warning' : 'text-success') }}">
                {{ number_format($item->stock) }}
            </p>
            <p class="text-sm text-gray-400 mt-1">{{ $item->unit }}</p>
            @if($item->stock == 0)
                <span class="mt-2 inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Out of Stock
                </span>
            @elseif($item->isLowStock())
                <span class="mt-2 inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <span class="w-1.5 h-1.5 inline-block bg-yellow-400 rounded-full"></span>Low Stock (threshold: {{ $item->low_stock_threshold }})
                </span>
            @endif
        </div>

        {{-- Add Stock (always open) --}}
        <div class="card p-6">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4 text-sm uppercase tracking-wide flex items-center gap-2">
                <i class="mgc_add_circle_line text-primary"></i> Add Stock
            </h3>
            <form action="{{ route('inventory.items.stock-in', $item) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" min="1" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                    <input type="text" name="source" class="form-input w-full" placeholder="e.g. DOH donation, LGU purchase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference / PO No.</label>
                    <input type="text" name="reference" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="form-input w-full"></textarea>
                </div>
                <button type="submit" class="btn bg-primary text-white w-full gap-2">
                    <i class="mgc_add_line"></i> Add to Stock
                </button>
            </form>
        </div>

        {{-- Adjust Stock accordion --}}
        <div class="card" id="accordion-adjust">
            <button type="button"
                    class="w-full flex items-center justify-between px-5 py-4 text-left"
                    onclick="toggleAccordion('adjust-body', 'adjust-chevron')">
                <span class="flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wide">
                    <i class="mgc_edit_2_line text-primary"></i> Adjust Stock
                </span>
                <i id="adjust-chevron" class="mgc_down_line text-gray-400 transition-transform"></i>
            </button>
            <div id="adjust-body" class="hidden border-t border-gray-100 dark:border-gray-700 px-5 pb-5 pt-4">
                <p class="text-xs text-gray-400 mb-4">Set the actual physical count. The difference will be logged in history with your note.</p>
                <form action="{{ route('inventory.items.adjust', $item) }}" method="POST" class="space-y-3"
                      onsubmit="confirmAdjust(event)">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Actual Quantity <span class="text-danger">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="actual_qty" id="actual-qty-input" min="0"
                                   class="form-input w-full text-lg font-semibold" required
                                   placeholder="{{ $item->stock }}"
                                   oninput="updateAdjustPreview(this.value)">
                            <span class="text-sm text-gray-400 shrink-0">{{ $item->unit }}</span>
                        </div>
                        <p id="adjust-preview" class="text-xs mt-1 min-h-[1rem]"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reason / Note <span class="text-danger">*</span>
                        </label>
                        <textarea name="notes" rows="2" class="form-input w-full text-sm"
                                  placeholder="e.g. Physical count — found 3 damaged units" required></textarea>
                    </div>
                    <button type="submit" class="btn bg-primary text-white w-full gap-2">
                        <i class="mgc_edit_2_line"></i> Apply Adjustment
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection

@push('inline-scripts')
<script>
const CURRENT_STOCK = {{ $item->stock }};

function toggleAccordion(bodyId, chevronId) {
    const body    = document.getElementById(bodyId);
    const chevron = document.getElementById(chevronId);
    const opening = body.classList.contains('hidden');
    body.classList.toggle('hidden');
    chevron.classList.toggle('mgc_down_line', !opening);
    chevron.classList.toggle('mgc_up_line', opening);
}

function updateAdjustPreview(val) {
    const preview = document.getElementById('adjust-preview');
    const n = parseInt(val, 10);
    if (isNaN(n) || val === '') { preview.textContent = ''; return; }
    const diff = n - CURRENT_STOCK;
    if (diff === 0) {
        preview.textContent = 'No change from current stock.';
        preview.className = 'text-xs mt-1 min-h-[1rem] text-gray-400';
    } else if (diff > 0) {
        preview.textContent = `+${diff} — stock will increase from ${CURRENT_STOCK} to ${n}`;
        preview.className = 'text-xs mt-1 min-h-[1rem] text-success';
    } else {
        preview.textContent = `${diff} — stock will decrease from ${CURRENT_STOCK} to ${n}`;
        preview.className = 'text-xs mt-1 min-h-[1rem] text-danger';
    }
}

function confirmAdjust(e) {
    e.preventDefault();
    const val = parseInt(document.getElementById('actual-qty-input').value, 10);
    if (isNaN(val)) return;
    const diff = val - CURRENT_STOCK;
    if (diff === 0) { e.target.submit(); return; }
    const dir = diff > 0 ? `increase by ${diff}` : `decrease by ${Math.abs(diff)}`;
    Swal.fire({
        title: 'Apply Adjustment?',
        text: `Stock will change from ${CURRENT_STOCK} to ${val} (${dir}). This will be logged in history.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4361ee',
    }).then(result => {
        if (result.isConfirmed) e.target.submit();
    });
}
</script>
@endpush
