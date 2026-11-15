{{-- One medicine row on the check-up form. Expects $i, $row, $medicineItems. --}}
@php $sel = (int) ($row['item_id'] ?? 0); $selItem = $medicineItems->firstWhere('id', $sel); @endphp
<div class="med-row space-y-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="flex gap-2">
        <select name="medicines[{{ $i }}][item_id]" class="form-select flex-1 min-w-0 text-sm med-item">
            <option value="">— Pick from Inventory —</option>
            @foreach($medicineItems->groupBy(fn($it) => $it->category?->name ?? 'Other') as $cat => $items)
            <optgroup label="{{ $cat }}">
                @foreach($items as $it)
                <option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-stock="{{ $it->stock }}" @selected($sel === $it->id)>{{ $it->name }} ({{ $it->stock }} {{ $it->unit }} left)</option>
                @endforeach
            </optgroup>
            @endforeach
        </select>
        <button type="button" onclick="this.closest('.med-row').remove()" class="btn btn-sm bg-danger/10 text-danger hover:bg-danger hover:text-white" title="Remove">
            <i class="mgc_close_line"></i>
        </button>
    </div>
    <div class="flex gap-2 items-center">
        <input type="number" name="medicines[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" min="1" @if($selItem) max="{{ $selItem->stock }}" @endif
               class="form-input w-20 text-sm text-center med-qty" placeholder="Qty">
        <span class="text-xs text-gray-500 dark:text-gray-400 w-10 med-unit">{{ $selItem?->unit }}</span>
        <input type="text" name="medicines[{{ $i }}][instructions]" value="{{ $row['instructions'] ?? '' }}" maxlength="255"
               class="form-input flex-1 min-w-0 text-sm" placeholder="1 tab every 6 hrs">
    </div>
</div>
