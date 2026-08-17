@extends('layouts.vertical', [
    'title'         => 'ID Card Designer',
    'sub_title'     => 'Barangay IDs',
    'sub_title_url' => route('citizens.ids.index'),
    'tagline'       => 'Drag fields onto the card to design the front and back. No coding needed.',
])

@section('content')

@php
$bgFrontUrl = $tpl->bg_front ? asset(str_replace('public/', 'storage/', $tpl->bg_front)) : null;
$bgBackUrl  = $tpl->bg_back  ? asset(str_replace('public/', 'storage/', $tpl->bg_back))  : null;

// Placeholder palette — click a chip to drop the field on the active side.
$fieldGroups = [
    'Name'        => [['full_name','Full name'],['lname','Surname'],['fname','First name'],['mname','Middle name'],['suffix','Suffix']],
    'ID Details'  => [['id_no','ID number'],['date_issued','Date issued'],['valid_until','Valid until'],['since','Member since'],['precinct_no','Precinct no.'],['qrcode_value','QR text']],
    'Personal'    => [['bday','Birth date'],['gender','Gender']],
    'Contact'     => [['address','Address'],['contact','Contact #']],
    'Emergency'   => [['ic_name','IC name'],['ic_relationship','IC relationship'],['ic_contact','IC contact'],['ic_address','IC address']],
    'Barangay'    => [['brgy_name','Barangay'],['municipality','Municipality'],['province','Province'],['captain','Captain'],['captain_pos','Captain title']],
];

// Sample values for the live editor preview (print substitutes the real data).
$sample = [
    'full_name'=>'DELA CRUZ, JUAN S.','lname'=>'DELA CRUZ','fname'=>'Juan','mname'=>'Santos','suffix'=>'',
    'id_no'=>'EBT-001938','date_issued'=>'July 24, 2026','valid_until'=>'Jul 24, 2028','since'=>'June 2021',
    'precinct_no'=>'0224D','qrcode_value'=>'abc123','bday'=>'May 01, 1998','gender'=>'MALE',
    'address'=>'246 Circumferential St. Elsol Subd. Pob. Tres, Cab. Lag.','contact'=>'09761515249',
    'ic_name'=>'Princess Jyra Quirino','ic_relationship'=>'PARTNER','ic_contact'=>'09762680467',
    'ic_address'=>'246 Circumferential Elsol Subd. Barangay Tres Cabuyao City Laguna',
    'brgy_name'=>'Barangay Tres','municipality'=>'Cabuyao City','province'=>'Laguna',
    'captain'=>'Hon. Antonette M. Hain','captain_pos'=>'Punong Barangay',
];
@endphp

