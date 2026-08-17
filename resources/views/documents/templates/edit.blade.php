@extends('layouts.vertical', [
    'breadcrumbs' => [
        ['label' => 'Settings',        'url' => route('settings.index')],
        ['label' => 'Paper Templates', 'url' => route('documents.templates.index')],
        ['label' => ($template->exists ? 'Edit' : 'Create'), 'url' => ''],
    ],
    'title' => ($template->exists ? 'Edit' : 'Create') . ' Paper Template',
    'mode'  => $mode ?? '',
    'demo'  => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl shrink-0"></i>
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

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('documents.templates.index') }}" class="text-gray-400 hover:text-primary">
        <i class="mgc_arrow_left_line text-lg"></i>
    </a>
    <div>
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
            {{ $template->exists ? 'Edit: ' . $template->name : 'New Paper Template' }}
        </h4>
        @if($template->exists && $template->currentVersion)
        <p class="text-sm text-gray-400">
            Current: <span class="font-medium text-primary">v{{ $template->currentVersion->version }}</span>
            · {{ $template->versions->count() }} version(s) total
        </p>
        @else
        <p class="text-sm text-gray-400">Upload a paper background and set content padding.</p>
        @endif
    </div>
</div>

<form action="{{ $template->exists ? route('documents.templates.update', $template) : route('documents.templates.store') }}"
      method="POST" enctype="multipart/form-data" id="template-form">
    @csrf
    @if($template->exists) @method('PUT') @endif

    <div class="grid grid-cols-12 gap-6">

        {{-- Left: Settings --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-5">

            {{-- Basic Info --}}
            <div class="card p-5 space-y-4">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_paper_line text-primary"></i> Template Info
                </h6>
                <div>
                    <label class="form-label text-sm">Template Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-input"
                           value="{{ old('name', $template->name) }}"
                           placeholder="e.g. Classic Letterhead" required maxlength="255">
                </div>
                <div>
                    <label class="form-label text-sm">Description</label>
                    <textarea name="description" rows="2" class="form-input"
                              placeholder="Optional notes about this template…">{{ old('description', $template->description) }}</textarea>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="form-checkbox" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-gray-600 dark:text-gray-300">Active (available for selection)</label>
                </div>
            </div>

            {{-- Paper Settings --}}
            <div class="card p-5 space-y-4">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_settings_4_line text-primary"></i> Paper Settings
                    @if($template->exists)
                        @if($template->currentVersion && !$template->currentVersion->isUsed())
                        <span class="text-xs font-normal text-success ml-1">· v{{ $template->currentVersion->version }} not used yet — changes update it in place</span>
                        @else
                        <span class="text-xs font-normal text-gray-400 ml-1">· v{{ $template->currentVersion->version ?? '?' }} is in use — changes create a new version</span>
                        @endif
                    @endif
                </h6>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label text-sm">Paper Size</label>
                        <select name="paper_size" id="paper_size" class="form-select" onchange="updatePreviewPaperSize()">
                            <option value="a4"          {{ old('paper_size', $template->currentVersion?->paper_size ?? 'a4') === 'a4'          ? 'selected' : '' }}>A4</option>
                            <option value="letter"      {{ old('paper_size', $template->currentVersion?->paper_size ?? 'a4') === 'letter'      ? 'selected' : '' }}>Short / Letter (8.5" × 11")</option>
                            <option value="half_letter" {{ old('paper_size', $template->currentVersion?->paper_size ?? 'a4') === 'half_letter' ? 'selected' : '' }}>Half Letter (5.5" × 8.5")</option>
                            <option value="long"        {{ old('paper_size', $template->currentVersion?->paper_size ?? 'a4') === 'long'        ? 'selected' : '' }}>Long / Legal (8.5" × 13")</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-sm">Orientation</label>
                        <select name="orientation" id="orientation" class="form-select" onchange="updatePreviewPaperSize()">
                            <option value="portrait"  {{ old('orientation', $template->currentVersion?->orientation ?? 'portrait')  === 'portrait'  ? 'selected' : '' }}>Portrait</option>
                            <option value="landscape" {{ old('orientation', $template->currentVersion?->orientation ?? 'portrait') === 'landscape' ? 'selected' : '' }}>Landscape</option>
                        </select>
                    </div>
                </div>

                {{-- Padding Controls --}}
                <div>
                    <label class="form-label text-sm mb-1">Content Padding (px)</label>
                    <p class="text-xs text-gray-400 mb-3">
                        Controls how far from the paper edges the certificate text starts.
                        Increase <strong>Top</strong> if your letterhead occupies the upper area.
                    </p>
                    <div class="flex flex-col items-center gap-1 select-none">
                        <div class="flex flex-col items-center gap-0.5">
                            <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Top</label>
                            <input type="number" name="padding_top"
                                   class="form-input text-center text-sm w-20 py-1"
                                   value="{{ old('padding_top', $template->currentVersion?->padding_top ?? 160) }}"
                                   min="0" max="500" oninput="updatePreviewPadding()">
                        </div>
                        <div class="flex items-center gap-2 w-full">
                            <div class="flex flex-col items-center gap-0.5 flex-1">
                                <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Left</label>
                                <input type="number" name="padding_left"
                                       class="form-input text-center text-sm w-full py-1"
                                       value="{{ old('padding_left', $template->currentVersion?->padding_left ?? 72) }}"
                                       min="0" max="500" oninput="updatePreviewPadding()">
                            </div>
                            <div id="padding-paper-icon" class="shrink-0 {{ old('orientation', $template->currentVersion?->orientation ?? 'portrait') === 'landscape' ? 'w-20 h-16' : 'w-16 h-20' }} border-2 border-dashed border-gray-300 dark:border-gray-600 rounded flex items-center justify-center bg-gray-50 dark:bg-gray-800">
                                <i class="mgc_paper_line text-2xl text-gray-300 dark:text-gray-600"></i>
                            </div>
                            <div class="flex flex-col items-center gap-0.5 flex-1">
                                <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Right</label>
                                <input type="number" name="padding_right"
                                       class="form-input text-center text-sm w-full py-1"
                                       value="{{ old('padding_right', $template->currentVersion?->padding_right ?? 72) }}"
                                       min="0" max="500" oninput="updatePreviewPadding()">
                            </div>
                        </div>
                        <div class="flex flex-col items-center gap-0.5">
                            <input type="number" name="padding_bottom"
                                   class="form-input text-center text-sm w-20 py-1"
                                   value="{{ old('padding_bottom', $template->currentVersion?->padding_bottom ?? 72) }}"
                                   min="0" max="500" oninput="updatePreviewPadding()">
                            <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Bottom</label>
                        </div>
                    </div>
                </div>

                {{-- Change note --}}
                <div>
                    <label class="form-label text-sm">Change Note <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="change_note" class="form-input"
                           value="{{ old('change_note') }}"
                           placeholder="e.g. Updated top padding for new letterhead">
                    <p class="text-xs text-gray-400 mt-1">Saved with this version in the history log.</p>
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <a href="{{ route('documents.templates.index') }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">Cancel</a>
                <button type="submit" class="btn bg-primary text-white px-8 flex items-center gap-2">
                    <i class="mgc_save_line"></i>
                    {{ $template->exists ? 'Save Changes' : 'Create Template' }}
                </button>
            </div>
        </div>

        {{-- Right: Upload + Live Preview --}}
        <div class="col-span-12 lg:col-span-7 flex flex-col gap-5">

            {{-- Upload --}}
            <div class="card p-5 space-y-4">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <i class="mgc_upload_2_line text-primary"></i> Paper Background Image
                </h6>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Upload your pre-designed letterhead (PNG or JPG).
                    Recommended: <strong>A4 = 794×1123px</strong> at 96dpi.
                    Uploading a new image will create a new version.
                </p>

                @if($template->currentVersion?->paper_bg)
                <div class="flex items-start gap-4">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden w-24 shrink-0 relative">
                        <img src="{{ asset('storage/' . $template->currentVersion->paper_bg) }}"
                             alt="Current" class="w-full object-cover object-top">
                        <span class="absolute top-1 left-1 text-[9px] font-bold bg-primary text-white px-1 rounded">
                            v{{ $template->currentVersion->version }}
                        </span>
                    </div>
                    <div class="flex-1 space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Current background (v{{ $template->currentVersion->version }}).
                            Upload a new file to replace it in the next version.
                        </p>
                        <input type="file" name="paper_bg" accept="image/png,image/jpeg"
                               class="form-input text-sm py-1.5" onchange="previewBg(this)">
                    </div>
                </div>
                @else
                <div id="upload-drop-area"
                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-primary transition"
                     onclick="document.getElementById('bg-file-input').click()">
                    <div id="upload-drop-placeholder">
                        <i class="mgc_upload_2_line text-4xl text-gray-300 dark:text-gray-600 block mb-2"></i>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Click to upload or drag & drop</p>
                        <p class="text-xs text-gray-400 mt-1">PNG or JPG · Max 10MB · A4 794×1123px recommended</p>
                    </div>
                </div>
                <input type="file" id="bg-file-input" name="paper_bg"
                       accept="image/png,image/jpeg" class="hidden"
                       onchange="previewBg(this)">
                @endif
            </div>

            {{-- Live Preview --}}
            <div class="card p-5">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-3">
                    <i class="mgc_eye_2_line text-primary"></i> Live Preview
                    <span class="text-xs font-normal text-gray-400 ml-1">(actual paper size)</span>
                </h6>

                @php
                    $previewPaperSize   = old('paper_size', $template->currentVersion?->paper_size ?? 'a4');
                    $previewOrientation = old('orientation', $template->currentVersion?->orientation ?? 'portrait');
                    [$previewW, $previewH] = match ($previewPaperSize) {
                        'a4'          => ['8.27in', '11.69in'],
                        'half_letter' => ['5.5in',  '8.5in'],
                        'long'        => ['8.5in',  '13in'],
                        default       => ['8.5in',  '11in'],
                    };
                    if ($previewOrientation === 'landscape') { [$previewW, $previewH] = [$previewH, $previewW]; }
                @endphp
                <div style="background:#e5e7eb; padding:24px; border-radius:0.5rem; overflow-x:auto;">
                    <div id="paper-preview"
                         style="
                            width: {{ $previewW }};
                            min-width: {{ $previewW }};
                            height: {{ $previewH }};
                            min-height: {{ $previewH }};
                            background-color: #fff;
                            border: 1px solid #ccc;
                            box-sizing: border-box;
                            overflow: hidden;
                            box-shadow: 0 0 10px rgba(0,0,0,0.1);
                            position: relative;
                            margin: 0 auto;
                            @if($template->currentVersion?->paper_bg)
                            background-image: url('{{ asset('storage/' . $template->currentVersion->paper_bg) }}');
                            background-size: cover;
                            background-position: center;
                            background-repeat: no-repeat;
                            @endif
                         ">
                        <div id="preview-content-zone"
                             style="
                                position: absolute;
                                top: {{ old('padding_top', $template->currentVersion?->padding_top ?? 160) }}px;
                                left: {{ old('padding_left', $template->currentVersion?->padding_left ?? 72) }}px;
                                right: {{ old('padding_right', $template->currentVersion?->padding_right ?? 72) }}px;
                                bottom: {{ old('padding_bottom', $template->currentVersion?->padding_bottom ?? 72) }}px;
                                border: 2px dashed rgba(59,130,246,0.4);
                                border-radius: 4px;
                                background: rgba(59,130,246,0.04);
                             ">
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;">
                                <div style="text-align:center;">
                                    <p style="font-size:11px;color:rgba(59,130,246,0.5);font-family:sans-serif;">Certificate content goes here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 text-center mt-2">Blue dashed area = where certificate text will appear</p>
            </div>

        </div>
    </div>
</form>

{{-- Version History + Activity Log (only on existing templates) --}}
@if($template->exists && $template->versions->count())
<div class="mt-8">
    <div class="card p-5">
        <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
            <i class="mgc_history_line text-primary"></i> Version History
            <span class="text-xs font-normal text-gray-400 ml-1">· {{ $template->versions->count() }} version(s)</span>
        </h6>

        <div class="space-y-3">
            @foreach($template->versions as $ver)
            <div class="flex items-start gap-4 p-3 rounded-lg border
                {{ $ver->id === $template->current_version_id
                    ? 'border-primary/40 bg-primary/5'
                    : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40' }}">

                {{-- Thumbnail --}}
                <div class="w-12 h-16 rounded border border-gray-200 dark:border-gray-600 overflow-hidden shrink-0 bg-white relative">
                    @if($ver->paper_bg)
                    <img src="{{ asset('storage/' . $ver->paper_bg) }}"
                         alt="v{{ $ver->version }}"
                         class="w-full h-full object-cover object-top">
                    @else
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="mgc_paper_line text-gray-300 text-sm"></i>
                    </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">
                            v{{ $ver->version }}
                        </span>
                        @if($ver->id === $template->current_version_id)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary text-white">Current</span>
                        @endif
                        <span class="text-xs text-gray-400">
                            {{ strtoupper($ver->paper_size) }} · {{ ucfirst($ver->orientation) }}
                        </span>
                        <span class="text-xs text-gray-400">
                            Padding: {{ $ver->padding_top }}↑ {{ $ver->padding_left }}← {{ $ver->padding_right }}→ {{ $ver->padding_bottom }}↓
                        </span>
                    </div>
                    @if($ver->change_note)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $ver->change_note }}</p>
                    @endif
                    <p class="text-[11px] text-gray-400 mt-1">
                        <i class="mgc_time_line"></i> {{ $ver->created_at->format('M d, Y g:i A') }}
                        @if($ver->creator)
                        · <i class="mgc_user_3_line"></i> {{ $ver->creator->name }}
                        @endif
                    </p>
                </div>

                {{-- Actions --}}
                <div class="shrink-0 flex flex-col gap-1.5">
                    <button type="button"
                            onclick="previewVersion('{{ $ver->paper_bg ? asset('storage/' . $ver->paper_bg) : '' }}', {{ $ver->padding_top }}, {{ $ver->padding_bottom }}, {{ $ver->padding_left }}, {{ $ver->padding_right }}, {{ $ver->version }})"
                            class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs py-1 px-3 hover:bg-gray-100 dark:hover:bg-gray-700 whitespace-nowrap flex items-center gap-1">
                        <i class="mgc_eye_2_line"></i> Preview
                    </button>
                    @if($ver->id !== $template->current_version_id)
                    <form action="{{ route('documents.templates.versions.set', [$template, $ver]) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="btn border-primary/40 text-primary text-xs py-1 px-3 hover:bg-primary/10 whitespace-nowrap flex items-center gap-1">
                            <i class="mgc_check_line"></i> Use this
                        </button>
                    </form>
                    @endif
                </div>

            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Version Preview Modal --}}
