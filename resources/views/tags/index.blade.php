@extends('layouts.vertical', [
    'title'    => 'Tags',
    'sub_title'=> 'Citizen Management',
    'tagline'  => 'Create and manage tags to categorize and filter citizens.',
])

@section('content')

<div class="grid grid-cols-12 gap-4">

    {{-- Create / Edit form --}}
    <div class="col-span-12 lg:col-span-4">
        <div class="card p-5">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4" id="form-title">
                <i class="mgc_tag_line mr-1"></i> Add New Tag
            </p>

            <div class="flex flex-col gap-3">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tag Name</label>
                    <input type="text" id="tag-name" maxlength="50"
                           class="form-input" placeholder="e.g. Senior, Scholar, PWD…">
                    <p class="text-xs text-danger mt-1 hidden" id="name-error"></p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="tag-description" maxlength="255" rows="2"
                              class="form-input resize-none"
                              placeholder="What does this tag mean? Who should be tagged with it?"></textarea>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Color</label>
                    <div class="flex flex-col gap-2">
                        {{-- Swatches --}}
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6','#f97316','#64748b','#0ea5e9','#84cc16','#f43f5e','#a855f7','#06b6d4','#eab308','#78716c','#1d4ed8','#be123c','#166534'] as $c)
                            <button type="button" onclick="pickSwatch('{{ $c }}')"
                                    class="swatch w-6 h-6 rounded-full border-2 border-white shadow-sm ring-1 ring-gray-200 dark:ring-gray-600 hover:scale-110 transition"
                                    data-color="{{ $c }}"
                                    style="background:{{ $c }}" title="{{ $c }}"></button>
                            @endforeach
                        </div>
                        {{-- Color picker + hex input --}}
                        <div class="flex items-center gap-2">
                            <input type="color" id="tag-color" value="#6366f1"
                                   class="w-10 h-9 rounded cursor-pointer border border-gray-200 dark:border-gray-700 p-0.5 shrink-0"
                                   oninput="onPickerChange(this.value)">
                            <input type="text" id="tag-color-hex" value="#6366f1" maxlength="7"
                                   class="form-input font-mono text-sm w-28"
                                   placeholder="#000000"
                                   oninput="onHexInput(this.value)">
                            <span class="text-xs text-gray-400">or type any hex</span>
                        </div>
                    </div>
                </div>

                {{-- Preview --}}
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Preview</label>
                    <span id="tag-preview"
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold text-white"
                          style="background:#6366f1">Sample Tag</span>
                </div>

                <div class="flex gap-2 pt-1">
                    <button id="save-btn" onclick="saveTag()"
                            class="btn bg-primary text-white flex-1">
                        <i class="mgc_save_line mr-1"></i> Save
                    </button>
                    <button id="cancel-btn" onclick="cancelEdit()" class="hidden btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tags list --}}
    <div class="col-span-12 lg:col-span-8">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title"><i class="mgc_tag_2_line mr-2"></i> All Tags</h5>
                <span class="text-sm text-gray-500" id="tag-count">{{ $tags->count() }} {{ Str::plural('tag', $tags->count()) }}</span>
            </div>

            <div id="tags-list" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($tags as $tag)
                <div class="tag-row flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                     data-id="{{ $tag->id }}"
                     data-name="{{ $tag->name }}"
                     data-color="{{ $tag->color }}"
                     data-description="{{ $tag->description }}">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold text-white shrink-0 mt-0.5"
                              style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                        <div class="min-w-0">
                            @if($tag->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-snug">{{ $tag->description }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">{{ $tag->citizens_count }} {{ Str::plural('citizen', $tag->citizens_count) }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0 ml-3">
                        <button onclick="editTag(this)" title="Edit"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-primary hover:bg-primary/10 transition">
                            <i class="mgc_edit_line text-base"></i>
                        </button>
                        <button onclick="deleteTag(this)" title="Delete"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-danger hover:bg-danger/10 transition">
                            <i class="mgc_delete_line text-base"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div id="empty-state" class="px-5 py-10 text-center text-sm text-gray-400">
                    No tags yet. Create your first tag using the form on the left.
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- Delete / Replace modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_delete_line text-danger"></i> Delete Tag
            </h6>
            <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>
        <div class="p-5 flex flex-col gap-4">

            {{-- Tag being deleted --}}
            <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <span id="del-badge"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold text-white shrink-0">
                </span>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200" id="del-name"></p>
                    <p class="text-xs text-gray-400" id="del-count"></p>
                </div>
            </div>

            {{-- Replace with --}}
            <div id="del-replace-wrap">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">
                    Replace with <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <select id="del-replace-select" class="form-select w-full">
                    <option value="">— No replacement, just remove —</option>
                </select>
                <p class="text-xs text-gray-400 mt-1.5">
                    Citizens assigned this tag will be switched to the selected tag before deletion.
                </p>
            </div>

            <div class="flex gap-2 pt-1">
                <button onclick="closeDeleteModal()"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex-1">
                    Cancel
                </button>
                <button onclick="confirmDelete()"
                        class="btn bg-danger text-white flex-1 flex items-center justify-center gap-2" id="del-confirm-btn">
                    <i class="mgc_delete_line"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
const STORE_URL   = '{{ route('tags.store') }}';
const UPDATE_BASE = '{{ url('tags') }}';
const CSRF        = '{{ csrf_token() }}';

let editingId = null;

document.getElementById('tag-name').addEventListener('input', updatePreview);
document.getElementById('tag-color').addEventListener('input', updatePreview);

function getColor() {
    return document.getElementById('tag-color').value;
}

function setColor(hex) {
    // Normalise — ensure valid 6-char hex before applying
    const valid = /^#[0-9a-fA-F]{6}$/.test(hex);
    document.getElementById('tag-color').value     = valid ? hex : getColor();
    document.getElementById('tag-color-hex').value = valid ? hex : getColor();
    updateSwatchRings(valid ? hex : getColor());
    updatePreview();
}

function pickSwatch(hex) {
    setColor(hex);
}

function onPickerChange(hex) {
    document.getElementById('tag-color-hex').value = hex;
    updateSwatchRings(hex);
    updatePreview();
}

function onHexInput(val) {
    // Accept typing — only apply when it looks like a full hex
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        document.getElementById('tag-color').value = val;
        updateSwatchRings(val);
        updatePreview();
    }
}

function updateSwatchRings(hex) {
    document.querySelectorAll('.swatch').forEach(btn => {
        const match = btn.dataset.color.toLowerCase() === hex.toLowerCase();
        btn.classList.toggle('ring-2', match);
        btn.classList.toggle('ring-primary', match);
        btn.classList.toggle('ring-offset-1', match);
        btn.classList.toggle('ring-1', !match);
        btn.classList.toggle('ring-gray-200', !match);
    });
}

function updatePreview() {
    const name  = document.getElementById('tag-name').value.trim() || 'Sample Tag';
    const color = getColor();
    const prev  = document.getElementById('tag-preview');
    prev.textContent      = name;
    prev.style.background = color;
}

function saveTag() {
    const name        = document.getElementById('tag-name').value.trim();
    const color       = getColor();
    const description = document.getElementById('tag-description').value.trim();
    const errEl       = document.getElementById('name-error');
    errEl.classList.add('hidden');

    if (!name) { showErr('Name is required.'); return; }

    const url    = editingId ? `${UPDATE_BASE}/${editingId}` : STORE_URL;
    const method = editingId ? 'PUT' : 'POST';

    fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color, description }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.errors) { showErr(Object.values(data.errors)[0][0]); return; }
        if (editingId) {
            updateRow(editingId, data.tag);
        } else {
            prependRow(data.tag);
        }
        cancelEdit();
    })
    .catch(() => showErr('Something went wrong.'));
}