{{-- ── Top bar ─────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <div class="flex items-center gap-3 text-xs text-gray-400">
        <i class="mgc_cursor_3_line"></i>
        Click a field to add it, drag to move, drag the corner to resize.
        <span class="hidden md:inline text-gray-400">
            Selected: <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[9px]">← ↑ → ↓</kbd> nudge
            (<kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[9px]">Shift</kbd> = 10px) ·
            <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[9px]">Del</kbd> remove
        </span>
        <span id="unsaved-badge" class="hidden font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            <i class="mgc_edit_line text-xs"></i> Unsaved
        </span>
    </div>
    <div class="flex items-center gap-2">
        {{-- Preview as a real citizen so the layout is accurate --}}
        <div class="relative">
            <div class="flex items-center gap-1.5 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1">
                <i class="mgc_user_search_line text-gray-400 text-sm"></i>
                <input type="text" id="preview-search" autocomplete="off" placeholder="Preview as citizen…"
                       class="text-xs bg-transparent outline-none w-40 text-gray-700 dark:text-gray-200"
                       oninput="onPreviewSearch(this.value)">
                <button type="button" id="preview-clear" onclick="clearPreviewCitizen()"
                        class="hidden text-gray-400 hover:text-danger" title="Back to sample data">
                    <i class="mgc_close_line text-sm"></i>
                </button>
            </div>
            <div id="preview-results"
                 class="hidden absolute z-40 mt-1 w-64 max-h-64 overflow-y-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg text-xs"></div>
        </div>
        <span id="preview-tag" class="hidden text-[10px] px-2 py-0.5 rounded-full bg-success/10 text-success font-medium"></span>

        <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
            <input type="checkbox" id="snap-toggle" checked class="rounded"> Snap &amp; guides
        </label>
        <button onclick="saveTemplate()" id="save-btn" class="btn bg-primary text-white flex items-center gap-2 text-sm">
            <i class="mgc_save_line"></i> Save Template
        </button>
    </div>
</div>

<div class="grid grid-cols-12 gap-4">

    {{-- ── Left: field palette ─────────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        <div class="card p-3 sticky top-20">
            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-2">Add to card</p>

            <div class="space-y-3 max-h-[560px] overflow-y-auto pr-1">
                @foreach($fieldGroups as $group => $fields)
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 mb-1">{{ $group }}</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($fields as [$tag, $label])
                        <button type="button" class="add-field-btn text-[10px] px-1.5 py-1 rounded border border-gray-200 dark:border-gray-600
                                       text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition"
                                data-tag="{{ $tag }}" data-label="{{ $label }}">
                            + {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <div class="pt-2 border-t dark:border-gray-700 space-y-1">
                    <p class="text-[10px] font-semibold text-gray-400 mb-1">Media &amp; text</p>
                    <div class="flex flex-wrap gap-1">
                        <button type="button" onclick="addPhoto()" class="text-[10px] px-1.5 py-1 rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition">+ Photo</button>
                        <button type="button" onclick="addQr()" class="text-[10px] px-1.5 py-1 rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition">+ QR</button>
                        <button type="button" onclick="addSignature()" class="text-[10px] px-1.5 py-1 rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition">+ Signature</button>
                        <button type="button" onclick="addLabel()" class="text-[10px] px-1.5 py-1 rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition">+ Text</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Center: canvases ────────────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-6 xl:col-span-7 space-y-5">

        @foreach(['front' => 'Front', 'back' => 'Back'] as $side => $label)
        @php
            $bgUrl  = $side === 'front' ? $bgFrontUrl : $bgBackUrl;
            $orient = $side === 'front' ? ($tpl->orientation_front ?? 'landscape') : ($tpl->orientation_back ?? 'landscape');
        @endphp
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-xs font-bold {{ $side==='front' ? 'bg-primary/15 text-primary' : 'bg-warning/15 text-warning' }}">{{ $label[0] }}</span>
                    <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ $label }} Side</span>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Orientation --}}
                    <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 text-xs">
                        <button type="button" id="btn-landscape-{{ $side }}" onclick="setOrientation('{{ $side }}','landscape')"
                                class="px-2 py-1 {{ $orient==='landscape' ? 'bg-primary text-white' : 'text-gray-500' }}">Landscape</button>
                        <button type="button" id="btn-portrait-{{ $side }}" onclick="setOrientation('{{ $side }}','portrait')"
                                class="px-2 py-1 {{ $orient==='portrait' ? 'bg-primary text-white' : 'text-gray-500' }}">Portrait</button>
                    </div>
                    {{-- Background --}}
                    <button type="button" onclick="document.getElementById('bg-file-{{ $side }}').click()" class="text-xs text-gray-500 hover:text-primary flex items-center gap-1">
                        <i class="mgc_pic_line"></i> Background
                    </button>
                    <button type="button" id="bg-remove-{{ $side }}" onclick="removeBg('{{ $side }}')" class="{{ $bgUrl ? '' : 'hidden' }} text-xs text-danger hover:text-danger/70">Remove</button>
                    <input type="file" id="bg-file-{{ $side }}" accept="image/jpeg,image/png" class="hidden" onchange="uploadBg(this,'{{ $side }}')">
                </div>
            </div>

            {{-- Canvas: true card size, scaled up for editing --}}
            <div class="flex justify-center bg-gray-100 dark:bg-gray-900 rounded-lg p-4 overflow-auto">
                <div id="canvas-{{ $side }}" class="id-canvas relative shadow-lg"
                     data-side="{{ $side }}"
                     style="background-image:{{ $bgUrl ? "url('$bgUrl')" : 'none' }};background-size:cover;background-color:#fff;">
                    {{-- elements injected by JS --}}
                </div>
            </div>
            <p class="text-[10px] text-gray-400 text-center mt-2">
                <span id="dims-{{ $side }}">{{ $orient==='portrait' ? '2.125 × 3.375 in' : '3.375 × 2.125 in' }}</span> · CR80 · shown at 2× scale
            </p>
        </div>
        @endforeach

        {{-- ── Advanced: custom CSS + JS (Super Admin only) ──────────────── --}}
        @if($canAdvanced)
        <div class="card">
            <button type="button" onclick="toggleAdvanced()"
                    class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span class="flex items-center gap-2">
                    <i class="mgc_code_line text-primary"></i> Advanced — Custom CSS &amp; JS
                    <span class="text-[9px] uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-primary/10 text-primary font-bold">Super Admin</span>
                </span>
                <i id="adv-chevron" class="mgc_down_line text-gray-400"></i>
            </button>
            <div id="adv-body" class="hidden px-4 pb-4 space-y-4">
                <div>
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold flex items-center gap-2">
                        Custom CSS
                        <span class="text-gray-400 normal-case font-normal">— define classes like <code>.sample &#123; … &#125;</code>, then use <code>class="sample"</code> in a field</span>
                    </label>
                    <textarea id="css-shared" rows="6" spellcheck="false" oninput="markDirty()"
                              class="w-full mt-1 font-mono text-xs bg-gray-900 text-blue-300 p-3 rounded-lg border-0 outline-none resize-y">{{ $tpl->css_shared ?? '' }}</textarea>
                </div>
                <div>
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold flex items-center gap-2">
                        Custom JavaScript
                        <span class="text-gray-400 normal-case font-normal">— runs on the printed ID page</span>
                    </label>
                    <textarea id="js-shared" rows="6" spellcheck="false" oninput="markDirty()"
                              class="w-full mt-1 font-mono text-xs bg-gray-900 text-yellow-300 p-3 rounded-lg border-0 outline-none resize-y">{{ $tpl->js_shared ?? '' }}</textarea>
                </div>
                <p class="text-[10px] text-amber-600 dark:text-amber-400 flex items-start gap-1.5">
                    <i class="mgc_warning_line mt-0.5"></i>
                    Advanced mode: HTML you type in fields (tags, class, style) and this CSS/JS are applied as-is to the printed ID. Only use code you trust.
                </p>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Right: properties panel ─────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-3">
        <div class="card p-4 sticky top-20" id="props-panel">
            <div id="props-empty" class="text-center py-10 text-gray-400">
                <i class="mgc_cursor_line text-3xl mb-2 block opacity-30"></i>
                <p class="text-xs">Select a field on the card to edit it.</p>
            </div>

            <div id="props-body" class="hidden space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200" id="props-title">Field</p>
                    <button type="button" onclick="deleteSelected()" class="text-danger hover:text-danger/70 text-xs flex items-center gap-1">
                        <i class="mgc_delete_2_line"></i> Delete
                    </button>
                </div>

                {{-- Text content (text/field kinds) --}}
                <div id="prop-text-wrap">
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Text</label>
                    <textarea id="prop-text" rows="2" class="form-input text-xs mt-1 font-mono" oninput="applyProp('text', this.value)"></textarea>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Placeholders like <code>@{{address}}</code> fill in per citizen.
                        HTML allowed — e.g. <code>&lt;b&gt;GENDER&lt;/b&gt;: @{{gender}}</code>
                        or <code>&lt;div class="sample"&gt;…&lt;/div&gt;</code>.
                    </p>
                </div>

                {{-- Font size --}}
                <div id="prop-font-wrap">
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Font size</label>
                    <div class="flex items-center gap-2 mt-1">
                        <input type="range" id="prop-font" min="3" max="20" step="0.5" class="flex-1" oninput="applyProp('fontSize', parseFloat(this.value))">
                        <div class="flex items-center gap-1">
                            <input type="number" id="prop-font-num" min="1" max="72" step="0.5"
                                   class="form-input text-xs w-16 text-center"
                                   oninput="applyProp('fontSize', clampFont(parseFloat(this.value)))">
                            <span class="text-[10px] text-gray-400">px</span>
                        </div>
                    </div>
                </div>

                {{-- Style toggles --}}
                <div id="prop-style-wrap" class="flex gap-1">
                    <button type="button" id="prop-bold" onclick="toggleProp('bold')" class="flex-1 btn btn-sm border border-gray-200 dark:border-gray-600 font-bold">B</button>
                    <button type="button" id="prop-italic" onclick="toggleProp('italic')" class="flex-1 btn btn-sm border border-gray-200 dark:border-gray-600 italic">I</button>
                    <button type="button" id="prop-underline" onclick="toggleProp('underline')" class="flex-1 btn btn-sm border border-gray-200 dark:border-gray-600 underline">U</button>
                </div>

                {{-- Alignment --}}
                <div id="prop-align-wrap">
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Align</label>
                    <div class="flex gap-1 mt-1">
                        <button type="button" onclick="applyProp('align','left')"   class="prop-align flex-1 btn btn-sm border border-gray-200 dark:border-gray-600" data-align="left"><i class="mgc_align_left_line"></i></button>
                        <button type="button" onclick="applyProp('align','center')" class="prop-align flex-1 btn btn-sm border border-gray-200 dark:border-gray-600" data-align="center"><i class="mgc_align_center_line"></i></button>
                        <button type="button" onclick="applyProp('align','right')"  class="prop-align flex-1 btn btn-sm border border-gray-200 dark:border-gray-600" data-align="right"><i class="mgc_align_right_line"></i></button>
                    </div>
                </div>

                {{-- Color --}}
                <div id="prop-color-wrap" class="flex items-center gap-3">
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Color</label>
                    <input type="color" id="prop-color" value="#000000" class="w-8 h-8 rounded border-0 cursor-pointer bg-transparent" oninput="applyProp('color', this.value)">
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold ml-2">BG</label>
                    <input type="color" id="prop-bg" value="#ffffff" class="w-8 h-8 rounded border-0 cursor-pointer bg-transparent" oninput="applyProp('bg', this.value)">
                    <button type="button" onclick="applyProp('bg','')" class="text-[10px] text-gray-400 hover:text-danger">clear BG</button>
                </div>

                {{-- Padding (only meaningful when a background color is set) --}}
                <div id="prop-pad-wrap" class="hidden">
                    <label class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold flex justify-between">
                        <span>Padding inside background</span><span id="prop-pad-val" class="text-gray-500">3px</span>
                    </label>
                    <input type="range" id="prop-pad" min="0" max="12" step="0.5" value="3" class="w-full mt-1" oninput="applyProp('pad', parseFloat(this.value))">
                </div>

                {{-- Position / size --}}
                <div class="grid grid-cols-4 gap-2">
                    <div><label class="text-[10px] text-gray-400">X</label><input type="number" id="prop-x" class="form-input text-xs" oninput="applyProp('x', parseFloat(this.value)||0)"></div>
                    <div><label class="text-[10px] text-gray-400">Y</label><input type="number" id="prop-y" class="form-input text-xs" oninput="applyProp('y', parseFloat(this.value)||0)"></div>
                    <div><label id="prop-w-label" class="text-[10px] text-gray-400">W</label><input type="number" id="prop-w" class="form-input text-xs" oninput="applyProp('w', parseFloat(this.value)||10)"></div>
                    <div id="prop-h-wrap" class="hidden"><label class="text-[10px] text-gray-400">H</label><input type="number" id="prop-h" class="form-input text-xs" oninput="applyProp('h', parseFloat(this.value)||10)"></div>
                </div>

                {{-- Photo sizing helpers (photo only) --}}
                <div id="prop-photo-wrap" class="hidden flex items-center justify-between gap-2">
                    <label class="flex items-center gap-1.5 text-[11px] text-gray-500 cursor-pointer select-none">
                        <input type="checkbox" id="prop-lock" class="rounded" onchange="markDirty()"> Lock W:H ratio
                    </label>
                    <button type="button" onclick="makeSquare()" class="text-[10px] px-2 py-1 rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition">
                        Make 1:1
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
const SAVE_URL      = @js(route('citizens.ids.template.save'));
const UPLOAD_BG_URL = @js(route('citizens.ids.template.upload-bg'));
const REMOVE_BG_URL = @js(route('citizens.ids.template.remove-bg'));
const SEARCH_URL    = @js(route('citizens.search'));
const PREVIEW_URL   = @js(url('citizens/ids/template/preview-data'));   // + /{id}
const CSRF          = @js(csrf_token());
const SAMPLE_DATA   = @js($sample);            // built-in fallback values
let   SAMPLE        = Object.assign({}, SAMPLE_DATA);   // active preview values (mutable)

// Builds a double-brace placeholder string (braces split so Blade never parses them).
const OB = '{' + '{', CB = '}' + '}';
function TAG(name) { return OB + name + CB; }

// Card pixel size at CR80 96dpi; canvas is rendered at SCALE× for editing comfort.
const SCALE = 2;
const DIMS = {
    landscape: { w: 324, h: 204 },
    portrait:  { w: 204, h: 324 },
};

// ── State ──────────────────────────────────────────────────────────────
const state = {
    front: { orientation: @js($tpl->orientation_front ?? 'landscape'), bg: @js($bgFrontUrl), els: @js($tpl->layout_front ?? []) },
    back:  { orientation: @js($tpl->orientation_back  ?? 'landscape'), bg: @js($bgBackUrl),  els: @js($tpl->layout_back  ?? []) },
};
let selected = null;   // { side, id }
let _uid = Date.now();
let _dirty = false;

function markDirty() { _dirty = true; document.getElementById('unsaved-badge').classList.remove('hidden'); }
function clearDirty() { _dirty = false; document.getElementById('unsaved-badge').classList.add('hidden'); }

// ── Canvas sizing ──────────────────────────────────────────────────────
function sizeCanvas(side) {
    const d = DIMS[state[side].orientation];
    const c = document.getElementById('canvas-' + side);
    c.style.width  = (d.w * SCALE) + 'px';
    c.style.height = (d.h * SCALE) + 'px';
}

function activeSideOf(el) { return el.closest('.id-canvas')?.dataset.side; }

// ── Add elements ───────────────────────────────────────────────────────
function currentSide() {
    // Add to whichever side owns the selection, else front.
    return selected?.side ?? 'front';
}
function pushEl(side, el) {
    el.id = 'e' + (++_uid);
    state[side].els.push(el);
    renderSide(side);
    select(side, el.id);
    markDirty();
}
function addField(tag, label) {
    pushEl(currentSide(), { id:'', kind:'field', text:TAG(tag), x:10, y:10, w:120, fontSize:6, align:'left', bold:false });
}
document.querySelectorAll('.add-field-btn').forEach(b => {
    b.addEventListener('click', () => addField(b.dataset.tag, b.dataset.label));
});
function addLabel() {
    pushEl(currentSide(), { id:'', kind:'text', text:'LABEL:', x:10, y:10, w:60, fontSize:6, align:'left', bold:true });
}
function addPhoto() {
    pushEl(currentSide(), { id:'', kind:'photo', x:250, y:36, w:60, h:62 });
}
function addQr() {
    pushEl(currentSide(), { id:'', kind:'qr', x:12, y:12, w:100 });
}
function addSignature() {
    pushEl(currentSide(), { id:'', kind:'signature', x:40, y:150, w:70, h:24 });
}

// ── Render ─────────────────────────────────────────────────────────────
function elById(side, id) { return state[side].els.find(e => e.id === id); }

function fillSample(text) {
    let out = String(text || '');
    Object.entries(SAMPLE).forEach(([k,v]) => { out = out.split(TAG(k)).join(v); });
    return out;
}

function renderSide(side) {
    const c = document.getElementById('canvas-' + side);
    c.querySelectorAll('.id-el').forEach(n => n.remove());

    state[side].els.forEach(el => {
        const node = document.createElement('div');
        node.className = 'id-el';
        node.dataset.id = el.id;
        node.style.left   = (el.x * SCALE) + 'px';
        node.style.top    = (el.y * SCALE) + 'px';
        node.style.width  = (el.w * SCALE) + 'px';

        if (el.kind === 'photo') {
            node.style.height = ((el.h || 62) * SCALE) + 'px';
            node.innerHTML = SAMPLE.photo_url
                ? '<img src="'+SAMPLE.photo_url+'" style="width:100%;height:100%;object-fit:cover;display:block;">'
                : '<div class="el-photo">PHOTO</div>';
        } else if (el.kind === 'qr') {
            node.style.height = (el.w * SCALE) + 'px';
            node.innerHTML = SAMPLE.qr_url
                ? '<img src="'+SAMPLE.qr_url+'" style="width:100%;height:100%;display:block;">'
                : '<div class="el-qr">QR</div>';
        } else if (el.kind === 'signature') {
            node.style.height = ((el.h || 24) * SCALE) + 'px';
            node.innerHTML = '<div class="el-sig">SIGNATURE</div>';
        } else {
            node.style.fontSize   = (el.fontSize * SCALE) + 'px';
            node.style.textAlign  = el.align || 'left';
            node.style.fontWeight = el.bold ? 'bold' : 'normal';
            node.style.fontStyle  = el.italic ? 'italic' : 'normal';
            node.style.textDecoration = el.underline ? 'underline' : 'none';
            node.style.color      = el.color || '#000';
            if (el.bg) {
                node.style.background = el.bg;
                const pad = (el.pad != null ? el.pad : 3) * SCALE;
                node.style.padding = pad + 'px';
                node.style.boxSizing = 'border-box';
            }
            // Advanced mode: render field text as HTML so <b>, class, style show live.
            node.innerHTML = fillSample(el.text) || '<span style="opacity:.4">(empty)</span>';
        }

        if (selected && selected.side === side && selected.id === el.id) node.classList.add('selected');

        const handle = document.createElement('div');
        handle.className = 'el-handle';
        node.appendChild(handle);

        c.appendChild(node);
        wireDrag(node, side, el, handle);
    });
}

function renderAll() { sizeCanvas('front'); sizeCanvas('back'); renderSide('front'); renderSide('back'); }

// ── Selection + properties ─────────────────────────────────────────────
function select(side, id) {
    selected = { side, id };
    document.querySelectorAll('.id-el').forEach(n => n.classList.remove('selected'));
    const node = document.querySelector('#canvas-'+side+' .id-el[data-id="'+id+'"]');
    if (node) node.classList.add('selected');
    syncPanel();
}
function deselect() { selected = null; document.querySelectorAll('.id-el').forEach(n=>n.classList.remove('selected')); syncPanel(); }

function syncPanel() {
    const empty = document.getElementById('props-empty');
    const body  = document.getElementById('props-body');
    if (!selected) { empty.classList.remove('hidden'); body.classList.add('hidden'); return; }
    empty.classList.add('hidden'); body.classList.remove('hidden');

    const el = elById(selected.side, selected.id);
    if (!el) { deselect(); return; }

    const isText = el.kind === 'text' || el.kind === 'field';
    ['prop-text-wrap','prop-font-wrap','prop-style-wrap','prop-align-wrap','prop-color-wrap']
        .forEach(id => document.getElementById(id).classList.toggle('hidden', !isText));

    document.getElementById('props-title').textContent =
        el.kind === 'photo' ? 'Photo' : el.kind === 'qr' ? 'QR code' :
        el.kind === 'signature' ? 'Signature' : el.kind === 'text' ? 'Text' : 'Field';

    if (isText) {
        document.getElementById('prop-text').value = el.text || '';
        document.getElementById('prop-font').value = el.fontSize || 6;
        document.getElementById('prop-font-num').value = el.fontSize || 6;
        document.getElementById('prop-bold').classList.toggle('bg-primary', !!el.bold);
        document.getElementById('prop-bold').classList.toggle('text-white', !!el.bold);
        document.getElementById('prop-italic').classList.toggle('bg-primary', !!el.italic);
        document.getElementById('prop-italic').classList.toggle('text-white', !!el.italic);
        document.getElementById('prop-underline').classList.toggle('bg-primary', !!el.underline);
        document.getElementById('prop-underline').classList.toggle('text-white', !!el.underline);
        document.querySelectorAll('.prop-align').forEach(b => {
            const on = b.dataset.align === (el.align || 'left');
            b.classList.toggle('bg-primary', on); b.classList.toggle('text-white', on);
        });
        document.getElementById('prop-color').value = el.color || '#000000';
        document.getElementById('prop-bg').value = el.bg || '#ffffff';

        // Padding control only matters when a background color is set.
        const hasBg = !!el.bg;
        document.getElementById('prop-pad-wrap').classList.toggle('hidden', !hasBg);
        if (hasBg) {
            const pad = el.pad != null ? el.pad : 3;
            document.getElementById('prop-pad').value = pad;
            document.getElementById('prop-pad-val').textContent = pad + 'px';
        }
    }
    document.getElementById('prop-x').value = Math.round(el.x);
    document.getElementById('prop-y').value = Math.round(el.y);
    document.getElementById('prop-w').value = Math.round(el.w);

    // Height input: photo and signature both have independent W/H.
    const hasHeight = el.kind === 'photo' || el.kind === 'signature';
    document.getElementById('prop-h-wrap').classList.toggle('hidden', !hasHeight);
    if (hasHeight) {
        document.getElementById('prop-h').value = Math.round(el.h || el.w);
    }
    // A QR is always square: W = H. Relabel the size field to make that clear.
    document.getElementById('prop-w-label').textContent = el.kind === 'qr' ? 'Size (1:1)' : 'W';
    // Lock-ratio / Make-1:1 helpers are photo-only.
    document.getElementById('prop-photo-wrap').classList.toggle('hidden', el.kind !== 'photo');
}

function applyProp(key, val) {
    if (!selected) return;
    const el = elById(selected.side, selected.id);
    if (!el) return;

    // Photo with locked ratio: changing W or H keeps the other in proportion.
    if (el.kind === 'photo' && photoLocked() && (key === 'w' || key === 'h')) {
        const oldW = el.w, oldH = el.h || el.w;
        if (key === 'w' && oldW) el.h = Math.round((oldH * (val / oldW)) * 10) / 10;
        if (key === 'h' && oldH) el.w = Math.round((oldW * (val / oldH)) * 10) / 10;
    }

    el[key] = val;
    if (key === 'fontSize') {
        // Keep the slider and the number box in sync with the applied value.
        document.getElementById('prop-font').value = val;
        document.getElementById('prop-font-num').value = val;
    }
    if (key === 'pad')      document.getElementById('prop-pad-val').textContent = val + 'px';
    renderSide(selected.side);
    select(selected.side, selected.id);
    markDirty();
}
// Keep a typed font size sane (1–72px); empty/NaN falls back to the current value.
function clampFont(v) {
    if (isNaN(v)) {
        const el = selected && elById(selected.side, selected.id);
        return el ? (el.fontSize || 6) : 6;
    }
    return Math.min(72, Math.max(1, v));
}
function photoLocked() { return document.getElementById('prop-lock')?.checked; }
function makeSquare() {
    if (!selected) return;
    const el = elById(selected.side, selected.id);
    if (!el || el.kind !== 'photo') return;
    el.h = el.w;
    renderSide(selected.side);
    select(selected.side, selected.id);
    markDirty();
}
function toggleProp(key) {
    if (!selected) return;
    const el = elById(selected.side, selected.id);
    if (!el) return;
    applyProp(key, !el[key]);
}
function deleteSelected() {
    if (!selected) return;
    state[selected.side].els = state[selected.side].els.filter(e => e.id !== selected.id);
    const side = selected.side; deselect(); renderSide(side); markDirty();
}

// Move the selected element by (dx,dy) card-pixels, clamped inside the card.
function nudge(dx, dy) {
    if (!selected) return;
    const el = elById(selected.side, selected.id);
    if (!el) return;
    const dim = DIMS[state[selected.side].orientation];
    const w = el.w || 0;
    const h = (el.kind === 'photo' || el.kind === 'signature') ? (el.h || el.w) :
              (el.kind === 'qr') ? el.w : 0;
    el.x = Math.min(Math.max(0, Math.round((el.x + dx) * 10) / 10), Math.max(0, dim.w - w));
    el.y = Math.min(Math.max(0, Math.round((el.y + dy) * 10) / 10), Math.max(0, dim.h - h));
    renderSide(selected.side);
    select(selected.side, selected.id);
    markDirty();
}

// ── Drag + resize with Canva-style snapping ────────────────────────────
function snapEnabled() { return document.getElementById('snap-toggle').checked; }

function wireDrag(node, side, el, handle) {
    // move
    node.addEventListener('mousedown', (ev) => {
        if (ev.target === handle) return;         // resize handled below
        ev.preventDefault();
        select(side, el.id);
        const startX = ev.clientX, startY = ev.clientY, ox = el.x, oy = el.y;
        const onMove = (e) => {
            let nx = ox + (e.clientX - startX) / SCALE;
            let ny = oy + (e.clientY - startY) / SCALE;
            [nx, ny] = maybeSnap(side, el, nx, ny);
            el.x = Math.max(0, Math.round(nx*10)/10);
            el.y = Math.max(0, Math.round(ny*10)/10);
            renderSide(side); select(side, el.id);
        };
        const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); clearGuides(side); markDirty(); };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
    // resize via corner handle
    handle.addEventListener('mousedown', (ev) => {
        ev.preventDefault(); ev.stopPropagation();
        select(side, el.id);
        const startX = ev.clientX, startY = ev.clientY, ow = el.w, oh = el.h || el.w;
        const onMove = (e) => {
            let nw = Math.max(8, ow + (e.clientX - startX) / SCALE);
            el.w = Math.round(nw * 10) / 10;

            if (el.kind === 'photo' && photoLocked()) {
                // keep original W:H ratio
                el.h = Math.round((oh * (el.w / ow)) * 10) / 10;
            } else if (el.kind === 'photo' || el.kind === 'signature') {
                // free: height follows vertical drag independently
                let nh = Math.max(8, oh + (e.clientY - startY) / SCALE);
                el.h = Math.round(nh * 10) / 10;
            }
            renderSide(side); select(side, el.id);
        };
        const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); markDirty(); };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
}

// Snap to other elements' edges + grid; draw guide lines.
function maybeSnap(side, self, x, y) {
    if (!snapEnabled()) return [x, y];
    const TOL = 3;
    clearGuides(side);
    const others = state[side].els.filter(e => e.id !== self.id);
    let gx = null, gy = null;

    others.forEach(o => {
        if (Math.abs(o.x - x) <= TOL) { x = o.x; gx = o.x; }
        if (Math.abs((o.x+o.w) - (x+self.w)) <= TOL) { x = o.x + o.w - self.w; gx = o.x + o.w; }
        if (Math.abs(o.y - y) <= TOL) { y = o.y; gy = o.y; }
    });
    // grid fallback (2px)
    x = Math.round(x/2)*2; y = Math.round(y/2)*2;
    if (gx !== null) drawGuide(side, 'v', gx);
    if (gy !== null) drawGuide(side, 'h', gy);
    return [x, y];
}
function drawGuide(side, dir, pos) {
    const c = document.getElementById('canvas-' + side);
    const g = document.createElement('div');
    g.className = 'snap-guide ' + dir;
    if (dir === 'v') { g.style.left = (pos*SCALE)+'px'; }
    else { g.style.top = (pos*SCALE)+'px'; }
    c.appendChild(g);
}
function clearGuides(side) {
    document.querySelectorAll('#canvas-'+side+' .snap-guide').forEach(n=>n.remove());
}

// Click empty canvas = deselect
['front','back'].forEach(side => {
    document.getElementById('canvas-'+side).addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('id-canvas')) deselect();
    });
});

