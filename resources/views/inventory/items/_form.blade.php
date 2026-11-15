@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="text-sm text-danger list-disc pl-4 space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select w-full @error('category_id') border-danger @enderror" required>
            <option value="">— Select category —</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('category_id')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Item Name <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}"
                   class="form-input w-full @error('name') border-danger @enderror" required>
            @error('name')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                SKU
                <span class="text-xs font-normal text-gray-400 ml-1">optional, must be unique</span>
            </label>
            <input type="text" name="sku" value="{{ old('sku', $item->sku ?? '') }}"
                   class="form-input w-full font-mono @error('sku') border-danger @enderror"
                   placeholder="e.g. MED-001">
            @error('sku')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit <span class="text-danger">*</span></label>
            <input type="text" name="unit" value="{{ old('unit', $item->unit ?? 'pcs') }}"
                   class="form-input w-full @error('unit') border-danger @enderror"
                   placeholder="pcs, kg, box, bottle…" required>
            @error('unit')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Low Stock Threshold</label>
            <input type="number" name="low_stock_threshold" min="0"
                   value="{{ old('low_stock_threshold', $item->low_stock_threshold ?? 10) }}"
                   class="form-input w-full">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
        <textarea name="description" rows="2" class="form-input w-full">{{ old('description', $item->description ?? '') }}</textarea>
    </div>

    {{-- Image upload --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Picture <span class="text-xs font-normal text-gray-400 ml-1">optional, max 2 MB</span>
        </label>

        @if(!empty($item->image))
        <div class="flex items-center gap-4 mb-3">
            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}"
                 class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
            <div>
                <p class="text-xs text-gray-500 mb-1">Current image</p>
                {{-- No nested <form> here: this partial sits inside the Update form, and a
                     nested form's </form> would close the Update form early (so Update Item
                     would stop working on items that have a picture). The delete form is
                     built on the fly in swalRemoveImage(). --}}
                <button type="button" class="btn btn-xs bg-danger/25 text-danger hover:bg-danger hover:text-white gap-1"
                        onclick="swalRemoveImage()">
                    <i class="mgc_delete_line text-xs"></i> Remove
                </button>
            </div>
        </div>
        @endif

        <div id="image-drop-zone"
             class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-primary/60 transition"
             onclick="document.getElementById('image-input').click()">
            <input type="file" name="image" id="image-input" accept="image/*" class="hidden"
                   onchange="previewImage(this)">
            <div id="image-placeholder">
                <i class="mgc_pic_2_line text-3xl text-gray-300 dark:text-gray-600 mb-2 block"></i>
                <p class="text-sm text-gray-400">Click or drag & drop to upload image</p>
                <p class="text-xs text-gray-300 mt-1">JPG, PNG, GIF, WEBP</p>
            </div>
            <div id="image-preview-wrap" class="hidden">
                <img id="image-preview-img" src="" alt="preview"
                     class="mx-auto max-h-40 rounded-lg object-contain mb-2">
                <p id="image-preview-name" class="text-xs text-gray-400"></p>
                <button type="button" onclick="clearImageInput(event)"
                        class="mt-2 text-xs text-danger hover:underline">Remove</button>
            </div>
        </div>
        @error('image')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-wrap items-center gap-6">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="requires_approval" value="1"
                   {{ old('requires_approval', $item->requires_approval ?? false) ? 'checked' : '' }}
                   class="form-checkbox">
            <span class="text-sm text-gray-700 dark:text-gray-300">Requires approval before release</span>
        </label>
        {{-- Off by default: most stock is consumable and never comes back. --}}
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_rentable" value="1"
                   {{ old('is_rentable', $item->is_rentable ?? false) ? 'checked' : '' }}
                   class="form-checkbox">
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Rentable
                <span class="text-xs text-gray-400 ml-1">lent out and expected back</span>
            </span>
        </label>
        {{-- Off by default: for things booked by date and time (ambulance, hall, vehicle). --}}
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_schedulable" id="item_is_schedulable" value="1"
                   {{ old('is_schedulable', $item->is_schedulable ?? false) ? 'checked' : '' }}
                   class="form-checkbox">
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Can be scheduled
                <span class="text-xs text-gray-400 ml-1">reserved by date and time on the Schedule calendar</span>
            </span>
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="item_is_active" value="1"
                   {{ old('is_active', ($item->is_active ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}
                   class="form-checkbox">
            <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
        </label>
    </div>

    {{-- Schedule settings: only when "Can be scheduled" is ticked --}}
    @php
        $schedOn     = (bool) old('is_schedulable', $item->is_schedulable ?? false);
        $schedColor  = old('schedule_color', $item->schedule_color ?? '') ?: '';
        $schedBuffer = old('schedule_buffer_days', $item->schedule_buffer_days ?? 3);
        $swatches    = ['#3073F1', '#0acf97', '#fa5c7c', '#f59e0b', '#8b5cf6', '#14b8a6', '#ec4899', '#64748b', '#22c55e', '#0ea5e9', '#ef4444', '#a16207'];
    @endphp
    <div id="schedule-settings" class="{{ $schedOn ? '' : 'hidden' }} p-4 rounded-lg border border-primary/20 bg-primary/5 space-y-4">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
            <i class="mgc_calendar_line text-primary"></i> Schedule settings
        </p>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Calendar color</label>
            <input type="hidden" name="schedule_color" id="schedule_color" value="{{ $schedColor }}">
            <div class="flex flex-wrap items-center gap-2">
                @foreach($swatches as $sw)
                    <button type="button" class="sched-swatch w-8 h-8 rounded-full transition" data-color="{{ $sw }}"
                            style="background: {{ $sw }}" title="{{ $sw }}" aria-label="Color {{ $sw }}"></button>
                @endforeach
                <label class="relative w-8 h-8 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center cursor-pointer overflow-hidden" title="Custom color">
                    <i class="mgc_palette_line text-gray-400"></i>
                    <input type="color" id="schedule_color_custom" class="absolute inset-0 opacity-0 cursor-pointer" value="{{ $schedColor ?: '#3073f1' }}">
                </label>
                <button type="button" id="sched-color-auto" class="text-xs px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-400 hover:bg-dark/10">Automatic</button>
                <span class="ms-1 inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    Preview:
                    <span id="sched-color-preview" class="px-2 py-0.5 rounded text-white font-medium">8:00am {{ ($item->name ?? null) ?: 'Item' }}</span>
                </span>
            </div>
            @error('schedule_color')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="schedule_buffer_days">Buffer days between reservations</label>
            <div class="flex items-center gap-2">
                <input type="number" name="schedule_buffer_days" id="schedule_buffer_days" min="0" max="365" value="{{ $schedBuffer }}" class="form-input" style="width: 7rem">
                <span class="text-sm text-gray-500 dark:text-gray-400">days</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Days kept free after each reservation (for return, cleaning or checking) before it can be reserved again. Use 0 for no gap.</p>
            @error('schedule_buffer_days')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

@push('inline-scripts')
<script>
function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('image-preview-img').src  = e.target.result;
        document.getElementById('image-preview-name').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('image-placeholder').classList.add('hidden');
        document.getElementById('image-preview-wrap').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

function clearImageInput(e) {
    e.stopPropagation();
    const input = document.getElementById('image-input');
    input.value = '';
    document.getElementById('image-preview-img').src = '';
    document.getElementById('image-placeholder').classList.remove('hidden');
    document.getElementById('image-preview-wrap').classList.add('hidden');
}

// Drag & drop
const zone = document.getElementById('image-drop-zone');
if (zone) {
    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('border-primary', 'bg-primary/5');
    });
    zone.addEventListener('dragleave', function () {
        zone.classList.remove('border-primary', 'bg-primary/5');
    });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('border-primary', 'bg-primary/5');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const input = document.getElementById('image-input');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            previewImage(input);
        }
    });
}

