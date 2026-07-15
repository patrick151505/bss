@extends('layouts.vertical', [
    'title'         => 'ID Card Designer',
    'sub_title'     => 'Barangay IDs',
    'sub_title_url' => route('citizens.ids.index'),
    'tagline'       => 'Design the front and back of the barangay ID card.',
])

@section('content')

@php
$bgFrontUrl = $tpl->bg_front ? asset(str_replace('public/', 'storage/', $tpl->bg_front)) : null;
$bgBackUrl  = $tpl->bg_back  ? asset(str_replace('public/', 'storage/', $tpl->bg_back))  : null;

$placeholderGroups = [
    'Personal'          => ['full_name','fname','lname','mname','suffix','bday','gender'],
    'ID Info'           => ['id_no','qrcode_value','since','valid_until'],
    'Address & Contact' => ['address','contact'],
    'Barangay'          => ['brgy_name','municipality','province','captain','captain_pos'],
    'Emergency Contact' => ['ic_name','ic_contact','ic_relationship','ic_address'],
    'Media'             => ['photo_url','qr_img','qr_img_60','signature_img'],
];

$groupColors = [
    'Personal'          => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300',
    'ID Info'           => 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300',
    'Address & Contact' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-700 dark:text-green-300',
    'Barangay'          => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-300',
    'Emergency Contact' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-700 dark:text-red-300',
    'Media'             => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-300',
];
@endphp