// ── Orientation ────────────────────────────────────────────────────────
function setOrientation(side, o) {
    state[side].orientation = o;
    document.getElementById('btn-landscape-'+side).className = 'px-2 py-1 ' + (o==='landscape'?'bg-primary text-white':'text-gray-500');
    document.getElementById('btn-portrait-'+side).className  = 'px-2 py-1 ' + (o==='portrait'?'bg-primary text-white':'text-gray-500');
    document.getElementById('dims-'+side).textContent = o==='portrait' ? '2.125 × 3.375 in' : '3.375 × 2.125 in';
    sizeCanvas(side); renderSide(side); markDirty();
}

// ── Background ─────────────────────────────────────────────────────────
function uploadBg(input, side) {
    if (!input.files[0]) return;
    const fd = new FormData();
    fd.append('bg', input.files[0]); fd.append('side', side); fd.append('_token', CSRF);
    fetch(UPLOAD_BG_URL, { method:'POST', body:fd }).then(r=>r.json()).then(data => {
        state[side].bg = data.url;
        document.getElementById('canvas-'+side).style.backgroundImage = "url('"+data.url+"')";
        document.getElementById('bg-remove-'+side).classList.remove('hidden');
    });
    input.value = '';
}
function removeBg(side) {
    const fd = new FormData(); fd.append('side', side); fd.append('_token', CSRF);
    fetch(REMOVE_BG_URL, { method:'POST', body:fd }).then(()=> {
        state[side].bg = null;
        document.getElementById('canvas-'+side).style.backgroundImage = 'none';
        document.getElementById('bg-remove-'+side).classList.add('hidden');
    });
}