<div id="ver-preview-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background: rgba(0,0,0,0.7);"
     onclick="if(event.target===this) closeVersionPreview()">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[90vh]">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <div>
                <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i class="mgc_eye_2_line text-primary"></i>
                    <span id="ver-preview-title">Version Preview</span>
                </h6>
                <p class="text-xs text-gray-400 mt-0.5" id="ver-preview-meta"></p>
            </div>
            <button type="button" onclick="closeVersionPreview()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">
                <i class="mgc_close_line"></i>
            </button>
        </div>

        {{-- Modal body: A4 paper --}}
        <div class="overflow-y-auto p-5 flex-1">
            <div class="w-full relative" style="padding-bottom: 141.4%;">
                <div id="ver-paper"
                     class="absolute inset-0 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white shadow-sm"
                     style="background-size: cover; background-position: top center; background-repeat: no-repeat;">
                    <div id="ver-content-zone"
                         class="absolute bg-primary/5 border-2 border-dashed border-primary/40 rounded">
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <i class="mgc_quill_pen_line text-primary/40 text-3xl block mb-1"></i>
                                <p class="text-[10px] text-primary/50 font-medium">Certificate content<br>goes here</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-400 text-center mt-3">Blue dashed area = where certificate text will appear</p>
        </div>

    </div>