// Schedule settings: show only when "Can be scheduled" is ticked; color swatches.
(function () {
    const box = document.getElementById('schedule-settings');
    const toggle = document.getElementById('item_is_schedulable');
    if (!box || !toggle) return;
    toggle.addEventListener('change', () => box.classList.toggle('hidden', !toggle.checked));

    const input = document.getElementById('schedule_color');
    const preview = document.getElementById('sched-color-preview');
    function paint() {
        const c = (input.value || '').toLowerCase();
        preview.style.background = c || '#3073F1';
        preview.textContent = preview.textContent.replace(/ \(automatic\)$/, '') + (c ? '' : ' (automatic)');
        document.querySelectorAll('.sched-swatch').forEach(b => {
            const on = b.dataset.color.toLowerCase() === c;
            b.style.boxShadow = on ? '0 0 0 2px #fff, 0 0 0 4px ' + b.dataset.color : 'none';
            b.innerHTML = on ? '<i class="mgc_check_line text-white"></i>' : '';
        });
    }
    document.querySelectorAll('.sched-swatch').forEach(b => b.addEventListener('click', () => { input.value = b.dataset.color; paint(); }));
    document.getElementById('schedule_color_custom').addEventListener('input', e => { input.value = e.target.value; paint(); });
    document.getElementById('sched-color-auto').addEventListener('click', () => { input.value = ''; paint(); });
    paint();
})();

function swalRemoveImage() {
    Swal.fire({
        title: 'Remove Image?',
        text: 'The current image will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important'),
    }).then(result => {
        if (!result.isConfirmed) return;
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = @json(isset($item) && $item->exists ? route('inventory.items.image.delete', $item) : '');
        f.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(f);
        f.submit();
    });
}
</script>
@endpush