// ── Save ───────────────────────────────────────────────────────────────
function saveTemplate() {
    const btn = document.getElementById('save-btn');
    btn.disabled = true; btn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Saving…';

    // strip transient ids? keep them — harmless. Send clean payload.
    const clean = els => els.map(({id, ...rest}) => rest);

    fetch(SAVE_URL, {
        method:'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            orientation_front: state.front.orientation,
            orientation_back:  state.back.orientation,
            layout_front: clean(state.front.els),
            layout_back:  clean(state.back.els),
            css_shared: cssBox ? cssBox.value : undefined,
            js_shared:  document.getElementById('js-shared')?.value,
        }),
    }).then(r=>r.json()).then(()=> {
        clearDirty();
        btn.innerHTML = '<i class="mgc_check_line"></i> Saved!'; btn.style.background = '#0acf97';
        setTimeout(()=> { btn.disabled=false; btn.innerHTML='<i class="mgc_save_line"></i> Save Template'; btn.style.background=''; }, 1800);
    }).catch(()=> {
        btn.disabled=false; btn.innerHTML='<i class="mgc_close_line"></i> Failed'; btn.style.background='#fa5c7c';
        setTimeout(()=> { btn.innerHTML='<i class="mgc_save_line"></i> Save Template'; btn.style.background=''; }, 2200);
    });
}

