{{-- Check-up form section. Inherits the variables set up in health._form. --}}
<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h4 class="card-title">Medicines given</h4>
            @if($canDispense && $medicineItems->isNotEmpty())
            <button type="button" onclick="addMedRow()" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white">
                <i class="mgc_add_line me-1"></i>Add
            </button>
            @endif
        </div>
    </div>
    <div class="p-6">
        @if(!$isNew)
            @forelse($C->medicines as $m)
            <div class="flex justify-between gap-2 text-sm py-1.5 {{ $loop->last ? '' : 'border-b border-gray-100 dark:border-gray-700' }}">
                <span class="text-gray-800 dark:text-gray-200">{{ $m->name }}</span>
                <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $m->quantity }} {{ $m->unit }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">None.</p>
            @endforelse
            <p class="text-xs text-gray-400 mt-3">Medicines can't be changed here because they already came out of stock. Use a stock adjustment in Inventory to correct a mistake.</p>
        @elseif(!$canDispense)
            <p class="text-sm text-gray-400">You can't give medicines from stock. Ask someone with the “Dispense” permission, or write them on the prescription (reseta).</p>
        @elseif($medicineItems->isEmpty())
            <p class="text-sm text-gray-400">No medicines in stock — write what the patient needs on the prescription (reseta). Only items in a category marked “Use for medicine” are listed. @can('inventory_categories.view')<a href="{{ route('inventory.categories.index') }}" class="text-primary hover:underline">Set it in Inventory → Categories</a>@endcan</p>
        @else
            <div id="med-rows" class="space-y-3">
                @foreach($medRows as $i => $row)
                @include('health._med-row', ['i' => $i, 'row' => $row])
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-3">Taken out of Inventory stock when you save. Not available here? Put it on the prescription (reseta).</p>
        @endif
    </div>
</div>
