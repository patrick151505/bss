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
                <form action="{{ route('inventory.items.image.delete', $item) }}" method="POST"
                      id="remove-image-form">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-xs btn-soft-danger gap-1"
                            onclick="swalRemoveImage()">
                        <i class="mgc_delete_line text-xs"></i> Remove
                    </button>
                </form>
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

    <div class="flex items-center gap-6">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="requires_approval" value="1"
                   {{ old('requires_approval', $item->requires_approval ?? false) ? 'checked' : '' }}
                   class="form-checkbox">
            <span class="text-sm text-gray-700 dark:text-gray-300">Requires approval before release</span>
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="item_is_active" value="1"
                   {{ old('is_active', ($item->is_active ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}
                   class="form-checkbox">
            <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
        </label>
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

function swalRemoveImage() {
    Swal.fire({
        title: 'Remove Image?',
        text: 'The current image will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#fa5c7c',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('remove-image-form').submit();
    });
}
</script>
@endpush