// Ensure loaded elements have ids (server strips them on save).
['front','back'].forEach(side => state[side].els.forEach(e => { if (!e.id) e.id = 'e'+(++_uid); }));

// ── Preview-as-citizen ─────────────────────────────────────────────────
// Search citizens and, on select, replace the preview SAMPLE with that
// citizen's real values fetched from the server (same map the print uses).
let _searchTimer = null;
function onPreviewSearch(q) {
    clearTimeout(_searchTimer);
    const box = document.getElementById('preview-results');
    if (!q || q.trim().length < 2) { box.classList.add('hidden'); box.innerHTML = ''; return; }
    _searchTimer = setTimeout(() => {
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q.trim()))
            .then(r => r.json())
            .then(list => {
                if (!Array.isArray(list) || !list.length) {
                    box.innerHTML = '<div class="px-3 py-2 text-gray-400">No citizens found.</div>';
                } else {
                    box.innerHTML = list.map(c =>
                        '<button type="button" class="w-full text-left px-3 py-2 hover:bg-primary/10 flex flex-col" '
                        + 'onclick="pickPreviewCitizen('+c.id+', '+JSON.stringify(c.name).replace(/"/g,'&quot;')+')">'
                        + '<span class="font-medium text-gray-700 dark:text-gray-200">'+escapeHtml(c.name)+'</span>'
                        + '<span class="text-[10px] text-gray-400">'+escapeHtml(c.address||'')+'</span></button>'
                    ).join('');
                }
                box.classList.remove('hidden');
            })
            .catch(() => { box.classList.add('hidden'); });
    }, 250);
}

