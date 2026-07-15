@extends('layouts.vertical', ['title' => 'Edit Blotter', 'sub_title' => 'Barangay', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 text-sm text-success font-medium flex gap-2">
    <i class="mgc_check_circle_line shrink-0 mt-0.5"></i> {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
        @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
    </ul>
</div>
@endif

<form action="{{ route('blotters.update', $blotter) }}" method="POST">
@csrf @method('PUT')

<div class="flex flex-col gap-5">

    {{-- ── Blotter Header ── --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('blotters.show', $blotter) }}" class="text-gray-400 hover:text-primary">
                    <i class="mgc_arrow_left_line text-lg"></i>
                </a>
                <h5 class="card-title">
                    <i class="mgc_edit_2_line me-2 text-primary"></i>Edit Blotter
                    <span class="ms-2 text-xs font-mono font-normal text-gray-400">{{ $blotter->blotter_no }}</span>
                </h5>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn bg-primary text-white text-sm">
                    <i class="mgc_save_line me-1"></i> Save Changes
                </button>
                <a href="{{ route('blotters.show', $blotter) }}"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm">
                    Cancel
                </a>
            </div>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Blotter No.</label>
                <input type="text" value="{{ $blotter->blotter_no }}" class="form-input bg-gray-50 dark:bg-gray-800 text-gray-500" readonly>
            </div>
            <div>
                <label class="form-label">Date Filed <span class="text-danger">*</span></label>
                <input type="date" name="filed_date" value="{{ old('filed_date', $blotter->filed_date->format('Y-m-d')) }}"
                    class="form-input @error('filed_date') border-danger @enderror" required>
                @error('filed_date') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                @php
                    $isCustomType  = $blotter->isCustomType();
                    $selectedType  = old('type', $isCustomType ? 'other' : $blotter->type);
                    $otherOldValue = old('type_other', $isCustomType ? $blotter->type : '');
                @endphp
                <label class="form-label">Nature / Type <span class="text-danger">*</span></label>
                <select name="type" id="type-select" class="form-select @error('type') border-danger @enderror" required onchange="toggleOtherType(this.value)">
                    @foreach(\App\Models\Blotter::TYPES as $val => $label)
                        <option value="{{ $val }}" {{ $selectedType === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div id="type-other-wrap" class="{{ $selectedType === 'other' ? '' : 'hidden' }} mt-2">
                    <input type="text" name="type_other" id="type-other-input"
                           value="{{ $otherOldValue }}"
                           placeholder="Specify type…"
                           class="form-input @error('type') border-danger @enderror"
                           {{ $selectedType === 'other' ? 'required' : '' }}>
                </div>
                @error('type') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Incident Date <span class="text-danger">*</span></label>
                <input type="date" name="incident_date" value="{{ old('incident_date', $blotter->incident_date->format('Y-m-d')) }}"
                    class="form-input @error('incident_date') border-danger @enderror" required>
                @error('incident_date') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Incident Time</label>
                <input type="time" name="incident_time" value="{{ old('incident_time', $blotter->incident_time) }}"
                    class="form-input @error('incident_time') border-danger @enderror">
                @error('incident_time') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Incident Location</label>
                <input type="text" name="incident_location" value="{{ old('incident_location', $blotter->incident_location) }}"
                    placeholder="e.g. Purok 3, near the barangay hall"
                    class="form-input @error('incident_location') border-danger @enderror">
                @error('incident_location') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ── Parties ── --}}
    @php
        $editComplainants = $blotter->complainants()->with('citizen')->get();
        $editRespondents  = $blotter->respondents()->with('citizen')->get();
        // Fall back to old single columns if no parties recorded yet
        if ($editComplainants->isEmpty()) {
            $editComplainants = collect([(object)[
                'citizen_id' => $blotter->complainant_citizen_id,
                'name'       => $blotter->complainant_name,
                'address'    => $blotter->complainant_address,
                'contact'    => $blotter->complainant_contact,
            ]]);
        }
        if ($editRespondents->isEmpty()) {
            $editRespondents = collect([(object)[
                'citizen_id' => $blotter->respondent_citizen_id,
                'name'       => $blotter->respondent_name,
                'address'    => $blotter->respondent_address,
                'contact'    => $blotter->respondent_contact,
            ]]);
        }
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @include('blotter.partials.party-card', ['role' => 'complainant', 'color' => 'success', 'label' => 'Complainant', 'parties' => $editComplainants])
        @include('blotter.partials.party-card', ['role' => 'respondent',  'color' => 'danger',  'label' => 'Respondent',  'parties' => $editRespondents])
    </div>

    {{-- ── Narrative ── --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title text-sm"><i class="mgc_document_line me-2 text-primary"></i>Incident Details</h5>
        </div>
        <div class="p-5 flex flex-col gap-4">
            <div>
                <label class="form-label">Narrative <span class="text-danger">*</span></label>
                <input type="hidden" name="narrative" id="narrative-input" value="{{ old('narrative', $blotter->narrative) }}">
                <div id="narrative-editor" class="quill-editor-wrap"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Witnesses</label>
                    <textarea name="witnesses" rows="3"
                        placeholder="Names of witnesses, one per line"
                        class="form-input @error('witnesses') border-danger @enderror">{{ old('witnesses', $blotter->witnesses) }}</textarea>
                    @error('witnesses') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="form-label">Attending Officer</label>
                        <input type="text" name="attending_officer" value="{{ old('attending_officer', $blotter->attending_officer) }}"
                            placeholder="Name of barangay officer on duty"
                            class="form-input @error('attending_officer') border-danger @enderror">
                        @error('attending_officer') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" rows="2"
                            placeholder="Additional notes…"
                            class="form-input @error('remarks') border-danger @enderror">{{ old('remarks', $blotter->remarks) }}</textarea>
                        @error('remarks') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
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
html[data-mode=dark] .quill-editor-wrap .ql-editor{min-height:160px;color:#1e293b!important;background:#ffffff!important;}
html .quill-editor-wrap .ql-editor.ql-blank::before,
html[data-mode=dark] .quill-editor-wrap .ql-editor.ql-blank::before{color:#94a3b8!important;font-style:normal;}
html .quill-editor-wrap .ql-toolbar .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-stroke{stroke:#475569!important;}
html .quill-editor-wrap .ql-toolbar .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-fill{fill:#475569!important;}
html .quill-editor-wrap .ql-toolbar button:hover .ql-stroke,
html .quill-editor-wrap .ql-toolbar button.ql-active .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button:hover .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button.ql-active .ql-stroke{stroke:#4361ee!important;}
html .quill-editor-wrap .ql-toolbar button:hover .ql-fill,
html .quill-editor-wrap .ql-toolbar button.ql-active .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button:hover .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button.ql-active .ql-fill{fill:#4361ee!important;}
html .quill-editor-wrap .ql-toolbar .ql-picker-label,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-label{color:#475569!important;}
html .quill-editor-wrap .ql-toolbar .ql-picker-options,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-options{background:#ffffff!important;border-color:#e2e8f0!important;color:#1e293b!important;}
html .quill-editor-wrap .ql-toolbar .ql-picker-item,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-item{color:#1e293b!important;}
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

document.addEventListener('DOMContentLoaded', function () {
    const narrativeQuill = new Quill('#narrative-editor', {
        theme: 'snow',
        placeholder: 'Detailed account of the incident…',
        modules: { toolbar: QUILL_TOOLBAR },
    });

    // Pre-fill with existing value
    const existing = document.getElementById('narrative-input').value;
    if (existing) {
        narrativeQuill.root.innerHTML = existing;
    }

    // Sync to hidden input on form submit
    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('narrative-input').value = narrativeQuill.root.innerHTML;
    });
});
</script>
@endsection
