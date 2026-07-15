@extends('layouts.vertical', [
    'title'         => 'File Blotter',
    'sub_title'     => 'Blotter',
    'sub_title_url' => route('blotters.index'),
    'tagline'       => 'Create a new barangay blotter entry',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<form id="blotter-create-form" action="{{ route('blotters.store') }}" method="POST" enctype="multipart/form-data">
@csrf

{{-- ── Sticky Page Header ── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('blotters.index') }}"
           class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary/40 transition">
            <i class="mgc_arrow_left_line text-sm"></i>
        </a>
        <div>
            <h4 class="text-base font-bold text-gray-800 dark:text-gray-100">New Blotter Entry</h4>
            <p class="text-xs text-gray-400 mt-0.5">Fill in all required fields then click <strong>File Blotter</strong></p>
        </div>
    </div>
    <div class="flex gap-2">
        <button type="submit" id="submit-btn" class="btn bg-primary text-white">
            <i class="mgc_save_line me-1.5" id="submit-icon"></i>
            <span id="submit-label">File Blotter</span>
        </button>
        <a href="{{ route('blotters.index') }}"
           class="btn bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20">
            Cancel
        </a>
    </div>
</div>

{{-- ── Validation errors ── --}}
<div id="form-errors" class="hidden mb-5 p-4 rounded-xl bg-danger/10 border border-danger/20 flex gap-3">
    <i class="mgc_alert_line text-danger text-lg shrink-0 mt-0.5"></i>
    <ul id="form-errors-list" class="list-disc list-inside text-sm text-danger/90 space-y-0.5 flex-1"></ul>
</div>

<div class="flex flex-col gap-5">

    {{-- ══ Section 1 — Incident Info ══ --}}
    <div class="card overflow-hidden">

        {{-- Section label strip --}}
        <div class="flex items-center gap-3 px-5 py-3.5 bg-primary/5 border-b border-primary/10">
            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
            <h5 class="text-sm font-semibold text-primary tracking-wide">Incident Information</h5>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                {{-- Blotter No --}}
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">Blotter No.</label>
                    <div class="form-input bg-gray-50 dark:bg-gray-800 text-gray-400 font-mono text-sm flex items-center gap-2 cursor-not-allowed select-none">
                        <i class="mgc_document_2_line text-gray-300 shrink-0"></i>
                        {{ $blotterNo }}
                    </div>
                </div>

                {{-- Date Filed --}}
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">
                        Date Filed <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="filed_date"
                           value="{{ old('filed_date', date('Y-m-d')) }}"
                           class="form-input @error('filed_date') border-danger @enderror" required>
                    @error('filed_date') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Nature / Type --}}
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">
                        Nature / Type <span class="text-danger">*</span>
                    </label>
                    <select name="type" id="type-select"
                            class="form-select @error('type') border-danger @enderror"
                            required onchange="toggleOtherType(this.value)">
                        @foreach(\App\Models\Blotter::TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="type-other-wrap" class="{{ old('type') === 'other' ? '' : 'hidden' }} mt-2">
                        <input type="text" name="type_other" id="type-other-input"
                               value="{{ old('type_other') }}"
                               placeholder="Specify type…"
                               class="form-input @error('type') border-danger @enderror"
                               {{ old('type') === 'other' ? 'required' : '' }}>
                    </div>
                    @error('type') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Incident Date --}}
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">
                        Incident Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="incident_date"
                           value="{{ old('incident_date', date('Y-m-d')) }}"
                           class="form-input @error('incident_date') border-danger @enderror" required>
                    @error('incident_date') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Incident Time --}}
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">Incident Time</label>
                    <input type="time" name="incident_time"
                           value="{{ old('incident_time') }}"
                           class="form-input @error('incident_time') border-danger @enderror">
                    @error('incident_time') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Location --}}
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">Incident Location</label>
                    <input type="text" name="incident_location"
                           value="{{ old('incident_location') }}"
                           placeholder="e.g. Purok 3, near barangay hall"
                           class="form-input @error('incident_location') border-danger @enderror">
                    @error('incident_location') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ══ Section 2 — Parties ══ --}}
    <div class="card overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3.5 bg-primary/5 border-b border-primary/10">
            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
            <h5 class="text-sm font-semibold text-primary tracking-wide">Parties Involved</h5>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 dark:divide-gray-700">

            {{-- Complainant column --}}
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
                            <i class="mgc_user_heart_line text-success text-sm"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Complainant(s)</p>
                            <p class="text-[11px] text-gray-400">Person(s) who filed the complaint</p>
                        </div>
                    </div>
                    <button type="button" onclick="addPartyRow('complainant')"
                            class="btn btn-sm bg-success/10 text-success hover:bg-success/20 gap-1">
                        <i class="mgc_add_line text-xs"></i> Add
                    </button>
                </div>
                <div id="complainant-rows" class="flex flex-col gap-3">
                    @include('blotter.partials.party-rows', ['role' => 'complainant', 'color' => 'success', 'parties' => []])
                </div>
            </div>

            {{-- Respondent column --}}
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-danger/10 flex items-center justify-center shrink-0">
                            <i class="mgc_user_warning_line text-danger text-sm"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Respondent(s)</p>
                            <p class="text-[11px] text-gray-400">Person(s) being complained against</p>
                        </div>
                    </div>
                    <button type="button" onclick="addPartyRow('respondent')"
                            class="btn btn-sm bg-danger/10 text-danger hover:bg-danger/20 gap-1">
                        <i class="mgc_add_line text-xs"></i> Add
                    </button>
                </div>
                <div id="respondent-rows" class="flex flex-col gap-3">
                    @include('blotter.partials.party-rows', ['role' => 'respondent', 'color' => 'danger', 'parties' => []])
                </div>
            </div>

        </div>
    </div>

    {{-- ══ Section 3 — Narrative & Details ══ --}}
    <div class="card overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3.5 bg-primary/5 border-b border-primary/10">
            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
            <h5 class="text-sm font-semibold text-primary tracking-wide">Narrative & Details</h5>
        </div>

        <div class="p-5 flex flex-col gap-5">

            {{-- Narrative --}}
            <div>
                <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">
                    Narrative <span class="text-danger">*</span>
                </label>
                <input type="hidden" name="narrative" id="narrative-input">
                <div id="narrative-editor" class="quill-editor-wrap"></div>
                <p id="narrative-error" class="text-danger text-xs mt-1 hidden">
                    <i class="mgc_alert_line me-1"></i>Narrative is required.
                </p>
            </div>

            {{-- Witnesses + Officer + Remarks --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">Witnesses</label>
                    <textarea name="witnesses" rows="4"
                        placeholder="Names of witnesses, one per line"
                        class="form-input w-full @error('witnesses') border-danger @enderror">{{ old('witnesses') }}</textarea>
                    @error('witnesses') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">Attending Officer</label>
                    <input type="text" name="attending_officer"
                           value="{{ old('attending_officer') }}"
                           placeholder="Officer's full name"
                           class="form-input @error('attending_officer') border-danger @enderror">
                    @error('attending_officer') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-sm font-medium inline-block mb-2">Remarks</label>
                    <textarea name="remarks" rows="4"
                        placeholder="Additional notes…"
                        class="form-input w-full @error('remarks') border-danger @enderror">{{ old('remarks') }}</textarea>
                    @error('remarks') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

        </div>
    </div>

    {{-- ══ Section 4 — Photos ══ --}}
    <div class="card overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3.5 bg-primary/5 border-b border-primary/10">
            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">4</span>
            <h5 class="text-sm font-semibold text-primary tracking-wide">Photos</h5>
            <span class="text-xs text-gray-400 font-normal">(optional)</span>
        </div>

        <div class="p-5">
            <input type="file" id="create-photos-real" name="photos[]" multiple accept="image/*" class="hidden">

            {{-- Drop zone --}}
            <div id="create-photos-zone"
                 onclick="document.getElementById('create-photos-real').click()"
                 class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-colors group">
                <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 group-hover:bg-primary/10 transition-colors">
                    <i class="mgc_upload_2_line text-2xl text-gray-400 group-hover:text-primary transition-colors"></i>
                </div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Click to attach photos</p>
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — you can add more in multiple rounds</p>
            </div>

            {{-- Count indicator --}}
            <div id="create-photos-count" class="hidden mt-3 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400" id="create-photos-count-text"></span>
                <button type="button"
                        onclick="document.getElementById('create-photos-real').click()"
                        class="btn btn-sm bg-primary/10 text-primary hover:bg-primary/20 gap-1">
                    <i class="mgc_add_line text-xs"></i> Add more
                </button>
            </div>

            {{-- Preview grid --}}
            <div id="create-photos-preview" class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3 mt-3"></div>
        </div>
    </div>

    {{-- ══ Bottom action bar ══ --}}
    <div class="flex items-center justify-between gap-3 py-2">
        <p class="text-xs text-gray-400"><span class="text-danger">*</span> Required fields</p>
        <div class="flex gap-2">
            <button type="submit" id="submit-btn-bottom" class="btn bg-primary text-white" onclick="document.getElementById('submit-btn').click(); return false;">
                <i class="mgc_save_line me-1.5"></i> File Blotter
            </button>
            <a href="{{ route('blotters.index') }}"
               class="btn bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20">
                Cancel
            </a>
        </div>
    </div>

</div>
</form>

@endsection

@section('script')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
html .quill-editor-wrap .ql-toolbar,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar {
    background:#ffffff!important;border-color:#e2e8f0!important;border-radius:.5rem .5rem 0 0;
}
html .quill-editor-wrap .ql-container,
html[data-mode=dark] .quill-editor-wrap .ql-container {
    background:#ffffff!important;border-color:#e2e8f0!important;border-radius:0 0 .5rem .5rem;
    min-height:180px;font-size:14px;color:#1e293b!important;
}
html .quill-editor-wrap .ql-editor,
html[data-mode=dark] .quill-editor-wrap .ql-editor { min-height:160px;color:#1e293b!important;background:#ffffff!important; }
html .quill-editor-wrap .ql-editor.ql-blank::before,
html[data-mode=dark] .quill-editor-wrap .ql-editor.ql-blank::before { color:#94a3b8!important;font-style:normal; }
html .quill-editor-wrap .ql-toolbar .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-stroke { stroke:#475569!important; }
html .quill-editor-wrap .ql-toolbar .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-fill { fill:#475569!important; }
html .quill-editor-wrap .ql-toolbar button:hover .ql-stroke,
html .quill-editor-wrap .ql-toolbar button.ql-active .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button:hover .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button.ql-active .ql-stroke { stroke:#4361ee!important; }
html .quill-editor-wrap .ql-toolbar button:hover .ql-fill,
html .quill-editor-wrap .ql-toolbar button.ql-active .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button:hover .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button.ql-active .ql-fill { fill:#4361ee!important; }
html .quill-editor-wrap .ql-toolbar .ql-picker-label,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-label { color:#475569!important; }
html .quill-editor-wrap .ql-toolbar .ql-picker-options,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-options { background:#ffffff!important;border-color:#e2e8f0!important;color:#1e293b!important; }
html .quill-editor-wrap .ql-toolbar .ql-picker-item,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-item { color:#1e293b!important; }
#create-photos-zone { min-height: 108px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
</style>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
@include('blotter.partials.party-js')
<script>
const QUILL_TOOLBAR = [
    [{ size: ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline'],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['clean'],
];

let narrativeQuill;
const createPhotoFiles = new DataTransfer();

document.addEventListener('DOMContentLoaded', function () {
    narrativeQuill = new Quill('#narrative-editor', {
        theme: 'snow',
        placeholder: 'Detailed account of the incident as reported by the complainant…',
        modules: { toolbar: QUILL_TOOLBAR },
    });

    const realInput = document.getElementById('create-photos-real');
    const preview   = document.getElementById('create-photos-preview');
    const countEl   = document.getElementById('create-photos-count');

    realInput.addEventListener('change', function () {
        Array.from(this.files).forEach(file => {
            const exists = Array.from(createPhotoFiles.files)
                .some(f => f.name === file.name && f.size === file.size);
            if (exists) return;
            createPhotoFiles.items.add(file);

            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'relative group aspect-square';
                div.dataset.filename = file.name;
                div.dataset.filesize  = file.size;
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="removeCreatePhoto(this)"
                            class="absolute top-1 right-1 w-5 h-5 rounded-full bg-danger text-white text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow">
                        <i class="mgc_close_line"></i>
                    </button>`;
                preview.appendChild(div);
                updatePhotoCount();
            };
            reader.readAsDataURL(file);
        });

        realInput.files = createPhotoFiles.files;
        this.value = '';
    });
});

function updatePhotoCount() {
    const n       = createPhotoFiles.files.length;
    const bar     = document.getElementById('create-photos-count');
    const countTx = document.getElementById('create-photos-count-text');
    const zone    = document.getElementById('create-photos-zone');
    if (n > 0) {
        countTx.textContent = `${n} photo${n > 1 ? 's' : ''} attached`;
        bar.classList.remove('hidden');
        zone.classList.add('hidden');
    } else {
        bar.classList.add('hidden');
        zone.classList.remove('hidden');
    }
}

function removeCreatePhoto(btn) {
    const div   = btn.closest('[data-filename]');
    const name  = div.dataset.filename;
    const size  = parseInt(div.dataset.filesize);
    const newDt = new DataTransfer();
    Array.from(createPhotoFiles.files).forEach(f => {
        if (!(f.name === name && f.size === size)) newDt.items.add(f);
    });
    createPhotoFiles.items.clear();
    Array.from(newDt.files).forEach(f => createPhotoFiles.items.add(f));
    document.getElementById('create-photos-real').files = createPhotoFiles.files;
    div.remove();
    updatePhotoCount();
}

document.getElementById('blotter-create-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const narrativeHtml = narrativeQuill.root.innerHTML;
    const narrativeText = narrativeQuill.getText().trim();
    document.getElementById('narrative-input').value = narrativeHtml;

    const narrativeErr = document.getElementById('narrative-error');
    if (!narrativeText) {
        narrativeErr.classList.remove('hidden');
        narrativeQuill.root.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    narrativeErr.classList.add('hidden');

    const form    = this;
    const btn     = document.getElementById('submit-btn');
    const icon    = document.getElementById('submit-icon');
    const label   = document.getElementById('submit-label');
    const errBox  = document.getElementById('form-errors');
    const errList = document.getElementById('form-errors-list');

    btn.disabled      = true;
    icon.className    = 'mgc_loading_4_line me-1.5 animate-spin';
    label.textContent = 'Filing…';
    errBox.classList.add('hidden');
    errList.innerHTML = '';

    fetch(form.action, {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body:    new FormData(form),
    })
    .then(async res => {
        const json = await res.json();
        if (res.ok && json.success) {
            Swal.fire({
                icon: 'success', title: 'Blotter Filed!', text: json.message,
                timer: 1800, showConfirmButton: false,
            }).then(() => { window.location.href = json.redirect_url; });
            return;
        }
        if (res.status === 422 && json.errors) {
            const msgs = Object.values(json.errors).flat();
            errList.innerHTML = msgs.map(m => `<li>${m}</li>`).join('');
            errBox.classList.remove('hidden');
            errBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: json.message || 'Something went wrong.' });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please try again.' });
    })
    .finally(() => {
        btn.disabled      = false;
        icon.className    = 'mgc_save_line me-1.5';
        label.textContent = 'File Blotter';
    });
});
</script>
@endsection
