@extends('layouts.vertical', ['title' => ($type->exists ? 'Edit' : 'Create') . ' Document Type', 'sub_title' => 'Documents', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
<style>
#editor-wrapper {
    background: #e5e7eb;
    border-top: 1px solid #e5e7eb;
}
html[data-mode="dark"] #editor-wrapper { background: #262f3d; border-top-color: #374151; }

/* Paper sheet */
.note-editable {
    width: 8.5in !important;
    min-height: 11in !important;
    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
    margin: 0 auto !important;
    box-shadow: 0 0 12px rgba(0,0,0,0.15) !important;
    font-family: "Times New Roman", Times, serif !important;
    font-size: 14px !important;
    line-height: 1.8 !important;
    color: #111827 !important;
    box-sizing: border-box !important;
    background-color: #fff !important;
}
.note-editing-area {
    height: auto !important;
    overflow: visible !important;
}
.note-codable {
    height: auto !important;
    min-height: 600px !important;
}

/* Editor area background */
.note-editing-area { background: #e5e7eb !important; padding: 24px !important; }
html[data-mode="dark"] .note-editing-area { background: #262f3d !important; }

/* Toolbar — flush against the card edges, pinned below the app topbar (70px) while scrolling */
.note-toolbar {
    background: #f9fafb !important;
    border-bottom: 1px solid #e5e7eb !important;
    border-radius: 0 !important;
    position: sticky !important;
    top: 70px !important;
    z-index: 20 !important;
}
html[data-mode="dark"] .note-toolbar { background: #1e2530 !important; border-bottom: 1px solid #374151 !important; }

.note-statusbar { display: none !important; }
.note-editor.note-frame { border: none !important; border-radius: 0 !important; }

.note-editor.note-fullscreen .note-editable {
    width: 8.5in !important;
    min-height: 11in !important;
    margin: 0 auto !important;
    overflow: visible !important;
}

/* Table styles inside editor */
.note-editable table { border-collapse: collapse; width: 100%; margin: 8px 0; }
.note-editable td, .note-editable th { border: 1px solid #d1d5db; padding: 6px 10px; vertical-align: top; }
.note-editable th { background: #f3f4f6; font-weight: 600; }

/* Toolbar buttons/dropdowns in dark mode */
html[data-mode="dark"] .note-toolbar .note-btn {
    background: #2a3441 !important;
    border-color: #3f4a5a !important;
    color: #d1d5db !important;
}
html[data-mode="dark"] .note-toolbar .note-btn:hover {
    background: #354152 !important;
}
html[data-mode="dark"] .note-toolbar .note-icon-caret { border-top-color: #d1d5db !important; }
html[data-mode="dark"] .note-dropdown-menu {
    background: #2a3441 !important;
    border-color: #3f4a5a !important;
}
html[data-mode="dark"] .note-dropdown-menu .note-dropdown-item {
    color: #d1d5db !important;
}
html[data-mode="dark"] .note-dropdown-menu .note-dropdown-item:hover {
    background: #354152 !important;
}
html[data-mode="dark"] .note-toolbar .note-color-select-btn { border-color: #3f4a5a !important; }

/* Custom field grid span in the preview modal, set inline via --field-col-span (1-12) */
@media (min-width: 1024px) {
    #custom-fields-preview-body > [style*="--field-col-span"] {
        grid-column: span var(--field-col-span) / span var(--field-col-span);
    }
}
</style>
@endsection

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<div class="flex items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('documents.types.index') }}" class="text-gray-400 hover:text-primary">
            <i class="mgc_arrow_left_line text-lg"></i>
        </a>
        <div>
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                {{ $type->exists ? 'Edit: ' . $type->name : 'New Document Type' }}
            </h4>
            <p class="text-sm text-gray-400">Configure the template, fields, and fee settings.</p>
        </div>
    </div>
    <button type="button" onclick="openPrintPreview()"
            class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-1.5 shrink-0">
        <i class="mgc_print_line"></i> Print Preview
    </button>
</div>

<form action="{{ $type->exists ? route('documents.types.update', $type) : route('documents.types.store') }}"
      method="POST" id="type-form">
    @csrf
    @if($type->exists) @method('PUT') @endif

    <div class="grid grid-cols-12 gap-6">

        {{-- ── Left: Settings ── --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-5">

            {{-- Basic Info --}}
            <div class="card p-5 space-y-4">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_document_2_line text-primary"></i> Basic Info
                </h6>
                <div>
                    <label class="form-label text-sm">Document Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-input"
                           value="{{ old('name', $type->name) }}"
                           placeholder="e.g. Barangay Clearance" required maxlength="255">
                </div>
                <div>
                    <label class="form-label text-sm">Short Name</label>
                    <input type="text" name="short_name" class="form-input"
                           value="{{ old('short_name', $type->short_name) }}"
                           placeholder="e.g. Clearance" maxlength="100">
                </div>
                <div>
                    <label class="form-label text-sm">Description</label>
                    <textarea name="description" rows="2" class="form-input"
                              placeholder="Optional description…">{{ old('description', $type->description) }}</textarea>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="form-checkbox" {{ old('is_active', $type->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-gray-600 dark:text-gray-300">Active (visible for requests)</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="requires_approval" id="requires_approval" value="1"
                           class="form-checkbox" {{ old('requires_approval', $type->requires_approval ?? true) ? 'checked' : '' }}>
                    <label for="requires_approval" class="text-sm text-gray-600 dark:text-gray-300">Requires approval before release</label>
                </div>
            </div>

            {{-- Fee Settings --}}
            <div class="card p-5 space-y-4">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_wallet_3_line text-warning"></i> Fee Settings
                </h6>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_paid" id="is_paid" value="1"
                           class="form-checkbox"
                           {{ old('is_paid', $type->is_paid ?? false) ? 'checked' : '' }}
                           onchange="toggleFee(this)">
                    <label for="is_paid" class="text-sm text-gray-600 dark:text-gray-300 font-medium">This document has a fee</label>
                </div>
                <div id="fee-field" class="{{ old('is_paid', $type->is_paid ?? false) ? '' : 'hidden' }}">
                    <label class="form-label text-sm">Fee Amount (₱) <span class="text-danger">*</span></label>
                    <input type="number" name="fee" id="fee" class="form-input"
                           value="{{ old('fee', $type->fee ?? '') }}"
                           placeholder="0.00" min="0" step="0.01">
                    <p class="text-xs text-gray-400 mt-1">Official receipt number will be required upon release.</p>
                </div>
                <div id="fee-free-label" class="{{ old('is_paid', $type->is_paid ?? false) ? 'hidden' : '' }}">
                    <div class="flex items-center gap-2 text-success text-sm font-medium">
                        <i class="mgc_check_circle_line"></i> This document is FREE
                    </div>
                </div>
            </div>

            {{-- Placeholder Reference --}}
            <div class="card p-5" data-placeholder-panel>
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-3">
                    <i class="mgc_code_line text-info"></i> Available Placeholders
                </h6>
                <p class="text-xs text-gray-400 mb-3">Click to insert — into the certificate body, or into a focused "Default Value" field below.</p>
                <div class="space-y-3">

                    {{-- Citizen --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1.5">Citizen</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach([
                                'fullname'      => 'Full Name',
                                'firstname'     => 'First Name',
                                'middlename'    => 'Middle Name',
                                'lastname'      => 'Last Name',
                                'suffix'        => 'Suffix',
                                'gender'        => 'Gender',
                                'civil_status'  => 'Civil Status',
                                'birthday'      => 'Birthday',
                                'birthplace'    => 'Birthplace',
                                'age'           => 'Age',
                                'address'       => 'Address',
                                'contact'       => 'Contact No.',
                                'email'         => 'Email',
                                'occupation'    => 'Occupation',
                                'year_stay'     => 'Year of Stay',
                                'qrcode'        => 'QR Code',
                                'profile_photo_link' => 'Profile Photo URL (use as img src="")',
                            ] as $tag => $label)
                            <button type="button" onclick="copyTag('{{ $tag }}')" title="{{ $label }}"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono bg-primary/10 text-primary hover:bg-primary/20 transition">
                                &#123;&#123; {{ $tag }} &#125;&#125;
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Date --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1.5">Date Issued</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach([
                                'date_day'    => 'Day (e.g. 15)',
                                'date_day_th' => 'Day ordinal (e.g. 15th)',
                                'date_month'  => 'Month (e.g. June)',
                                'date_year'   => 'Year (e.g. 2026)',
                                'date_full'   => 'Full date (June 15, 2026)',
                            ] as $tag => $label)
                            <button type="button" onclick="copyTag('{{ $tag }}')" title="{{ $label }}"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono bg-success/10 text-success hover:bg-success/20 transition">
                                &#123;&#123; {{ $tag }} &#125;&#125;
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Barangay / Request --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1.5">Barangay & Request</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach([
                                'brgy_name'   => 'Barangay Name',
                                'city'        => 'City/Municipality',
                                'province'    => 'Province',
                                'captain'     => 'Barangay Captain',
                                'issued_by'   => 'Issued By (current user)',
                                'or_number'   => 'O.R. Number',
                                'doc_number'  => 'Document Control No.',
                            ] as $tag => $label)
                            <button type="button" onclick="copyTag('{{ $tag }}')" title="{{ $label }}"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono bg-info/10 text-info hover:bg-info/20 transition">
                                &#123;&#123; {{ $tag }} &#125;&#125;
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Profile photo --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1.5">Profile Photo</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" onclick="copyTag('profile_photo [80,100]')" title="80×100 portrait"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 hover:bg-purple-200 transition">
                                &#123;&#123; profile_photo [80,100] &#125;&#125;
                            </button>
                            <button type="button" onclick="copyTag('profile_photo [120,120]')" title="120×120 square"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 hover:bg-purple-200 transition">
                                &#123;&#123; profile_photo [120,120] &#125;&#125;
                            </button>
                            <button type="button" onclick="copyTag('profile_photo_link')" title="URL only"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 hover:bg-purple-200 transition">
                                &#123;&#123; profile_photo_link &#125;&#125;
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Syntax: <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&#123;&#123; profile_photo [width,height] &#125;&#125;</code></p>
                    </div>

                    {{-- Custom fields --}}
                    <div id="custom-tags-ref">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1.5">Custom Fields</p>
                        <div id="custom-tag-list" class="flex flex-wrap gap-1.5">
                            <span class="text-xs text-gray-400 italic">Add fields below to see them here.</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Paper Template Selector --}}
            <div class="card p-5 space-y-3">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_paper_line text-primary"></i> Paper Template
                </h6>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Select a template and pin the exact version to use. New requests will always use the pinned version.
                    <a href="{{ route('documents.templates.index') }}" class="text-primary underline">Manage templates →</a>
                </p>

                @if($templates->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-5 text-center">
                    <i class="mgc_paper_line text-2xl text-gray-300 dark:text-gray-600 block mb-1"></i>
                    <p class="text-xs text-gray-400">No templates yet.</p>
                    <a href="{{ route('documents.templates.create') }}" class="text-xs text-primary underline mt-1 inline-block">Create one →</a>
                </div>
                @else

                {{-- None --}}
                <label class="cursor-pointer block">
                    <input type="radio" name="document_template_id" value=""
                           id="tpl_none" class="sr-only peer"
                           onchange="showVersions(null)"
                           {{ old('document_template_id', $type->document_template_id) === null ? 'checked' : '' }}>
                    <div class="flex items-center gap-3 p-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
                        <div class="w-10 h-14 rounded border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                            <i class="mgc_close_line text-gray-400 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">None</p>
                            <p class="text-[11px] text-gray-400">Auto-header from Barangay Settings</p>
                        </div>
                    </div>
                </label>

                {{-- Templates --}}
                @foreach($templates as $tpl)
                @php
                    $isSelected = (string) old('document_template_id', $type->document_template_id) === (string) $tpl->id;
                @endphp
                <label class="cursor-pointer block">
                    <input type="radio" name="document_template_id" value="{{ $tpl->id }}"
                           id="tpl_{{ $tpl->id }}" class="sr-only peer"
                           onchange="showVersions({{ $tpl->id }})"
                           {{ $isSelected ? 'checked' : '' }}>
                    <div class="flex items-center gap-3 p-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
                        <div class="w-10 h-14 rounded border border-gray-200 dark:border-gray-600 overflow-hidden shrink-0 bg-gray-100 dark:bg-gray-800">
                            @if($tpl->currentVersion?->paper_bg)
                            <img src="{{ asset('storage/' . $tpl->currentVersion->paper_bg) }}"
                                 alt="{{ $tpl->name }}" class="w-full h-full object-cover object-top">
                            @else
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="mgc_paper_line text-gray-300 text-xs"></i>
                            </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $tpl->name }}</p>
                            <p class="text-[11px] text-gray-400">
                                {{ strtoupper($tpl->currentVersion?->paper_size ?? 'A4') }} ·
                                {{ ucfirst($tpl->currentVersion?->orientation ?? 'portrait') }} ·
                                {{ $tpl->versions->count() }} version(s)
                            </p>
                        </div>
                    </div>
                </label>

                {{-- Version picker — shown when this template is selected --}}
                <div id="versions_{{ $tpl->id }}"
                     class="{{ $isSelected ? '' : 'hidden' }} ml-3 pl-3 border-l-2 border-primary/30 space-y-1.5">
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Pin a version</p>
                    @foreach($tpl->versions as $ver)
                    @php
                        $verSelected = (string) old('document_template_version_id', $type->document_template_version_id) === (string) $ver->id
                            || ($isSelected && $type->document_template_version_id === null && $ver->id === $tpl->current_version_id);
                    @endphp
                    <label class="cursor-pointer block">
                        <input type="radio" name="document_template_version_id" value="{{ $ver->id }}"
                               class="sr-only peer tpl-ver-radio" data-tpl="{{ $tpl->id }}"
                               {{ $verSelected ? 'checked' : '' }}>
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
                            {{-- Thumbnail --}}
                            <div class="w-8 h-10 rounded border border-gray-200 dark:border-gray-600 overflow-hidden shrink-0 bg-white">
                                @if($ver->paper_bg)
                                <img src="{{ asset('storage/' . $ver->paper_bg) }}"
                                     class="w-full h-full object-cover object-top" alt="">
                                @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="mgc_paper_line text-gray-300 text-[9px]"></i>
                                </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">v{{ $ver->version }}</span>
                                    @if($ver->id === $tpl->current_version_id)
                                    <span class="text-[9px] font-bold px-1 rounded bg-primary text-white">CURRENT</span>
                                    @endif
                                </div>
                                @if($ver->change_note)
                                <p class="text-[10px] text-gray-400 truncate">{{ $ver->change_note }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400">{{ $ver->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @endforeach

                {{-- Hidden version field for when None is selected --}}
                <input type="hidden" id="version_none_clear" name="document_template_version_id"
                       {{ old('document_template_id', $type->document_template_id) === null ? '' : 'disabled' }}>

                @endif
            </div>

        </div>

        {{-- ── Right: Fields + Template ── --}}
        <div class="col-span-12 lg:col-span-8 flex flex-col gap-5">

            {{-- Custom Fields --}}
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <i class="mgc_list_check_2_line text-purple-500"></i> Custom Fields
                        <span class="text-xs font-normal text-gray-400">(filled per request)</span>
                        <button type="button" data-fc-type="modal" data-fc-target="custom-fields-info-modal"
                                class="text-gray-400 hover:text-primary transition" title="What do these columns mean?">
                            <i class="mgc_information_line text-base"></i>
                        </button>
                    </h6>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openCustomFieldsPreview()"
                                class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3 flex items-center gap-1">
                            <i class="mgc_eye_2_line"></i> Preview
                        </button>
                        <button type="button" onclick="addField()"
                                class="btn border-purple-400 text-purple-600 dark:text-purple-400 text-sm py-1.5 px-3 hover:bg-purple-50 dark:hover:bg-purple-900/20 flex items-center gap-1">
                            <i class="mgc_add_line"></i> Add Field
                        </button>
                    </div>
                </div>

                <div id="fields-container" class="space-y-3">
                    @forelse($type->fields ?? [] as $i => $field)
                    <div class="field-row grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="col-span-12 sm:col-span-3">
                            <label class="form-label text-xs mb-1">Field Key <span class="text-danger">*</span></label>
                            <input type="text" name="fields[{{ $i }}][field_key]"
                                   class="form-input text-sm py-1.5 field-key-input"
                                   value="{{ $field->field_key }}" placeholder="e.g. purpose"
                                   oninput="refreshCustomTags()">
                            <p class="text-[10px] text-gray-400 mt-0.5">becomes &#123;&#123; key &#125;&#125;</p>
                        </div>
                        <div class="col-span-12 sm:col-span-3">
                            <label class="form-label text-xs mb-1">Label <span class="text-danger">*</span></label>
                            <input type="text" name="fields[{{ $i }}][field_label]"
                                   class="form-input text-sm py-1.5"
                                   value="{{ $field->field_label }}" placeholder="e.g. Purpose of Request">
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label class="form-label text-xs mb-1">Type</label>
                            <select name="fields[{{ $i }}][field_type]"
                                    class="form-select text-sm py-1.5 field-type-select"
                                    onchange="toggleOptions(this)">
                                <option value="text"     {{ $field->field_type === 'text'     ? 'selected' : '' }}>Text</option>
                                <option value="textarea" {{ $field->field_type === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                <option value="date"     {{ $field->field_type === 'date'     ? 'selected' : '' }}>Date</option>
                                <option value="select"   {{ $field->field_type === 'select'   ? 'selected' : '' }}>Select</option>
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label class="form-label text-xs mb-1">Column</label>
                            <select name="fields[{{ $i }}][column_width]" class="form-select text-sm py-1.5">
                                @for($w = 1; $w <= 12; $w++)
                                <option value="{{ $w }}" {{ (int) $field->column_width === $w ? 'selected' : '' }}>col-lg-{{ $w }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-1">
                            <label class="form-label text-xs mb-1">Required</label>
                            <div class="flex items-center h-9">
                                <input type="checkbox" name="fields[{{ $i }}][is_required]" value="1"
                                       class="form-checkbox" {{ $field->is_required ? 'checked' : '' }}>
                                <span class="ms-2 text-xs text-gray-500">Yes</span>
                            </div>
                        </div>
                        <div class="col-span-6 sm:col-span-1 flex items-end pb-1">
                            <button type="button" onclick="removeField(this)"
                                    class="btn border-danger/40 text-danger text-sm py-1.5 px-2 hover:bg-danger/10 w-full flex justify-center">
                                <i class="mgc_delete_2_line"></i>
                            </button>
                        </div>
                        <div class="col-span-12">
                            <label class="form-label text-xs mb-1">Default Value <span class="text-gray-400">(optional)</span></label>
                            <input type="text" name="fields[{{ $i }}][default_value]"
                                   class="form-input text-sm py-1.5 field-default-input"
                                   value="{{ $field->default_value }}" placeholder="Pre-filled value, or click a placeholder above (e.g. &#123;&#123; date_full &#125;&#125;)">
                            <p class="text-[10px] text-gray-400 mt-0.5">Placeholder tags are resolved when the citizen leaves this field blank.</p>
                        </div>
                        <div class="col-span-12 field-options-wrap {{ $field->field_type === 'select' ? '' : 'hidden' }}">
                            <label class="form-label text-xs mb-1">Options <span class="text-gray-400">(one per line)</span></label>
                            <textarea name="fields[{{ $i }}][field_options]" rows="3"
                                      class="form-input text-sm py-1.5"
                                      placeholder="Option 1&#10;Option 2&#10;Option 3">{{ is_array($field->field_options) ? implode("\n", $field->field_options) : '' }}</textarea>
                        </div>
                    </div>
                    @empty
                    <div id="fields-empty" class="text-center py-6 text-gray-400 text-sm">
                        <i class="mgc_list_check_2_line text-2xl block mb-1"></i>
                        No custom fields yet. Click "Add Field" to add one.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Template Body --}}
            <div class="card overflow-hidden">
                <div class="p-5 pb-3">
                    <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-1">
                        <i class="mgc_quill_pen_line text-primary"></i> Certificate Template
                    </h6>
                    <p class="text-xs text-gray-400">
                        Write the certificate body. Use <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&#123;&#123; placeholder &#125;&#125;</code> tags from the left panel — click a tag to insert it at the cursor.
                    </p>
                </div>

                <div id="editor-wrapper">
                    <textarea name="template_body" id="template_body">{{ old('template_body', $type->template_body) }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <a href="{{ route('documents.types.index') }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    Cancel
                </a>
                <button type="submit" class="btn bg-primary text-white px-8 flex items-center gap-2">
                    <i class="mgc_save_line"></i>
                    {{ $type->exists ? 'Save Changes' : 'Create Document Type' }}
                </button>
            </div>
        </div>

    </div>
</form>

{{-- Custom Fields Info Modal --}}
<div id="custom-fields-info-modal" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
    <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="mgc_information_line text-primary"></i> Custom Field Columns
                </h3>
                <button data-fc-dismiss="modal"><i class="mgc_close_line text-xl"></i></button>
            </div>
            <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Field Key</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Internal key used in the placeholder tag — e.g. <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">purpose</code> becomes
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">&#123;&#123; purpose &#125;&#125;</code> in the certificate body.
                        Letters, numbers, and underscores only — no spaces.
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Label</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        The question shown to staff on the request form, e.g. "Purpose of Request".
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Type</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        The kind of input shown on the request form: <strong>Text</strong> (single line),
                        <strong>Textarea</strong> (multi-line), <strong>Date</strong> (date picker), or
                        <strong>Select</strong> (dropdown with fixed Options).
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Column</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        How wide this field appears on the request form's grid (1–12).
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">col-lg-12</code> = full width,
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">col-lg-6</code> = half width, etc.
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Required</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        If checked, staff must fill this in before the request can be submitted.
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Default Value</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Optional pre-filled value. Can include placeholder tags (e.g. <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">&#123;&#123; date_full &#125;&#125;</code>) —
                        these resolve automatically when staff leave the field blank on the request form.
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Options</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Only shown for the <strong>Select</strong> type — one choice per line, shown as a dropdown on the request form.
                    </p>
                </div>

            </div>
            <div class="flex justify-end py-3 px-4 border-t dark:border-gray-700">
                <button data-fc-dismiss="modal" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Custom Fields Preview Modal --}}
<div id="custom-fields-preview-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60" onclick="if(event.target===this)closeCustomFieldsPreview()">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col w-full max-w-2xl mx-4" style="max-height:90vh;">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_eye_2_line text-primary"></i> Custom Fields Preview
                <span class="text-xs font-normal text-gray-400">— as seen on the request form</span>
            </h6>
            <button type="button" onclick="closeCustomFieldsPreview()"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-500 text-sm py-1.5 px-3">
                <i class="mgc_close_line"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-800/50 p-6">
            <div id="custom-fields-preview-body" class="grid grid-cols-12 gap-3"></div>
        </div>
    </div>
</div>

{{-- Print Preview Modal --}}
<div id="print-preview-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60" onclick="if(event.target===this)closePrintPreview()">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col w-full max-w-4xl mx-4" style="max-height:90vh;">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_print_line text-primary"></i> Print Preview
            </h6>
            <div class="flex items-center gap-2">
                <button type="button" onclick="printPreview()"
                        class="btn bg-primary text-white text-sm py-1.5 px-4 flex items-center gap-1.5">
                    <i class="mgc_print_line"></i> Print
                </button>
                <button type="button" onclick="closePrintPreview()"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-500 text-sm py-1.5 px-3">
                    <i class="mgc_close_line"></i>
                </button>
            </div>
        </div>
        <div class="overflow-y-auto flex-1 bg-gray-100 p-6">
            <iframe id="print-preview-frame"
                    style="width:8.5in; border:none; display:block; margin:0 auto; background:#fff; box-shadow:0 0 16px rgba(0,0,0,0.15);"
                    scrolling="no"
                    ></iframe>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Template version → paper settings map
const versionMap = {
    @foreach($templates as $tpl)
        @foreach($tpl->versions as $ver)
        {{ $ver->id }}: {
            bg:      '{{ $ver->paper_bg ? asset('storage/' . $ver->paper_bg) : '' }}',
            padTop:  {{ $ver->padding_top    ?? 50 }},
            padBot:  {{ $ver->padding_bottom ?? 20 }},
            padLeft: {{ $ver->padding_left   ?? 50 }},
            padRight:{{ $ver->padding_right  ?? 50 }},
        },
        @endforeach
    @endforeach
};

let fieldIndex = {{ $type->fields->count() ?? 0 }};

function getPaperSettings() {
    const checked = document.querySelector('.tpl-ver-radio:checked');
    if (checked && versionMap[checked.value]) return versionMap[checked.value];
    return { bg: '', padTop: 50, padBot: 20, padLeft: 50, padRight: 50 };
}

function applyPaperBg() {
    const v      = getPaperSettings();
    const editor = document.querySelector('.note-editable');
    if (!editor) return;

    editor.style.setProperty('background-image',    v.bg ? `url('${v.bg}')` : 'none',   'important');
    editor.style.setProperty('background-size',     '100% auto',                         'important');
    editor.style.setProperty('background-position', 'top center',                        'important');
    editor.style.setProperty('background-repeat',   'repeat-y',                          'important');
    editor.style.setProperty('padding-top',         v.padTop   + 'px',                   'important');
    editor.style.setProperty('padding-bottom',      v.padBot   + 'px',                   'important');
    editor.style.setProperty('padding-left',        v.padLeft  + 'px',                   'important');
    editor.style.setProperty('padding-right',       v.padRight + 'px',                   'important');

    // Keep the placeholder text aligned with the paper sheet (same box as .note-editable)
    const placeholder = document.querySelector('.note-placeholder');
    if (placeholder) {
        placeholder.style.setProperty('left',           editor.offsetLeft + 'px', 'important');
        placeholder.style.setProperty('top',             editor.offsetTop  + 'px', 'important');
        placeholder.style.setProperty('width',           editor.offsetWidth + 'px', 'important');
        placeholder.style.setProperty('padding-top',    v.padTop   + 'px', 'important');
        placeholder.style.setProperty('padding-bottom', v.padBot   + 'px', 'important');
        placeholder.style.setProperty('padding-left',   v.padLeft  + 'px', 'important');
        placeholder.style.setProperty('padding-right',  v.padRight + 'px', 'important');
        placeholder.style.setProperty('box-sizing',     'border-box', 'important');
    }
}

function showVersions(tplId) {
    document.querySelectorAll('[id^="versions_"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tpl-ver-radio').forEach(r => r.disabled = true);
    if (tplId) {
        const panel = document.getElementById('versions_' + tplId);
        if (panel) {
            panel.classList.remove('hidden');
            panel.querySelectorAll('.tpl-ver-radio').forEach(r => r.disabled = false);
            if (!panel.querySelector('.tpl-ver-radio:checked')) {
                const first = panel.querySelector('.tpl-ver-radio');
                if (first) first.checked = true;
            }
        }
    }
    applyPaperBg();
}

var $sn = null; // set after Summernote loads
var lastFocusedDefaultInput = null; // tracks the last-focused "Default Value" input, if any

// Track focus on any custom field "Default Value" input so placeholder
// tag clicks know whether to insert into it instead of the certificate body.
// Clicking a placeholder button also fires focusin (buttons are focusable),
// so ignore focus changes caused by the placeholder panel itself — otherwise
// the click would wipe lastFocusedDefaultInput before copyTag() reads it.
document.addEventListener('focusin', function (e) {
    if (e.target.matches('.field-default-input')) {
        lastFocusedDefaultInput = e.target;
    } else if (!e.target.closest('#print-preview-modal') && !e.target.closest('[data-placeholder-panel]')) {
        lastFocusedDefaultInput = null;
    }
});

function copyTag(key) {
    const open  = '\x7B\x7B';
    const close = '\x7D\x7D';
    const tag   = open + ' ' + key + ' ' + close;

    if (lastFocusedDefaultInput) {
        const input = lastFocusedDefaultInput;
        const start = input.selectionStart ?? input.value.length;
        const end   = input.selectionEnd ?? input.value.length;
        input.value = input.value.slice(0, start) + tag + input.value.slice(end);
        input.focus();
        input.setSelectionRange(start + tag.length, start + tag.length);
        return;
    }

    if ($sn) $sn('#template_body').summernote('insertText', tag);
}

function addField() {
    const empty = document.getElementById('fields-empty');
    if (empty) empty.remove();
    const i   = fieldIndex++;
    const div = document.createElement('div');
    div.className = 'field-row grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700';

    let columnOptions = '';
    for (let w = 1; w <= 12; w++) {
        columnOptions += `<option value="${w}" ${w === 12 ? 'selected' : ''}>col-lg-${w}</option>`;
    }

    div.innerHTML = `
        <div class="col-span-12 sm:col-span-3">
            <label class="form-label text-xs mb-1">Field Key <span class="text-danger">*</span></label>
            <input type="text" name="fields[${i}][field_key]"
                   class="form-input text-sm py-1.5 field-key-input"
                   placeholder="e.g. purpose" oninput="refreshCustomTags()">
            <p class="text-[10px] text-gray-400 mt-0.5">becomes &#123;&#123; key &#125;&#125;</p>
        </div>
        <div class="col-span-12 sm:col-span-3">
            <label class="form-label text-xs mb-1">Label <span class="text-danger">*</span></label>
            <input type="text" name="fields[${i}][field_label]"
                   class="form-input text-sm py-1.5" placeholder="e.g. Purpose of Request">
        </div>
        <div class="col-span-6 sm:col-span-2">
            <label class="form-label text-xs mb-1">Type</label>
            <select name="fields[${i}][field_type]"
                    class="form-select text-sm py-1.5 field-type-select"
                    onchange="toggleOptions(this)">
                <option value="text">Text</option>
                <option value="textarea">Textarea</option>
                <option value="date">Date</option>
                <option value="select">Select</option>
            </select>
        </div>
        <div class="col-span-6 sm:col-span-2">
            <label class="form-label text-xs mb-1">Column</label>
            <select name="fields[${i}][column_width]" class="form-select text-sm py-1.5">
                ${columnOptions}
            </select>
        </div>
        <div class="col-span-6 sm:col-span-1">
            <label class="form-label text-xs mb-1">Required</label>
            <div class="flex items-center h-9">
                <input type="checkbox" name="fields[${i}][is_required]" value="1" class="form-checkbox" checked>
                <span class="ms-2 text-xs text-gray-500">Yes</span>
            </div>
        </div>
        <div class="col-span-6 sm:col-span-1 flex items-end pb-1">
            <button type="button" onclick="removeField(this)"
                    class="btn border-danger/40 text-danger text-sm py-1.5 px-2 hover:bg-danger/10 w-full flex justify-center">
                <i class="mgc_delete_2_line"></i>
            </button>
        </div>
        <div class="col-span-12">
            <label class="form-label text-xs mb-1">Default Value <span class="text-gray-400">(optional)</span></label>
            <input type="text" name="fields[${i}][default_value]"
                   class="form-input text-sm py-1.5 field-default-input" placeholder="Pre-filled value, or click a placeholder above">
            <p class="text-[10px] text-gray-400 mt-0.5">Placeholder tags are resolved when the citizen leaves this field blank.</p>
        </div>
        <div class="col-span-12 field-options-wrap hidden">
            <label class="form-label text-xs mb-1">Options <span class="text-gray-400">(one per line)</span></label>
            <textarea name="fields[${i}][field_options]" rows="3"
                      class="form-input text-sm py-1.5"
                      placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
        </div>
    `;
    document.getElementById('fields-container').appendChild(div);
}

function removeField(btn) {
    btn.closest('.field-row').remove();
    refreshCustomTags();
}

function toggleOptions(select) {
    const wrap = select.closest('.field-row').querySelector('.field-options-wrap');
    wrap.classList.toggle('hidden', select.value !== 'select');
}

function toggleFee(cb) {
    document.getElementById('fee-field').classList.toggle('hidden', !cb.checked);
    document.getElementById('fee-free-label').classList.toggle('hidden', cb.checked);
    if (!cb.checked) document.getElementById('fee').value = '';
}

function openPrintPreview() {
    const html = $sn ? $sn('#template_body').summernote('code') : document.getElementById('template_body').value;

    // Resolve the selected version's paper background
    const checked = document.querySelector('.tpl-ver-radio:checked');
    const v       = (checked && versionMap[checked.value]) ? versionMap[checked.value] : { bg:'', padTop:50, padBot:20, padLeft:50, padRight:50 };
    const bgStyle = v.bg ? `background-image:url('${v.bg}');background-size:cover;background-position:top center;background-repeat:no-repeat;` : 'background:#fff;';

    const frame = document.getElementById('print-preview-frame');
    const doc   = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                width: 8.5in; min-height: 11in;
                padding: ${v.padTop}px ${v.padRight}px ${v.padBot}px ${v.padLeft}px;
                font-family: "Times New Roman", Times, serif;
                font-size: 14px; line-height: 1.8; color: #111827;
                ${bgStyle}
            }
            table { border-collapse: collapse; width: 100%; margin: 8px 0; }
            td, th { border: 1px solid #d1d5db; padding: 6px 10px; vertical-align: top; }
            th { background: #f3f4f6; font-weight: 600; }
            img { max-width: 100%; }
            @media print {
                body { margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            }
        </style>
    </head><body>${html}</body></html>`);
    doc.close();

    // Resize iframe to full content height so no internal scrollbar appears
    frame.onload = function () {
        frame.style.height = frame.contentDocument.body.scrollHeight + 'px';
    };
    // Fallback for same-document write (onload may not fire)
    setTimeout(function () {
        if (frame.contentDocument && frame.contentDocument.body) {
            frame.style.height = frame.contentDocument.body.scrollHeight + 'px';
        }
    }, 100);

    document.getElementById('print-preview-modal').classList.remove('hidden');
}

function closePrintPreview() {
    document.getElementById('print-preview-modal').classList.add('hidden');
}

function printPreview() {
    document.getElementById('print-preview-frame').contentWindow.print();
}

// ── Custom Fields Preview ──────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function openCustomFieldsPreview() {
    const body = document.getElementById('custom-fields-preview-body');
    body.innerHTML = '';

    const rows = document.querySelectorAll('#fields-container .field-row');

    if (!rows.length) {
        body.innerHTML = '<p class="col-span-12 text-center text-gray-400 text-sm py-6">No custom fields yet. Add one to see its preview here.</p>';
    }

    rows.forEach(row => {
        const label    = row.querySelector('input[name*="[field_label]"]')?.value.trim() || '(Untitled field)';
        const type     = row.querySelector('select[name*="[field_type]"]')?.value || 'text';
        const colSpan  = Math.max(1, Math.min(12, parseInt(row.querySelector('select[name*="[column_width]"]')?.value) || 12));
        const required = row.querySelector('input[name*="[is_required]"]')?.checked;
        const defVal   = row.querySelector('input[name*="[default_value]"]')?.value || '';
        const options  = (row.querySelector('textarea[name*="[field_options]"]')?.value || '')
            .split('\n').map(o => o.trim()).filter(Boolean);

        const req   = required ? '<span class="text-danger">*</span>' : '';
        let   input = '';

        if (type === 'textarea') {
            input = `<textarea rows="2" class="form-input" disabled>${escHtml(defVal)}</textarea>`;
        } else if (type === 'select') {
            const opts = options.map(o => `<option>${escHtml(o)}</option>`).join('');
            input = `<select class="form-select" disabled><option>— Select —</option>${opts}</select>`;
        } else if (type === 'date') {
            input = `<input type="date" class="form-input" value="${escHtml(defVal)}" disabled>`;
        } else {
            input = `<input type="text" class="form-input" value="${escHtml(defVal)}" disabled>`;
        }

        body.insertAdjacentHTML('beforeend', `
            <div class="col-span-12" style="--field-col-span:${colSpan}">
                <label class="form-label text-sm">${escHtml(label)} ${req}</label>
                ${input}
            </div>
        `);
    });

    document.getElementById('custom-fields-preview-modal').classList.remove('hidden');
}

function closeCustomFieldsPreview() {
    document.getElementById('custom-fields-preview-modal').classList.add('hidden');
}

function refreshCustomTags() {
    const inputs = document.querySelectorAll('.field-key-input');
    const list   = document.getElementById('custom-tag-list');
    const tags   = [];
    inputs.forEach(inp => { const v = inp.value.trim(); if (v) tags.push(v); });
    if (tags.length === 0) {
        list.innerHTML = '<span class="text-xs text-gray-400 italic">Add fields below to see them here.</span>';
        return;
    }
    list.innerHTML = tags.map(t =>
        `<button type="button" onclick="copyTag('${t}')"
                 class="px-2 py-0.5 rounded text-[11px] font-mono bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 hover:bg-purple-200 transition">
             &#123;&#123; ${t} &#125;&#125;
         </button>`
    ).join('');
}

// ── Summernote init ───────────────────────────────────────────────────
function initSummernote(jq) {
    $sn = jq; // expose to copyTag

    const v = getPaperSettings();

    jq('#template_body').summernote({
        height: null,
        minHeight: null,
        placeholder: 'Write the certificate body here…',
        toolbar: [
            ['style',   ['style']],
            ['font',    ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontname',['fontname']],
            ['fontsize',['fontsize']],
            ['color',   ['color']],
            ['para',    ['ul', 'ol', 'paragraph']],
            ['table',   ['table']],
            ['insert',  ['link', 'picture', 'hr']],
            ['view',    ['fullscreen', 'codeview', 'undo', 'redo']],
        ],
        fontSizes: ['7','8','9','10','11','12','13','14','16','18','20','22','24','26','28','32','36','40','48','56','64','72'],
        fontNames: ['Times New Roman', 'Arial', 'Calibri', 'Verdana', 'Georgia', 'Courier New'],
        styleTags: ['p', 'h1', 'h2', 'h3', 'h4'],
        callbacks: {
            onInit: function () {
                // Remove inline height Summernote sets so editor grows with content
                var editable = document.querySelector('.note-editable');
                var editArea = document.querySelector('.note-editing-area');
                if (editable) editable.style.removeProperty('height');
                if (editArea) editArea.style.removeProperty('height');
                applyPaperBg();
            },
            onChange: function (contents) {
                jq('#template_body').val(contents);
            },
        },
    });

    // Wire version radios
    document.querySelectorAll('.tpl-ver-radio').forEach(r => {
        const panel = r.closest('[id^="versions_"]');
        if (panel && panel.classList.contains('hidden')) r.disabled = true;
        r.addEventListener('change', applyPaperBg);
    });

    // On submit, make sure the textarea has latest content
    document.getElementById('type-form').addEventListener('submit', function () {
        jq('#template_body').val(jq('#template_body').summernote('code'));
    });

    refreshCustomTags();
    applyPaperBg();
}
</script>
@endsection

@push('inline-scripts')
@vite('resources/js/summernote-init.js')
<script>
(function waitForSummernote() {
    if (window.summernoteReady && window.$sn && typeof window.$sn('#template_body').summernote === 'function') {
        initSummernote(window.$sn);
    } else {
        setTimeout(waitForSummernote, 30);
    }
})();
</script>
@endpush