{{-- ── Top bar ────────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-400 flex items-center gap-1">
            <i class="mgc_information_line text-sm"></i>
            HTML is per side. CSS and JS are shared across both sides.
        </span>
        {{-- Unsaved changes badge --}}
        <span id="unsaved-badge"
              class="hidden text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 flex items-center gap-1">
            <i class="mgc_edit_line text-xs"></i> Unsaved changes
        </span>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="refreshBoth()"
                class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-2 text-sm">
            <i class="mgc_refresh_line"></i> Refresh
        </button>
        <button onclick="saveTemplate()" id="save-btn"
                class="btn bg-primary text-white flex items-center gap-2 text-sm">
            <i class="mgc_save_line"></i> Save Template
        </button>
    </div>
</div>

{{-- ── HTML: Front | Back ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4 mb-4">

    @foreach(['front' => ['label'=>'Front','accent'=>'primary'], 'back' => ['label'=>'Back','accent'=>'warning']] as $side => $cfg)
    @php
        $bgUrl  = $side === 'front' ? $bgFrontUrl : $bgBackUrl;
        $orient = $side === 'front' ? ($tpl->orientation_front ?? 'landscape') : ($tpl->orientation_back ?? 'landscape');
        $html   = $side === 'front' ? ($tpl->html_front ?? '') : ($tpl->html_back ?? '');
        // CR80: landscape 3.375×2.125in, portrait 2.125×3.375in — scaled to screen at 96dpi
        $isPortrait = $orient === 'portrait';
        $cardW = $isPortrait ? 204 : 324;
        $cardH = $isPortrait ? 204 : 204;
        $ratio = $isPortrait ? (2.125/3.375) : (2.125/3.375); // h/w
    @endphp

    <div class="space-y-3">

        {{-- Side label --}}
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded text-xs font-bold
                {{ $side === 'front' ? 'bg-primary/15 text-primary' : 'bg-warning/15 text-warning' }}">
                {{ $cfg['label'][0] }}
            </span>
            <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ $cfg['label'] }} Side</span>
        </div>

        {{-- CR80 Preview --}}
        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 border-b dark:border-gray-700 flex items-center justify-between">
                <span class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Preview</span>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-400" id="preview-label-{{ $side }}">{{ ucfirst($orient) }}</span>
                    <span class="text-[10px] text-gray-300 dark:text-gray-600">CR80</span>
                </div>
            </div>
            {{-- Fixed-ratio CR80 container: landscape = 3.375:2.125 = 1.588:1 --}}
            <div class="p-3 bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                <div id="preview-wrap-{{ $side }}"
                     class="relative overflow-hidden shadow-md"
                     style="width:100%; max-width:324px; aspect-ratio: {{ $isPortrait ? '2.125/3.375' : '3.375/2.125' }};">
                    <iframe id="preview-{{ $side }}"
                            class="border-0 absolute inset-0 w-full h-full"
                            style=""></iframe>
                </div>
            </div>
        </div>

        {{-- Orientation --}}
        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl px-4 py-3 flex items-center gap-4">
            <span class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold shrink-0">Orientation</span>
            <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <button id="btn-landscape-{{ $side }}" onclick="setOrientation('{{ $side }}','landscape')"
                        class="px-3 py-1.5 text-xs font-medium flex items-center gap-1
                               {{ $orient === 'landscape' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-300' }} transition">
                    <i class="mgc_monitor_line"></i> Landscape
                </button>
                <button id="btn-portrait-{{ $side }}" onclick="setOrientation('{{ $side }}','portrait')"
                        class="px-3 py-1.5 text-xs font-medium flex items-center gap-1
                               {{ $orient === 'portrait' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-300' }} transition">
                    <i class="mgc_phone_line"></i> Portrait
                </button>
            </div>
            <span class="text-[10px] text-gray-400 ml-auto">
                <span id="card-dims-{{ $side }}">{{ $isPortrait ? '2.125 × 3.375 in' : '3.375 × 2.125 in' }}</span>
            </span>
        </div>

        {{-- Background --}}
        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl px-4 py-3 flex items-center gap-3">
            <span class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold shrink-0">Background</span>
            <div class="w-16 h-11 rounded border-2 border-dashed border-gray-200 dark:border-gray-700
                        overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-800 shrink-0 cursor-pointer"
                 onclick="document.getElementById('bg-file-{{ $side }}').click()">
                <img id="bg-thumb-img-{{ $side }}" src="{{ $bgUrl ?? '' }}"
                     class="{{ $bgUrl ? '' : 'hidden' }} w-full h-full object-cover">
                <i id="bg-thumb-icon-{{ $side }}"
                   class="mgc_upload_2_line text-gray-300 {{ $bgUrl ? 'hidden' : '' }}"></i>
            </div>
            <input type="file" id="bg-file-{{ $side }}" accept="image/jpeg,image/png" class="hidden"
                   onchange="uploadBg(this,'{{ $side }}')">
            <button type="button" onclick="document.getElementById('bg-file-{{ $side }}').click()"
                    class="text-xs text-gray-500 hover:text-primary transition">Upload</button>
            <button type="button" id="bg-remove-btn-{{ $side }}" onclick="removeBg('{{ $side }}')"
                    class="{{ $bgUrl ? '' : 'hidden' }} text-xs text-danger hover:text-danger/70 transition">Remove</button>
            <span class="text-[10px] text-gray-400 ml-auto">Use <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">var(--bg)</code> in CSS</span>
        </div>

        {{-- HTML editor --}}
        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 border-b dark:border-gray-700 flex items-center justify-between">
                <span class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">HTML — {{ $cfg['label'] }}</span>
                <span class="text-[10px] text-gray-400">Click a placeholder below to insert at cursor</span>
            </div>
            <textarea id="html-{{ $side }}"
                      class="editor-ta w-full font-mono text-xs bg-gray-900 text-green-300 p-3 border-0 outline-none resize-y"
                      rows="12" spellcheck="false"
                      oninput="debounceRefresh('{{ $side }}'); markUnsaved();"
            >{{ $html }}</textarea>
        </div>

    </div>
    @endforeach

</div>

{{-- ── Shared CSS + JS ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4 mb-4">

    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">CSS</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-400">Shared — applies to both sides</span>
            </div>
            <span class="text-[10px] text-gray-400">Use <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">var(--bg)</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">--card-w</code>, <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">--card-h</code></span>
        </div>
        <textarea id="css-shared"
                  class="editor-ta w-full font-mono text-xs bg-gray-900 text-blue-300 p-3 border-0 outline-none resize-y"
                  rows="14" spellcheck="false"
                  oninput="debounceRefreshBoth(); markUnsaved();"
        >{{ $tpl->css_shared ?? '' }}</textarea>
    </div>

    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">JavaScript</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-400">Shared — runs inside each card iframe</span>
            </div>
        </div>
        <textarea id="js-shared"
                  class="editor-ta w-full font-mono text-xs bg-gray-900 text-yellow-300 p-3 border-0 outline-none resize-y"
                  rows="14" spellcheck="false"
                  oninput="debounceRefreshBoth(); markUnsaved();"
        >{{ $tpl->js_shared ?? '' }}</textarea>
    </div>

</div>

{{-- ── Placeholders ────────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-4">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">
            Available Placeholders
        </p>
        <p class="text-[10px] text-gray-400">
            <i class="mgc_cursor_line"></i> Click = copy &nbsp;|&nbsp;
            <i class="mgc_edit_line"></i> Click while editor focused = insert at cursor
        </p>
    </div>

    <div class="space-y-3">
        @foreach($placeholderGroups as $group => $phs)
        <div class="flex items-start gap-3">
            <span class="text-[10px] font-semibold text-gray-400 w-28 shrink-0 pt-1">{{ $group }}</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach($phs as $ph)
                @php $tag = '{{' . $ph . '}}'; @endphp
                <button type="button"
                        data-ph="{{ $tag }}"
                        class="ph-btn text-[10px] px-2 py-1 rounded font-mono cursor-pointer select-none border transition
                               {{ $groupColors[$group] }} hover:opacity-80 active:scale-95">
                    {{ $tag }}
                </button>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-4 mt-4 pt-3 border-t dark:border-gray-700 text-[10px] text-gray-400">
        <span><strong class="font-mono">&#123;&#123;qr_img_N&#125;&#125;</strong> — QR at exact Npx, e.g. <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">&#123;&#123;qr_img_80&#125;&#125;</code></span>
        <span><strong class="font-mono">&#123;&#123;photo_url&#125;&#125;</strong> — use as <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">&lt;img src="&#123;&#123;photo_url&#125;&#125;"&gt;</code></span>
        <span><strong class="font-mono">var(--bg)</strong> — background image CSS variable</span>
    </div>
</div>

@endsection

@section('script')
<script>
const SAVE_URL      = '{{ route('citizens.ids.template.save') }}';
const UPLOAD_BG_URL = '{{ route('citizens.ids.template.upload-bg') }}';
const REMOVE_BG_URL = '{{ route('citizens.ids.template.remove-bg') }}';
const CSRF          = '{{ csrf_token() }}';

const TPL = {
    front: {
        orientation: @json($tpl->orientation_front ?? 'landscape'),
        bgUrl:  @json($bgFrontUrl),
        bgPath: @json($tpl->bg_front ?? null),
    },
    back: {
        orientation: @json($tpl->orientation_back ?? 'landscape'),
        bgUrl:  @json($bgBackUrl),
        bgPath: @json($tpl->bg_back ?? null),
    },
};

// ── Unsaved changes ───────────────────────────────────────────────────
let _unsaved = false;
function markUnsaved() {
    if (_unsaved) return;
    _unsaved = true;
    document.getElementById('unsaved-badge').classList.remove('hidden');
}
function clearUnsaved() {
    _unsaved = false;
    document.getElementById('unsaved-badge').classList.add('hidden');
}

// ── Last focused editor (for insert-at-cursor) ─────────────────────────
let _lastEditor = null;
document.addEventListener('focusin', function (e) {
    if (e.target.classList.contains('editor-ta')) {
        _lastEditor = e.target;
    }
});

// ── Static sample QR SVG ──────────────────────────────────────────────
function qrSvg(px) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="${px}" height="${px}" style="display:inline-block;image-rendering:pixelated;vertical-align:middle;">
<rect width="21" height="21" fill="white"/>
<rect x="0" y="0" width="7" height="7" fill="black"/><rect x="1" y="1" width="5" height="5" fill="white"/><rect x="2" y="2" width="3" height="3" fill="black"/>
<rect x="14" y="0" width="7" height="7" fill="black"/><rect x="15" y="1" width="5" height="5" fill="white"/><rect x="16" y="2" width="3" height="3" fill="black"/>
<rect x="0" y="14" width="7" height="7" fill="black"/><rect x="1" y="15" width="5" height="5" fill="white"/><rect x="2" y="16" width="3" height="3" fill="black"/>
<rect x="8" y="6" width="1" height="1" fill="black"/><rect x="10" y="6" width="1" height="1" fill="black"/><rect x="12" y="6" width="1" height="1" fill="black"/>
<rect x="6" y="8" width="1" height="1" fill="black"/><rect x="6" y="10" width="1" height="1" fill="black"/><rect x="6" y="12" width="1" height="1" fill="black"/>
<rect x="8" y="8" width="1" height="1" fill="black"/><rect x="9" y="8" width="1" height="1" fill="black"/><rect x="11" y="8" width="1" height="1" fill="black"/><rect x="13" y="8" width="1" height="1" fill="black"/>
<rect x="8" y="9" width="1" height="1" fill="black"/><rect x="10" y="9" width="1" height="1" fill="black"/><rect x="12" y="9" width="1" height="1" fill="black"/>
<rect x="9" y="10" width="1" height="1" fill="black"/><rect x="11" y="10" width="1" height="1" fill="black"/><rect x="13" y="10" width="1" height="1" fill="black"/>
<rect x="8" y="11" width="1" height="1" fill="black"/><rect x="10" y="11" width="1" height="1" fill="black"/>
<rect x="8" y="12" width="1" height="1" fill="black"/><rect x="9" y="12" width="1" height="1" fill="black"/><rect x="12" y="12" width="1" height="1" fill="black"/>
<rect x="14" y="8" width="1" height="1" fill="black"/><rect x="16" y="8" width="1" height="1" fill="black"/><rect x="18" y="8" width="1" height="1" fill="black"/><rect x="20" y="8" width="1" height="1" fill="black"/>
<rect x="15" y="9" width="1" height="1" fill="black"/><rect x="17" y="9" width="1" height="1" fill="black"/><rect x="19" y="9" width="1" height="1" fill="black"/>
<rect x="14" y="10" width="1" height="1" fill="black"/><rect x="16" y="10" width="1" height="1" fill="black"/><rect x="20" y="10" width="1" height="1" fill="black"/>
</svg>`;
}

const SAMPLE = {
    full_name:       'DELA CRUZ, JUAN S.',
    fname:           'Juan',
    lname:           'Dela Cruz',
    mname:           'Santos',
    suffix:          '',
    id_no:           @json(\App\Models\Setting::instance()->formatCitizenId(1)),
    qrcode_value:    'abc123',
    bday:            'Jan 01, 1990',
    gender:          'Male',
    address:         'Blk 1 Lot 2, Zone 1',
    contact:         '09171234567',
    since:           '2010',
    valid_until:     'Jun 18, 2028',
    brgy_name:       'Sample Barangay',
    municipality:    'Sample City',
    province:        'Sample Province',
    captain:         'Hon. Juan Reyes',
    captain_pos:     'Barangay Captain',
    ic_name:         'Maria Dela Cruz',
    ic_contact:      '09181234567',
    ic_relationship: 'Spouse',
    ic_address:      'Zone 2, Sample',
    signature_img:   '<div class="signature" style="position:absolute;top:158px;left:40px;"><img src="" style="display:block;height:22px;opacity:.4;border:1px dashed #aaa;" title="signature placeholder"></div>',
    photo_url:       'https://ui-avatars.com/api/?name=Juan+Dela+Cruz&size=128&background=6366f1&color=fff',
    qr_img:          `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" style="width:100%;height:100%;image-rendering:pixelated;"><rect width="21" height="21" fill="white"/><rect x="0" y="0" width="7" height="7" fill="black"/><rect x="1" y="1" width="5" height="5" fill="white"/><rect x="2" y="2" width="3" height="3" fill="black"/><rect x="14" y="0" width="7" height="7" fill="black"/><rect x="15" y="1" width="5" height="5" fill="white"/><rect x="16" y="2" width="3" height="3" fill="black"/><rect x="0" y="14" width="7" height="7" fill="black"/><rect x="1" y="15" width="5" height="5" fill="white"/><rect x="2" y="16" width="3" height="3" fill="black"/><rect x="8" y="6" width="1" height="1" fill="black"/><rect x="10" y="6" width="1" height="1" fill="black"/><rect x="8" y="8" width="2" height="2" fill="black"/><rect x="11" y="9" width="2" height="2" fill="black"/><rect x="14" y="8" width="1" height="1" fill="black"/><rect x="16" y="8" width="1" height="1" fill="black"/></svg>`,
};

// ── Orientation ───────────────────────────────────────────────────────
function setOrientation(side, o) {
    TPL[side].orientation = o;
    const active   = 'px-3 py-1.5 text-xs font-medium flex items-center gap-1 bg-primary text-white transition';
    const inactive = 'px-3 py-1.5 text-xs font-medium flex items-center gap-1 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-300 transition';
    document.getElementById(`btn-landscape-${side}`).className = o === 'landscape' ? active : inactive;
    document.getElementById(`btn-portrait-${side}`).className  = o === 'portrait'  ? active : inactive;
    document.getElementById(`preview-label-${side}`).textContent = o.charAt(0).toUpperCase() + o.slice(1);
    document.getElementById(`card-dims-${side}`).textContent = o === 'portrait' ? '2.125 × 3.375 in' : '3.375 × 2.125 in';
    // Update preview wrap aspect ratio
    const wrap = document.getElementById(`preview-wrap-${side}`);
    wrap.style.aspectRatio = o === 'portrait' ? '2.125/3.375' : '3.375/2.125';
    markUnsaved();
    refreshPreview(side);
}

// ── Background ────────────────────────────────────────────────────────
function uploadBg(input, side) {
    if (!input.files[0]) return;
    const fd = new FormData();
    fd.append('bg', input.files[0]);
    fd.append('side', side);
    fd.append('_token', CSRF);
    fetch(UPLOAD_BG_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            TPL[side].bgUrl  = data.url;
            TPL[side].bgPath = data.path;
            setBgThumb(side, data.url);
            refreshPreview(side);
        });
    input.value = '';
}

function removeBg(side) {
    const fd = new FormData();
    fd.append('side', side);
    fd.append('_token', CSRF);
    fetch(REMOVE_BG_URL, { method: 'POST', body: fd })
        .then(() => {
            TPL[side].bgUrl  = null;
            TPL[side].bgPath = null;
            setBgThumb(side, null);
            refreshPreview(side);
        });
}

function setBgThumb(side, url) {
    const img  = document.getElementById(`bg-thumb-img-${side}`);
    const icon = document.getElementById(`bg-thumb-icon-${side}`);
    const rmv  = document.getElementById(`bg-remove-btn-${side}`);
    if (url) {
        img.src = url; img.classList.remove('hidden');
        icon.classList.add('hidden'); rmv.classList.remove('hidden');
    } else {
        img.classList.add('hidden'); img.src = '';
        icon.classList.remove('hidden'); rmv.classList.add('hidden');
    }
}

// ── Preview ───────────────────────────────────────────────────────────
const debounceTimers = {};

function debounceRefresh(side) {
    clearTimeout(debounceTimers[side]);
    debounceTimers[side] = setTimeout(() => refreshPreview(side), 400);
}

function debounceRefreshBoth() {
    clearTimeout(debounceTimers['both']);
    debounceTimers['both'] = setTimeout(() => refreshBoth(), 400);
}

function refreshPreview(side) {
    const bg  = TPL[side].bgUrl || '';
    const o   = TPL[side].orientation;
    let   html = document.getElementById(`html-${side}`).value;
    const css  = document.getElementById('css-shared').value;
    const js   = document.getElementById('js-shared').value;

    Object.entries(SAMPLE).forEach(([k, v]) => {
        html = html.split('{{' + k + '}}').join(v);
    });
    html = html.replace(/\{\{qr_img_(\d+)\}\}/g, (_, px) => qrSvg(Number(px)));

    const isPortrait = o === 'portrait';
    // CR80 at 96dpi: landscape 324×204px, portrait 204×324px
    const cardW = isPortrait ? 204 : 324;
    const cardH = isPortrait ? 324 : 204;

    const bgStyle = bg ? `background-image:url('${bg}');background-size:cover;background-position:center;` : '';

    const doc = `<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
:root{--bg:url('${bg}');--card-w:${cardW}px;--card-h:${cardH}px;}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{width:${cardW}px;height:${cardH}px;overflow:hidden;}
body{font-family:Arial,sans-serif;}
.id-card-preview{width:${cardW}px;height:${cardH}px;position:relative;overflow:hidden;${bgStyle}}
${css}
</style></head><body>
<div class="id-card-preview">${html}</div>
<script>${js}<\/script>
</body></html>`;

    const frame = document.getElementById(`preview-${side}`);
    frame.contentDocument.open();
    frame.contentDocument.write(doc);
    frame.contentDocument.close();
}

function refreshBoth() {
    refreshPreview('front');
    refreshPreview('back');
}

// ── Save ──────────────────────────────────────────────────────────────
function saveTemplate() {
    const btn = document.getElementById('save-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Saving…';

    fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            orientation_front: TPL.front.orientation,
            html_front:        document.getElementById('html-front').value,
            orientation_back:  TPL.back.orientation,
            html_back:         document.getElementById('html-back').value,
            css_shared:        document.getElementById('css-shared').value,
            js_shared:         document.getElementById('js-shared').value,
        }),
    })
    .then(r => r.json())
    .then(() => {
        clearUnsaved();
        btn.innerHTML = '<i class="mgc_check_line"></i> Saved!';
        btn.style.background = '#059669';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="mgc_save_line"></i> Save Template';
            btn.style.background = '';
        }, 2000);
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_close_line"></i> Save Failed';
        btn.style.background = '#dc2626';
        setTimeout(() => { btn.innerHTML = '<i class="mgc_save_line"></i> Save Template'; btn.style.background = ''; }, 2500);
    });
}

document.addEventListener('DOMContentLoaded', () => refreshBoth());
</script>
@endsection

@push('inline-scripts')
<script>
// Runs after Swal is loaded
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.ph-btn');
    if (!btn) return;
    const ph = btn.dataset.ph;

    // Insert at cursor if an editor textarea was last focused
    if (_lastEditor && document.body.contains(_lastEditor)) {
        const ta    = _lastEditor;
        const start = ta.selectionStart;
        const end   = ta.selectionEnd;
        ta.value    = ta.value.slice(0, start) + ph + ta.value.slice(end);
        ta.selectionStart = ta.selectionEnd = start + ph.length;
        ta.dispatchEvent(new Event('input'));
        ta.focus();
        Swal.fire({
            icon: 'success',
            title: 'Inserted!',
            text: ph + ' → inserted at cursor',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
        });
        return;
    }

    // No editor focused — fall back to clipboard copy
    const showToast = () => Swal.fire({
        icon: 'success',
        title: 'Copied!',
        text: ph,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true,
    });

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(ph).then(showToast).catch(() => fbCopy(ph, showToast));
    } else {
        fbCopy(ph, showToast);
    }
});

function fbCopy(text, cb) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); cb && cb(); } catch(e) {}
    document.body.removeChild(ta);
}

// Warn on page leave if unsaved
window.addEventListener('beforeunload', function (e) {
    if (_unsaved) { e.preventDefault(); e.returnValue = ''; }
});
</script>
@endpush