function editTag(btn) {
    const row = btn.closest('.tag-row');
    editingId  = row.dataset.id;
    document.getElementById('tag-name').value        = row.dataset.name;
    document.getElementById('tag-description').value = row.dataset.description || '';
    setColor(row.dataset.color);
    document.getElementById('form-title').innerHTML  = '<i class="mgc_edit_line mr-1"></i> Edit Tag';
    document.getElementById('cancel-btn').classList.remove('hidden');
    updatePreview();
    document.getElementById('tag-name').focus();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancelEdit() {
    editingId = null;
    document.getElementById('tag-name').value        = '';
    document.getElementById('tag-description').value = '';
    setColor('#6366f1');
    document.getElementById('form-title').innerHTML  = '<i class="mgc_tag_line mr-1"></i> Add New Tag';
    document.getElementById('cancel-btn').classList.add('hidden');
    document.getElementById('name-error').classList.add('hidden');
    updatePreview();
}

let deletingRow = null;

function deleteTag(btn) {
    deletingRow = btn.closest('.tag-row');
    const id    = deletingRow.dataset.id;
    const name  = deletingRow.dataset.name;
    const color = deletingRow.dataset.color;
    const count = deletingRow.querySelector('.text-gray-400').textContent.trim();

    // Populate modal
    const badge = document.getElementById('del-badge');
    badge.textContent      = name;
    badge.style.background = color;
    document.getElementById('del-name').textContent  = name;
    document.getElementById('del-count').textContent = count;

    // Build replacement options from other tag rows
    const sel = document.getElementById('del-replace-select');
    sel.innerHTML = '<option value="">— No replacement, just remove —</option>';
    document.querySelectorAll('.tag-row').forEach(row => {
        if (row.dataset.id === id) return;
        const opt = document.createElement('option');
        opt.value       = row.dataset.id;
        opt.textContent = row.dataset.name;
        sel.appendChild(opt);
    });

    // Hide replace picker if no other tags exist
    document.getElementById('del-replace-wrap').style.display =
        sel.options.length > 1 ? '' : 'none';

    document.getElementById('delete-modal').classList.replace('hidden', 'flex');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.replace('flex', 'hidden');
    deletingRow = null;
}

function confirmDelete() {
    if (!deletingRow) return;

    const id        = deletingRow.dataset.id;
    const replaceId = document.getElementById('del-replace-select').value;
    const btn       = document.getElementById('del-confirm-btn');

    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Deleting…';

    fetch(`${UPDATE_BASE}/${id}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ replace_with: replaceId ? parseInt(replaceId) : 0 }),
    })
    .then(r => r.json())
    .then(() => {
        deletingRow.remove();
        updateCount();
        closeDeleteModal();
        if (!document.querySelector('.tag-row')) {
            document.getElementById('tags-list').innerHTML =
                '<div id="empty-state" class="px-5 py-10 text-center text-sm text-gray-400">No tags yet.</div>';
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_delete_line"></i> Delete';
    });
}

// Close on backdrop click
document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

function prependRow(tag) {
    const empty = document.getElementById('empty-state');
    if (empty) empty.remove();
    document.getElementById('tags-list').prepend(buildRow(tag));
    updateCount();
}

function updateRow(id, tag) {
    const row = document.querySelector(`.tag-row[data-id="${id}"]`);
    if (!row) return;
    row.dataset.name        = tag.name;
    row.dataset.color       = tag.color;
    row.dataset.description = tag.description || '';

    const badge = row.querySelector('span');
    badge.textContent      = tag.name;
    badge.style.background = tag.color;

    const descEl = row.querySelector('.tag-desc');
    if (descEl) descEl.textContent = tag.description || '';
    else if (tag.description) {
        // description was empty before, inject it now
        row.querySelector('.min-w-0').insertAdjacentHTML('afterbegin',
            `<p class="tag-desc text-xs text-gray-500 dark:text-gray-400 leading-snug">${tag.description}</p>`);
    }
}

function buildRow(tag) {
    const div = document.createElement('div');
    div.className           = 'tag-row flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50';
    div.dataset.id          = tag.id;
    div.dataset.name        = tag.name;
    div.dataset.color       = tag.color;
    div.dataset.description = tag.description || '';
    div.innerHTML = `
        <div class="flex items-start gap-3 min-w-0">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold text-white shrink-0 mt-0.5"
                  style="background:${tag.color}">${tag.name}</span>
            <div class="min-w-0">
                ${tag.description ? `<p class="tag-desc text-xs text-gray-500 dark:text-gray-400 leading-snug">${tag.description}</p>` : ''}
                <p class="text-xs text-gray-400 mt-0.5">0 citizens</p>
            </div>
        </div>
        <div class="flex gap-2 shrink-0 ml-3">
            <button onclick="editTag(this)" title="Edit"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-primary hover:bg-primary/10 transition">
                <i class="mgc_edit_line text-base"></i>
            </button>
            <button onclick="deleteTag(this)" title="Delete"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-danger hover:bg-danger/10 transition">
                <i class="mgc_delete_line text-base"></i>
            </button>
        </div>`;
    return div;
}

function updateCount() {
    const n = document.querySelectorAll('.tag-row').length;
    document.getElementById('tag-count').textContent = n + ' ' + (n === 1 ? 'tag' : 'tags');
}

function showErr(msg) {
    const el = document.getElementById('name-error');
    el.textContent = msg;
    el.classList.remove('hidden');
}
</script>
@endsection