function pickPreviewCitizen(id, name) {
    document.getElementById('preview-results').classList.add('hidden');
    document.getElementById('preview-search').value = name;
    fetch(PREVIEW_URL + '/' + id)
        .then(r => r.json())
        .then(vals => {
            SAMPLE = vals;                       // real citizen values (incl. photo_url, qr_url)
            const tag = document.getElementById('preview-tag');
            tag.textContent = 'Previewing: ' + name;
            tag.classList.remove('hidden');
            document.getElementById('preview-clear').classList.remove('hidden');
            renderSide('front'); renderSide('back');
        })
        .catch(() => alert('Could not load that citizen.'));
}

function clearPreviewCitizen() {
    SAMPLE = Object.assign({}, SAMPLE_DATA);     // back to built-in sample values
    document.getElementById('preview-search').value = '';
    document.getElementById('preview-results').classList.add('hidden');
    document.getElementById('preview-tag').classList.add('hidden');
    document.getElementById('preview-clear').classList.add('hidden');
    renderSide('front'); renderSide('back');
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// Close the results dropdown on outside click.
document.addEventListener('click', (e) => {
    if (!e.target.closest('#preview-search') && !e.target.closest('#preview-results')) {
        document.getElementById('preview-results').classList.add('hidden');
    }
});

window.addEventListener('beforeunload', (e) => { if (_dirty) { e.preventDefault(); e.returnValue=''; } });

// ── Keyboard controls for the selected field ───────────────────────────
// Arrows nudge 1px (Shift = 10px). Delete/Backspace removes. Esc deselects.
// Ignored while typing in an input/textarea so the property fields still work.
document.addEventListener('keydown', (e) => {
    const t = e.target;
    const typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
    if (typing || !selected) return;

    const step = e.shiftKey ? 10 : 1;
    switch (e.key) {
        case 'ArrowUp':    nudge(0, -step); break;
        case 'ArrowDown':  nudge(0,  step); break;
        case 'ArrowLeft':  nudge(-step, 0); break;
        case 'ArrowRight': nudge( step, 0); break;
        case 'Delete':
        case 'Backspace':  deleteSelected(); break;
        case 'Escape':     deselect(); return;   // no preventDefault needed
        default: return;                          // ignore other keys
    }
    e.preventDefault();   // stop arrows from scrolling the page
});

// ── Advanced panel ─────────────────────────────────────────────────────
function toggleAdvanced() {
    const body = document.getElementById('adv-body');
    const chev = document.getElementById('adv-chevron');
    const open = body.classList.contains('hidden');
    body.classList.toggle('hidden', !open);
    chev.classList.toggle('mgc_down_line', !open);
    chev.classList.toggle('mgc_up_line', open);
}

// Live-apply the user's custom CSS to both canvases while designing.
// The CSS box only exists for Super Admins; guard for everyone else.
const cssBox = document.getElementById('css-shared');
function applyUserCss() {
    if (!cssBox) return;
    let tag = document.getElementById('user-css-live');
    if (!tag) { tag = document.createElement('style'); tag.id = 'user-css-live'; document.head.appendChild(tag); }
    // Scope the CSS to the editor canvases so it can't wreck the admin chrome.
    const raw = cssBox.value || '';
    tag.textContent = raw.replace(/(^|\})\s*([^{}@]+)\{/g, (m, close, sel) =>
        close + ' ' + sel.split(',').map(s => '.id-canvas ' + s.trim()).join(', ') + ' {');
}
if (cssBox) { cssBox.addEventListener('input', applyUserCss); applyUserCss(); }

renderAll();
</script>
<style>
.id-canvas { image-rendering:auto; }
.id-el {
    position:absolute; box-sizing:border-box; cursor:move; white-space:normal;
    font-family:"Arial Narrow", Arial, sans-serif; line-height:1.15; overflow:visible;
    outline:1px dashed transparent;
}
.id-el:hover { outline-color:rgba(114,124,245,.5); }
.id-el.selected { outline:1.5px solid #727cf5; z-index:5; }
.id-el .el-handle {
    position:absolute; right:-4px; bottom:-4px; width:9px; height:9px;
    background:#727cf5; border:1.5px solid #fff; border-radius:2px; cursor:nwse-resize; display:none;
}
.id-el.selected .el-handle { display:block; }
.el-photo, .el-qr, .el-sig {
    width:100%; height:100%; display:flex; align-items:center; justify-content:center;
    font-size:9px; font-weight:bold; color:#64748b; background:rgba(148,163,184,.25);
    border:1px dashed #94a3b8; letter-spacing:1px;
}
.el-sig {
    color:#0891b2; background:rgba(6,182,212,.12); border-color:#22d3ee;
    font-size:7px; letter-spacing:.5px;
}
.snap-guide { position:absolute; background:#ec4899; z-index:20; pointer-events:none; }
.snap-guide.v { top:0; bottom:0; width:1px; }
.snap-guide.h { left:0; right:0; height:1px; }
</style>
@endpush
