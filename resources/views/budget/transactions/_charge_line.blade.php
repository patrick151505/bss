{{-- Rendered server-side for the first line; subsequent lines added via JS --}}
<div class="col-span-4">
    <label class="text-xs text-gray-400 mb-1 block">Program</label>
    <select name="lines[{{ $index }}][program_id_ui]"
        class="form-select w-full text-sm prog-select">
        <option value="">Select program</option>
        @foreach($programs as $prog)
        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-span-5">
    <label class="text-xs text-gray-400 mb-1 block">Line Item <span class="text-danger">*</span></label>
    <select name="lines[{{ $index }}][line_item_id]" class="form-select w-full text-sm line-select" required>
        <option value="">— pick a program first —</option>
    </select>
</div>
<div class="col-span-2">
    <label class="text-xs text-gray-400 mb-1 block">Amount <span class="text-danger">*</span></label>
    <input type="number" name="lines[{{ $index }}][amount]"
        class="form-input w-full text-sm text-right tabular-nums line-amount-input"
        step="0.01" min="0.01" placeholder="0.00" oninput="updateLinesTotal()" required>
</div>
<div class="col-span-1 flex items-end justify-center pb-0.5">
    <button type="button" onclick="removeLine(this)"
        class="p-1.5 text-gray-400 hover:text-danger rounded transition-colors">
        <i class="mgc_delete_line"></i>
    </button>
</div>