</div>
@endif

{{-- Activity Log --}}
@if($template->exists && isset($logs) && $logs->count())
<div class="mt-5">
    <div class="card p-5">
        <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
            <i class="mgc_list_check_line text-info"></i> Activity Log
            <span class="text-xs font-normal text-gray-400 ml-1">· last {{ $logs->count() }} events</span>
        </h6>

        <div class="relative">
            {{-- Timeline line --}}
            <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

            <div class="space-y-4">
                @foreach($logs as $log)
                <div class="flex items-start gap-4 pl-10 relative">
                    {{-- Dot --}}
                    <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full flex items-center justify-center shrink-0
                        {{ $log->action === 'created' ? 'bg-success/10' : 'bg-primary/10' }}">
                        <i class="text-sm
                            {{ $log->action === 'created' ? 'mgc_add_circle_line text-success' : 'mgc_edit_2_line text-primary' }}">
                        </i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0 pb-4 border-b border-gray-100 dark:border-gray-800 last:border-0 last:pb-0">
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ $log->description }}</p>
                        <div class="flex items-center gap-3 mt-1 flex-wrap">
                            <span class="text-[11px] text-gray-400 flex items-center gap-1">
                                <i class="mgc_time_line"></i>
                                {{ $log->created_at->format('M d, Y g:i A') }}
                            </span>
                            @if($log->user)
                            <span class="text-[11px] text-gray-400 flex items-center gap-1">
                                <i class="mgc_user_3_line"></i>
                                {{ $log->user->name }}
                            </span>
                            @endif
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide
                                {{ $log->action === 'created' ? 'bg-success/10 text-success' : 'bg-primary/10 text-primary' }}">
                                {{ $log->action }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
<script>
function previewVersion(bgUrl, padTop, padBottom, padLeft, padRight, version) {
    const modal   = document.getElementById('ver-preview-modal');
    const paper   = document.getElementById('ver-paper');
    const zone    = document.getElementById('ver-content-zone');
    const title   = document.getElementById('ver-preview-title');
    const meta    = document.getElementById('ver-preview-meta');

    title.textContent = 'v' + version + ' Preview';
    meta.textContent  = 'Padding — Top: ' + padTop + 'px · Left: ' + padLeft + 'px · Right: ' + padRight + 'px · Bottom: ' + padBottom + 'px';

    if (bgUrl) {
        paper.style.backgroundImage = 'url(' + bgUrl + ')';
    } else {
        paper.style.backgroundImage = 'none';
    }

    zone.style.top    = 'calc(' + padTop    + ' / 842 * 100%)';
    zone.style.bottom = 'calc(' + padBottom + ' / 842 * 100%)';
    zone.style.left   = 'calc(' + padLeft   + ' / 595 * 100%)';
    zone.style.right  = 'calc(' + padRight  + ' / 595 * 100%)';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeVersionPreview() {
    const modal = document.getElementById('ver-preview-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeVersionPreview();
});

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function previewBg(input) {
    if (!input.files || !input.files[0]) return;
    const url      = URL.createObjectURL(input.files[0]);
    const preview  = document.getElementById('paper-preview');
    const dropArea = document.getElementById('upload-drop-area');

    preview.style.backgroundImage    = `url(${url})`;
    preview.style.backgroundSize     = 'cover';
    preview.style.backgroundPosition = 'center';
    preview.style.backgroundRepeat   = 'no-repeat';

    if (dropArea) {
        dropArea.innerHTML = `
            <div class="flex items-center gap-3 text-left">
                <img src="${url}" class="w-16 h-20 object-cover object-top rounded border border-gray-200 dark:border-gray-600 shrink-0">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">${escHtml(input.files[0].name)}</p>
                    <p class="text-xs text-gray-400">Click to choose a different file</p>
                </div>
            </div>`;
    }
}

function updatePreviewPadding() {
    const top    = parseInt(document.querySelector('[name=padding_top]').value)    || 0;
    const bottom = parseInt(document.querySelector('[name=padding_bottom]').value) || 0;
    const left   = parseInt(document.querySelector('[name=padding_left]').value)   || 0;
    const right  = parseInt(document.querySelector('[name=padding_right]').value)  || 0;

    const zone = document.getElementById('preview-content-zone');
    if (!zone) return;
    zone.style.top    = top    + 'px';
    zone.style.bottom = bottom + 'px';
    zone.style.left   = left   + 'px';
    zone.style.right  = right  + 'px';
}

function updatePreviewPaperSize() {
    const paperSize   = document.getElementById('paper_size').value;
    const orientation = document.getElementById('orientation').value;

    const sizes = {
        a4:          ['8.27in', '11.69in'],
        letter:      ['8.5in',  '11in'],
        half_letter: ['5.5in',  '8.5in'],
        long:        ['8.5in',  '13in'],
    };
    let [w, h] = sizes[paperSize] || sizes.letter;
    if (orientation === 'landscape') { [w, h] = [h, w]; }

    // Flip the padding-control paper icon to match orientation
    const paperIcon = document.getElementById('padding-paper-icon');
    if (paperIcon) {
        const landscape = orientation === 'landscape';
        paperIcon.classList.toggle('w-20', landscape);
        paperIcon.classList.toggle('h-16', landscape);
        paperIcon.classList.toggle('w-16', !landscape);
        paperIcon.classList.toggle('h-20', !landscape);
    }

    const preview = document.getElementById('paper-preview');
    if (!preview) return;
    preview.style.width     = w;
    preview.style.minWidth  = w;
    preview.style.height    = h;
    preview.style.minHeight = h;
}

document.addEventListener('DOMContentLoaded', function () {
    // Sync the live preview + padding icon to the current paper size / orientation
    updatePreviewPaperSize();

    const drop  = document.getElementById('upload-drop-area');
    const input = document.getElementById('bg-file-input');
    if (!drop || !input) return;

    drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('border-primary'); });
    drop.addEventListener('dragleave', () => drop.classList.remove('border-primary'));
    drop.addEventListener('drop', e => {
        e.preventDefault();
        drop.classList.remove('border-primary');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            previewBg(input);
        }
    });
});
</script>
@endsection
