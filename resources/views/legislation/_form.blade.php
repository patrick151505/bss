{{-- Shared create/edit form body. Expects $legislation, $officials, $others, $repealIds, $nextNumbers. --}}
@php
    use App\Models\Legislation;
    $L = $legislation;
    $isNew = !$L->exists;
    $canApprove = auth()->user()->can('legislation.approve');
    $lbl = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $authorRows = old('authors', $L->exists
        ? $L->authors->map(fn($a) => ['name' => $a->name, 'role' => $a->role])->all()
        : []);
    if (!$authorRows) $authorRows = [['name' => '', 'role' => 'author']];
    $oldRepeals = array_map('intval', old('repeals', $repealIds));
    // Only measures that have passed can be amended or repealed.
    $linkable = $others->filter(fn($o) => in_array($o->status, ['approved', 'effective', 'repealed'], true));
@endphp

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="text-sm text-danger list-disc pl-4 space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    {{-- ── Left: the measure ── --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6 space-y-4">
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $lbl }}">Type <span class="text-danger">*</span></label>
                    <select name="type" id="leg-type" class="form-select w-full" required>
                        @foreach(Legislation::TYPES as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', $L->type) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Number <span class="text-danger">*</span></label>
                    <input type="text" name="number" id="leg-number" value="{{ old('number', $L->number) }}" required maxlength="50"
                           class="form-input w-full font-mono @error('number') border-danger @enderror" placeholder="2026-001">
                    @error('number')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $lbl }}">Series of <span class="text-danger">*</span></label>
                    <input type="number" name="series_year" value="{{ old('series_year', $L->series_year) }}" min="1950" max="2100" required class="form-input w-full">
                </div>
            </div>

            <div>
                <label class="{{ $lbl }}">Title <span class="text-danger">*</span></label>
                <textarea name="title" rows="2" required maxlength="500" class="form-input w-full @error('title') border-danger @enderror"
                          placeholder="An ordinance regulating…">{{ old('title', $L->title) }}</textarea>
                @error('title')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    @php
                        $catOptions = Legislation::categoryOptions();
                        $catCurrent = old('category', $L->category);
                        // A category typed in but not saved yet (e.g. form came back with errors)
                        $catTyped   = $catCurrent && !in_array($catCurrent, $catOptions, true) ? $catCurrent : null;
                    @endphp
                    <label class="{{ $lbl }}">Category</label>
                    <select id="category-select" name="category" class="form-select w-full" @disabled($catTyped)>
                        <option value="">— None —</option>
                        @foreach($catOptions as $c)
                        <option value="{{ $c }}" @selected($catCurrent === $c)>{{ $c }}</option>
                        @endforeach
                        <option value="__new" @selected($catTyped)>+ Add new category…</option>
                    </select>
                    <div id="category-new-wrap" class="mt-2 {{ $catTyped ? '' : 'hidden' }}">
                        <div class="flex gap-2">
                            <input type="text" id="category-new" name="category" maxlength="100"
                                   value="{{ $catTyped }}" placeholder="New category name"
                                   class="form-input w-full" @disabled(!$catTyped)>
                            <button type="button" id="category-new-cancel" title="Back to the list"
                                    class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                                <i class="mgc_close_line"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Saved with this measure and added to the category list.</p>
                    </div>
                    @error('category')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Summary <span class="text-xs font-normal text-gray-400 ml-1">what it does, in a few lines</span></label>
                    <textarea name="summary" rows="3" class="form-input w-full">{{ old('summary', $L->summary) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Authors --}}
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h4 class="card-title">Authors &amp; sponsors</h4>
                    <button type="button" onclick="addAuthorRow()" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white">
                        <i class="mgc_add_line me-1"></i>Add
                    </button>
                </div>
            </div>
            <div class="p-6">
                <datalist id="officials-list">
                    @foreach($officials as $o)<option value="{{ $o->name }}">{{ $o->position }}</option>@endforeach
                </datalist>
                <div id="author-rows" class="space-y-2">
                    @foreach($authorRows as $i => $row)
                    <div class="author-row flex gap-2">
                        <input type="text" name="authors[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" list="officials-list"
                               class="form-input flex-1" placeholder="Hon. Juan Dela Cruz">
                        <select name="authors[{{ $i }}][role]" class="form-select w-36">
                            @foreach(Legislation::AUTHOR_ROLES as $val => $label)
                            <option value="{{ $val }}" @selected(($row['role'] ?? 'author') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="this.closest('.author-row').remove()" class="btn btn-sm bg-danger/10 text-danger hover:bg-danger hover:text-white" title="Remove">
                            <i class="mgc_close_line"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    Type a name or pick a barangay official.
                    @if($officials->isEmpty())
                        No officials are set up yet.
                        @can('settings.view') Add them in <a href="{{ route('officials.index') }}" class="text-primary hover:underline">Settings → Officials</a>. @endcan
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ── Right: status, dates, links, file ── --}}
    <div class="space-y-6">
        <div class="card p-6 space-y-4">
            <div>
                <label class="{{ $lbl }}">Status</label>
                @if($canApprove)
                <select name="status" class="form-select w-full">
                    @foreach(Legislation::STATUSES as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', $L->status) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $isNew ? 'Keep Draft for a new measure and move it along from its page. Pick a later status only to record a measure passed earlier.' : 'Usually changed with “Next step” on the measure’s page, so the timeline records it.' }}
                </p>
                @else
                <input type="text" value="{{ Legislation::STATUSES[$L->status] ?? 'Draft' }}" disabled class="form-input w-full bg-gray-50 dark:bg-gray-700/50">
                <p class="text-xs text-gray-400 mt-1">Set by staff who can approve measures.</p>
                @endif
            </div>
            <div>
                <label class="{{ $lbl }}">Date introduced</label>
                <input type="date" name="date_introduced" value="{{ old('date_introduced', $L->date_introduced?->format('Y-m-d')) }}" class="form-input w-full">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $lbl }}">Date approved</label>
                    <input type="date" name="date_approved" value="{{ old('date_approved', $L->date_approved?->format('Y-m-d')) }}" class="form-input w-full">
                </div>
                <div>
                    <label class="{{ $lbl }}">Effective</label>
                    <input type="date" name="date_effective" value="{{ old('date_effective', $L->date_effective?->format('Y-m-d')) }}" class="form-input w-full @error('date_effective') border-danger @enderror">
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">Votes</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['votes_yes' => 'Yes', 'votes_no' => 'No', 'votes_abstain' => 'Abstain'] as $f => $label)
                    <div>
                        <input type="number" name="{{ $f }}" value="{{ old($f, $L->$f) }}" min="0" max="99" class="form-input w-full text-center" placeholder="—">
                        <p class="text-[11px] text-center text-gray-400 mt-0.5">{{ $label }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-4">
            <div>
                <label class="{{ $lbl }}">Amends</label>
                <select name="amends_id" class="form-select w-full">
                    <option value="">— Nothing —</option>
                    @foreach($linkable as $o)
                    <option value="{{ $o->id }}" @selected((int) old('amends_id', $L->amends_id) === $o->id)>{{ $o->typeLabel() }} {{ $o->number }} — {{ Str::limit($o->title, 50) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Repeals</label>
                @php $repealable = $linkable->filter(fn($o) => !$o->repealed_by_id || $o->repealed_by_id === $L->id || in_array($o->id, $oldRepeals, true)); @endphp
                @if($repealable->isEmpty())
                <p class="text-xs text-gray-400">No approved measures to repeal.</p>
                @else
                <div class="max-h-40 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($repealable as $o)
                    <label class="flex items-start gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <input type="checkbox" name="repeals[]" value="{{ $o->id }}" @checked(in_array($o->id, $oldRepeals, true)) class="form-checkbox mt-0.5 rounded text-primary">
                        <span class="text-gray-700 dark:text-gray-300"><span class="font-mono text-xs">{{ $o->number }}</span> {{ Str::limit($o->title, 60) }}</span>
                    </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-1">Ticked measures become Repealed once this one is approved.</p>
                @endif
            </div>
        </div>

        <div class="card p-6">
            <label class="{{ $lbl }}">Signed copy <span class="text-xs font-normal text-gray-400 ml-1">PDF or photo, max 20 MB</span></label>
            @if($L->file_path)
            <div class="flex items-center justify-between gap-2 mb-3 p-2.5 rounded-md bg-gray-50 dark:bg-gray-700/50">
                <a href="{{ route('legislation.file', $L) }}" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1.5">
                    <i class="mgc_pdf_line me-1"></i>Current file
                </a>
                <label class="flex items-center gap-1.5 text-xs text-danger cursor-pointer">
                    <input type="checkbox" name="remove_file" value="1" class="form-checkbox rounded text-danger"> Remove
                </label>
            </div>
            @endif
            <input type="file" name="file" accept=".pdf,image/jpeg,image/png" class="form-input w-full text-sm @error('file') border-danger @enderror">
            @if($L->file_path)<p class="text-xs text-gray-400 mt-1">Choosing a file replaces the current one.</p>@endif
            @error('file')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<template id="author-row-tpl">
    <div class="author-row flex gap-2">
        <input type="text" data-name="name" list="officials-list" class="form-input flex-1" placeholder="Hon. Juan Dela Cruz">
        <select data-name="role" class="form-select w-36">
            @foreach(Legislation::AUTHOR_ROLES as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
        </select>
        <button type="button" onclick="this.closest('.author-row').remove()" class="btn btn-sm bg-danger/10 text-danger hover:bg-danger hover:text-white" title="Remove">
            <i class="mgc_close_line"></i>
        </button>
    </div>
</template>

@push('inline-scripts')
<script>
// Category: "+ Add new category…" swaps the dropdown for a text box (only one of the
// two is enabled, so only one "category" value is submitted).
(function () {
    const sel = document.getElementById('category-select');
    const wrap = document.getElementById('category-new-wrap');
    const input = document.getElementById('category-new');
    if (!sel) return;
    sel.addEventListener('change', () => {
        if (sel.value !== '__new') return;
        sel.disabled = true;
        input.disabled = false;
        wrap.classList.remove('hidden');
        input.focus();
    });
    document.getElementById('category-new-cancel').addEventListener('click', () => {
        input.value = '';
        input.disabled = true;
        wrap.classList.add('hidden');
        sel.disabled = false;
        sel.value = '';
    });
})();
let authorSeq = {{ count($authorRows) }};
function addAuthorRow() {
    const node = document.getElementById('author-row-tpl').content.firstElementChild.cloneNode(true);
    const i = authorSeq++;
    node.querySelector('[data-name=name]').name = `authors[${i}][name]`;
    const role = node.querySelector('[data-name=role]');
    role.name = `authors[${i}][role]`;
    // after the first row, default new rows to co-author
    if (document.querySelectorAll('#author-rows .author-row').length) role.value = 'co_author';
    document.getElementById('author-rows').appendChild(node);
    node.querySelector('input').focus();
}

@if($isNew)
// New measure: follow the suggested number when the type changes, unless it was typed by hand.
(function () {
    const next = @json($nextNumbers);
    const type = document.getElementById('leg-type');
    const num  = document.getElementById('leg-number');
    let auto = Object.values(next).includes(num.value);
    num.addEventListener('input', () => auto = false);
    type.addEventListener('change', () => { if (auto) num.value = next[type.value] || ''; });
})();
@endif
</script>
@endpush
