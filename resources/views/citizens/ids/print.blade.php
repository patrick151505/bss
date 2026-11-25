<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay ID — {{ $citizenId->citizen?->full_name }}</title>
@php
    $c        = $citizenId->citizen;
    $setting  = \App\Models\Setting::instance();
    $profile  = $c?->profile ? asset(str_replace('public/', 'storage/', $c->profile)) : null;
    $qrValue  = $c?->qrcode ?? $setting->formatCitizenId($c?->id);
    // QR generated locally (no internet) as an inline SVG data URI.
    $qrUrl    = \App\Support\Qr::svgDataUri($qrValue, 200);

    $lname   = strtoupper($c?->lname ?? '');
    $fname   = $c?->fname ?? '';
    $mname   = $c?->mname ?? '';
    $suffix  = $c?->suffix ?? '';
    $midInit = $mname ? strtoupper(substr($mname, 0, 1)) . '.' : '';
    $fullName       = trim(implode(' ', array_filter([$fname, $midInit, $lname, $suffix])));
    $fullNameFormal = trim(($lname ? $lname . ',' : '') . ' ' . trim(implode(' ', array_filter([$fname, $midInit, $suffix]))));

    $photoUrl   = $profile ?? 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&size=200&background=94a3b8&color=fff';
    $qrImgTag   = '<img src="' . $qrUrl . '" style="width:100%;height:100%;display:block;" alt="QR">';

    // {{signature_img}} placeholder — filled after upload via JS; server sends empty tag initially.
    // Image fits inside its box (max-height/width 100%) so the designer-set box size controls it.
    $sigImgStyle = 'max-height:100%;max-width:100%;height:auto;object-fit:contain;';
    $sigInitTag = $citizenId->sig_front
        ? '<img src="' . asset(str_replace('public/', 'storage/', $citizenId->sig_front)) . '" id="sig-img-inner" style="display:block;' . $sigImgStyle . '">'
        : '<img id="sig-img-inner" style="display:none;' . $sigImgStyle . '">';

    $idNo       = $setting->formatCitizenId($c?->id);
    $bday       = $c?->bday?->format('M d, Y') ?? '—';
    $gender     = match((int)($c?->gender ?? 0)) { 1=>'Male', 2=>'Female', default=>'—' };
    $address    = $c?->full_address ?: '—';
    $contact    = $c?->contact ?? '—';
    $since      = $c?->year_stay?->format('Y') ?? '—';
    $validUntil = $citizenId->valid_until?->format('M d, Y') ?? '—';
    $dateIssued = $citizenId->created_at?->format('F d, Y') ?? '—';
    // NOTE: column is spelled `pricinct_no` in eb_citizen (existing typo).
    $precinctNo = $c?->pricinct_no ?: '—';
    $brgyName   = $setting->barangay_name ?? 'BARANGAY';
    $municity   = $setting->municipality ?? '';
    $province   = $setting->province ?? '';
    $captain    = $setting->captain_name ?? '—';
    $icName     = $c?->ic_fullname ?? '—';
    $icAddress  = $c?->ic_address ?? '—';
    $icContact  = $c?->ic_contact ?? '—';
    $icRel      = $c?->ic_relationship ?? '—';
    $sigUrl     = $citizenId->sig_front
                    ? asset(str_replace('public/', 'storage/', $citizenId->sig_front))
                    : null;

    $tpl        = $templates['front'] ?? null;
    // Rebuild from the saved designer layout when there is one, so the print
    // always matches the current compiler (and fixes reach templates saved
    // earlier); fall back to the stored HTML for layout-less templates.
    $frontHtml  = trim(! empty($tpl?->layout_front) ? \App\Models\CitizenIdTemplate::compileLayout($tpl->layout_front) : ($tpl?->html_front ?? ''));
    $backHtml   = trim(! empty($tpl?->layout_back)  ? \App\Models\CitizenIdTemplate::compileLayout($tpl->layout_back)  : ($tpl?->html_back ?? ''));
    $sharedCss  = $tpl?->css_shared ?? '';
    $sharedJs   = $tpl?->js_shared ?? '';
    $useTemplate = $frontHtml !== '' || $backHtml !== '';

    if ($useTemplate) {
        // Same placeholder map the designer preview uses, plus print-only media
        // tags (qr_img / signature_img) that need live substitution here.
        $vals = \App\Models\CitizenId::placeholderValues(
            $c,
            $citizenId->created_at,
            $citizenId->valid_until,
        );
        unset($vals['qr_url']);   // not a template placeholder
        $vals['qr_img'] = $qrImgTag;
        // signature_img is just the inner <img>; the designer's compiled HTML
        // provides the positioned #sig-overlay wrapper around it. If a template
        // was built the old way (bare tag, no wrapper), fall back to wrapping.
        $vals['signature_img'] = str_contains($frontHtml . $backHtml, 'id="sig-overlay"')
            ? $sigInitTag
            : '<div class="signature" id="sig-overlay" style="position:absolute;top:158px;left:40px;">' . $sigInitTag . '</div>';

        // Replace all fixed placeholders first ({{Municipality}}, {{ address }}, {{Barangay}} all work)
        $frontHtml = \App\Models\CitizenId::fillPlaceholders($frontHtml, $vals);
        $backHtml  = \App\Models\CitizenId::fillPlaceholders($backHtml, $vals);

        // Replace any {{qr_img_N}} dynamically — supports any pixel size.
        // QR is generated locally as an SVG data URI (no network dependency).
        $qrReplace = function(string $html) use ($qrValue): string {
            return preg_replace_callback('/\{\{qr_img_(\d+)\}\}/', function($m) use ($qrValue) {
                $px  = (int) $m[1];
                $uri = \App\Support\Qr::svgDataUri($qrValue, $px);
                return '<img src="' . $uri . '" width="' . $px . '" height="' . $px . '" style="display:block;width:' . $px . 'px;height:' . $px . 'px;" alt="QR">';
            }, $html);
        };
        $frontHtml = $qrReplace($frontHtml);
        $backHtml  = $qrReplace($backHtml);

        $frontBgUrl  = $tpl?->bg_front ? asset(str_replace('public/', 'storage/', $tpl->bg_front)) : '';
        $frontOrient = $tpl?->orientation_front ?? 'landscape';
        $backBgUrl   = $tpl?->bg_back  ? asset(str_replace('public/', 'storage/', $tpl->bg_back))  : '';
        $backOrient  = $tpl?->orientation_back ?? 'landscape';
        // CR80 card, same as the designer canvas (324 × 204 px at 96 dpi).
        $frontW = ($frontOrient === 'portrait') ? '2.125in' : '3.375in';
        $frontH = ($frontOrient === 'portrait') ? '3.375in' : '2.125in';
        $backW  = ($backOrient  === 'portrait') ? '2.125in' : '3.375in';
        $backH  = ($backOrient  === 'portrait') ? '3.375in' : '2.125in';
    }

    // ── Citizen tags + flags → CSS classes on the cards container ──────────
    // Lets custom CSS style a card by membership, e.g. .tag-toda { … } or .flag-pwd { … }.
    $slug = fn ($s) => 'tag-' . \Illuminate\Support\Str::slug($s);
    $citizenClasses = collect();
    foreach (($c?->tags ?? collect()) as $tag) {
        if ($tag->name) $citizenClasses->push($slug($tag->name));
    }
    // Boolean flags on the citizen record.
    if ($c?->is_pwd)         $citizenClasses->push('flag-pwd');
    if ($c?->voters)         $citizenClasses->push('flag-voter');
    if ($c?->is_soloparents) $citizenClasses->push('flag-solo-parent');
    if (($c?->age ?? 0) >= 60) $citizenClasses->push('flag-senior');
    if (($c?->age ?? 999) < 18) $citizenClasses->push('flag-minor');
    if (($c?->gender ?? 0) == 1) $citizenClasses->push('flag-male');
    if (($c?->gender ?? 0) == 2) $citizenClasses->push('flag-female');

    $citizenClasses = $citizenClasses->unique()->implode(' ');
@endphp

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    background: #e5e7eb;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    align-items: flex-start;
    padding: 20px;
    gap: 20px;
    min-height: 100vh;
}

/* ─── Controller panel ──────────────────────────────────── */
.controller {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 8px rgba(0,0,0,.08);
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-width: 130px;
    align-self: flex-start;
    margin-top: 8px;
}
.ctrl-meta {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 12px;
    display: flex; flex-direction: column; gap: 3px;
}
.back-link {
    font-size: 11px; color: #4f46e5; text-decoration: none; margin-bottom: 4px;
}
.back-link:hover { text-decoration: underline; }
.meta-name { font-size: 13px; font-weight: 700; color: #1f2937; line-height: 1.2; }
.meta-id   { font-size: 11px; color: #6b7280; font-family: monospace; }
.meta-valid{ font-size: 11px; color: #6b7280; margin-top: 2px; }
.meta-valid b { color: #059669; font-size: 11px; text-transform: none; letter-spacing: 0; }
.controller b {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #6b7280;
    display: block;
    margin-bottom: 4px;
}
.ctrl-grid {
    display: grid;
    grid-template-columns: 32px 32px 32px;
    grid-template-rows: 32px 32px 32px;
    gap: 2px;
}
.ctrl-grid .move {
    width: 32px; height: 32px;
    background: #f3f4f6; border: 1px solid #d1d5db;
    border-radius: 6px; font-size: 14px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    user-select: none;
}
.ctrl-grid .move:hover { background: #e0e7ff; border-color: #818cf8; }
.ctrl-grid .move:active { background: #c7d2fe; }
.ctrl-grid .spacer { width: 32px; height: 32px; }

.resize-row {
    display: flex; gap: 4px; align-items: center;
}
.resize-row .resize {
    width: 32px; height: 28px;
    background: #f3f4f6; border: 1px solid #d1d5db;
    border-radius: 6px; font-size: 13px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.resize-row .resize:hover { background: #fef9c3; border-color: #fcd34d; }

/* Upload + action buttons */
.sig-actions { display: flex; flex-direction: column; gap: 6px; }
.btn-upload {
    padding: 7px 10px; background: #4f46e5; color: #fff;
    border: none; border-radius: 7px; font-size: 11px;
    font-weight: 600; cursor: pointer; text-align: center;
}
.btn-upload:hover { background: #4338ca; }
.btn-remove {
    padding: 6px 10px; background: transparent; color: #dc2626;
    border: 1px solid #fca5a5; border-radius: 7px;
    font-size: 11px; cursor: pointer;
}
.btn-remove:hover { background: #fef2f2; }
.btn-print-main {
    padding: 9px 10px; background: #059669; color: #fff;
    border: none; border-radius: 7px; font-size: 11px;
    font-weight: 600; cursor: pointer;
}
.btn-print-main:hover { background: #047857; }

/* ─── Cards area ────────────────────────────────────────── */
.cards-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}
.card-label {
    font-size: 10px; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: 1.5px;
    text-align: center; margin-bottom: 4px;
}

/* ─── Built-in card ─────────────────────────────────────── */
.id-card {
    position: relative;
    width: 3.375in;
    height: 2.125in;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
    font-family: Arial, sans-serif;
    background: #fff;
}
.id-card-bg {
    position: absolute; inset: 0;
    width: 100%; height: 100%; object-fit: cover; z-index: 0;
}

/* ─── Template card ─────────────────────────────────────── */
.tpl-card {
    position: relative; overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
    background: #fff;
    /* Must match the designer's .id-el, or text widths/heights differ and
       the print drifts from what was laid out. */
    font-family: "Arial Narrow", Arial, sans-serif;
    line-height: 1.15;
    white-space: normal;
}
.tpl-card * { box-sizing: border-box; }

/* Designer "Show only if": hidden unless the citizen has the matching flag
   (flag-* classes are on .cards-area). */
.show-if-pwd, .show-if-solo-parent, .show-if-senior, .show-if-voter { display: none !important; }
.flag-pwd .show-if-pwd,
.flag-solo-parent .show-if-solo-parent,
.flag-senior .show-if-senior,
.flag-voter .show-if-voter { display: block !important; }

/* Shared user CSS */
@if($useTemplate)
{{ $sharedCss }}
@if(isset($frontBgUrl) && $frontBgUrl)
.tpl-front { background-image: url('{{ $frontBgUrl }}'); background-size: cover; background-position: center; }
@endif
@if(isset($backBgUrl) && $backBgUrl)
.tpl-back  { background-image: url('{{ $backBgUrl }}');  background-size: cover; background-position: center; }
@endif
@endif

/* Signature overlay element (used in both built-in + template mode) */
.signature {
    position: absolute;
    z-index: 20;
    top: 158px;
    left: 40px;
}
.signature img {
    display: block;
    height: 22px;
    max-width: 200px;
}

.btn-upload-alt { background: #fff; color: #4f46e5; border: 1px solid #c7d2fe; }
.btn-upload-alt:hover { background: #eef2ff; }

/* ─── Pen signing pad (screen only) ─────────────────────── */
.sp-backdrop {
    position: fixed; inset: 0; z-index: 1000; background: rgba(17,24,39,.55);
    display: none; align-items: center; justify-content: center; padding: 16px;
}
.sp-backdrop.open { display: flex; }
.sp-box {
    background: #fff; border-radius: 14px; padding: 18px; width: 100%; max-width: 680px;
    box-shadow: 0 10px 40px rgba(0,0,0,.3); display: flex; flex-direction: column; gap: 12px;
}
.sp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.sp-title { font-size: 15px; font-weight: 700; color: #111827; }
.sp-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
.sp-x { background: none; border: none; font-size: 20px; color: #9ca3af; cursor: pointer; line-height: 1; }
.sp-x:hover { color: #374151; }
.sp-pad {
    position: relative; width: 100%; aspect-ratio: 3 / 1; background: #fff;
    border: 2px dashed #c7d2fe; border-radius: 10px; overflow: hidden;
}
.sp-pad canvas { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none; cursor: crosshair; }
.sp-line {
    position: absolute; left: 6%; right: 6%; bottom: 24%; border-bottom: 1px solid #d1d5db;
    pointer-events: none;
}
.sp-hint {
    position: absolute; left: 0; right: 0; top: 50%; transform: translateY(-50%);
    text-align: center; font-size: 13px; color: #c7c9d1; pointer-events: none;
}
.sp-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 12px; color: #4b5563; }
.sp-tools label { display: flex; align-items: center; gap: 4px; cursor: pointer; }
.sp-swatch { width: 18px; height: 18px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; padding: 0; }
.sp-swatch.on { border-color: #4f46e5; box-shadow: 0 0 0 2px #fff inset; }
.sp-tools input[type=range] { width: 90px; }
.sp-spacer { flex: 1; }
.sp-btn {
    padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer;
    border: 1px solid #d1d5db; background: #fff; color: #374151;
}
.sp-btn:hover { background: #f3f4f6; }
.sp-btn-primary { background: #059669; border-color: #059669; color: #fff; }
.sp-btn-primary:hover { background: #047857; }
.sp-btn:disabled { opacity: .5; cursor: not-allowed; }
.sp-msg { font-size: 12px; color: #dc2626; min-height: 1em; }
@media print { .sp-backdrop { display: none !important; } }

/* Print options in the controller */
.print-opts { display: flex; flex-direction: column; gap: 6px; border-top: 1px solid #f0f0f0; padding-top: 12px; }
.print-opts select, .print-opts input {
    width: 100%; font-size: 11px; padding: 4px 6px; border: 1px solid #d1d5db;
    border-radius: 6px; background: #fff; color: #1f2937;
}
.print-opts summary { font-size: 11px; color: #4f46e5; cursor: pointer; }
.fit-grid { display: grid; grid-template-columns: 1fr 64px; gap: 4px 6px; align-items: center; margin-top: 6px; font-size: 11px; color: #4b5563; }
.btn-reset { font-size: 10px; color: #6b7280; background: none; border: none; text-decoration: underline; cursor: pointer; text-align: left; padding: 0; }
.po-hint { font-size: 10px; color: #6b7280; line-height: 1.4; max-width: 170px; }
.po-hint b { display: inline; font-size: 10px; color: #374151; text-transform: none; letter-spacing: 0; margin: 0; }

/* ─── Print ───────────────────────────────────────────────
   Default = ID card printer: every side is its own CR80 page
   (3.375 × 2.125 in), no margins. The script below rewrites
   #print-style for "plain paper" and the fine-tune values. */
@php
    $fo = $useTemplate ? ($frontOrient ?? 'landscape') : 'landscape';
    $bo = $useTemplate ? ($backOrient  ?? 'landscape') : 'landscape';
    $cr80 = fn ($o) => $o === 'portrait' ? ['2.125in', '3.375in'] : ['3.375in', '2.125in'];
    [$pfW, $pfH] = $cr80($fo);
    [$pbW, $pbH] = $cr80($bo);
@endphp
</style>
<style id="print-style">
@page { size: {{ $pfW }} {{ $pfH }}; margin: 0; }
@media print {
    html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; display: block !important; min-height: 0 !important; }
    .controller, .card-label { display: none !important; }
    .cards-area { display: block; gap: 0; }
    .card-wrap { width: {{ $pfW }}; height: {{ $pfH }}; overflow: hidden; }
    .card-wrap-back { break-before: page; }
    .id-card, .tpl-card { box-shadow: none !important; }
}
</style>
</head>
<body>

{{-- ── Controller (screen-only) ── --}}
<div class="controller no-print" id="ctrl-panel">

    {{-- Who + validity --}}
    <div class="ctrl-meta">
        <a href="{{ route('citizens.ids.index') }}" class="back-link">← Back to ID list</a>
        <div class="meta-name">{{ $c?->full_name ?? 'Citizen' }}</div>
        <div class="meta-id">{{ $idNo }}</div>
        <div class="meta-valid">
            Valid until <b>{{ $validUntil }}</b>
        </div>
    </div>

    <div class="sig-actions">
        <button class="btn-print-main" onclick="window.print()">🖨 Print</button>
        @can('citizen_ids.signature')
        <input type="file" id="sig-file" accept="image/*" style="display:none" onchange="handleSigUpload(this)">
        <button class="btn-upload" onclick="openSignPad()">✍ Sign with pen</button>
        <button class="btn-upload btn-upload-alt" onclick="document.getElementById('sig-file').click()">📷 Upload Signature</button>
        <button class="btn-remove" id="btn-remove" onclick="removeSig()" style="{{ $sigUrl ? '' : 'display:none' }}">✕ Remove</button>
        @endcan
    </div>

    <div>
        <b>Move Signature</b>
        <div class="ctrl-grid">
            <div class="spacer"></div>
            <button class="move" data-dir="up">↑</button>
            <div class="spacer"></div>
            <button class="move" data-dir="left">←</button>
            <div class="spacer"></div>
            <button class="move" data-dir="right">→</button>
            <div class="spacer"></div>
            <button class="move" data-dir="down">↓</button>
            <div class="spacer"></div>
        </div>
    </div>

    <div>
        <b>Signature Height</b>
        <div class="resize-row">
            <button class="resize" data-dir="up">↑</button>
            <button class="resize" data-dir="down">↓</button>
        </div>
    </div>

    {{-- Print setup — remembered on this computer (per printer). --}}
    <div class="print-opts">
        <div>
            <b>Print on</b>
            <select id="po-mode">
                <option value="card">ID card printer (CR80)</option>
                <option value="paper">Plain paper, cut out</option>
            </select>
        </div>
        <div>
            <b>Sides</b>
            <select id="po-sides">
                <option value="both">Front + back</option>
                <option value="front">Front only</option>
                <option value="back">Back only</option>
            </select>
        </div>
        <details id="po-fit">
            <summary>Fine-tune fit</summary>
            <div class="fit-grid">
                <label for="po-dx">Move right (mm)</label><input type="number" id="po-dx" step="0.5" value="0">
                <label for="po-dy">Move down (mm)</label><input type="number" id="po-dy" step="0.5" value="0">
                <label for="po-scale">Size (%)</label><input type="number" id="po-scale" step="0.5" min="80" max="120" value="100">
            </div>
            <button type="button" class="btn-reset" id="po-reset">Reset</button>
        </details>
        <p class="po-hint" id="po-hint-card">
            In the print window: <b>Paper size</b> CR80 / ID card, <b>Margins</b> None,
            <b>Scale</b> Default (100%), <b>Background graphics</b> on, headers and footers off.
            Single-sided printer? Print <b>Front only</b>, turn the card over, then <b>Back only</b>.
        </p>
        <p class="po-hint" id="po-hint-paper" style="display:none">
            Prints at actual size on A4 / Letter. Set <b>Scale</b> to Default (100%) — not “Fit to page” — and turn <b>Background graphics</b> on.
        </p>
    </div>

</div>

{{-- ── Cards ── --}}
{{-- cards-area also carries the citizen's tags (tag-*) and flags (flag-*) as
     classes, so custom template CSS can style a card by membership. --}}
<div class="cards-area {{ $citizenClasses }}">

@if(!$useTemplate)
{{-- ══════════════════════════════════════════════════════ --}}
{{-- BUILT-IN LAYOUT                                       --}}
{{-- ══════════════════════════════════════════════════════ --}}

{{-- FRONT --}}
<div class="card-wrap card-wrap-front">
    <p class="card-label">Front</p>
    <div class="id-card" id="card-front">

        @if($tpl?->bg_front)
        <img class="id-card-bg" src="{{ asset(str_replace('public/','storage/',$tpl->bg_front)) }}" alt="">
        @endif

        {{-- Header --}}
        <div style="position:absolute;top:0;left:0;right:0;z-index:1;text-align:center;padding:5px 0 2px;">
            <div style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.5px;line-height:1.15;">
                {{ strtoupper($brgyName) }} RESIDENT
            </div>
            @if($municity || $province)
            <div style="font-size:7.5px;text-transform:uppercase;opacity:.75;">
                {{ implode(', ', array_filter([$municity, $province])) }}
            </div>
            @endif
        </div>

        {{-- Photo + ID badge --}}
        <div style="position:absolute;top:26px;right:7px;z-index:1;width:80px;">
            <img src="{{ $photoUrl }}" alt="Photo"
                 style="width:80px;height:80px;object-fit:cover;border:.5px solid #888;display:block;">
            <div style="background:#c00;color:#fff;font-size:8px;font-weight:bold;
                        text-align:center;padding:1px 2px;letter-spacing:.5px;">
                {{ $idNo }}
            </div>
        </div>

        {{-- Info table --}}
        <div style="position:absolute;top:28px;left:5px;max-width:248px;z-index:1;">
            <table style="border-collapse:collapse;font-size:8.5px;text-transform:uppercase;line-height:1.3;width:100%;">
                <tr>
                    <td colspan="2" style="font-size:12px;font-weight:bold;padding-bottom:3px;word-break:break-word;">
                        {{ strtoupper($fullNameFormal) }}
                    </td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;font-weight:bold;padding-right:5px;vertical-align:top;">ADDRESS:</td>
                    <td style="text-decoration:underline;">{{ strtoupper($address) }}</td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;font-weight:bold;padding-right:5px;">DATE OF BIRTH:</td>
                    <td style="text-decoration:underline;">{{ strtoupper($bday) }}</td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;font-weight:bold;padding-right:5px;">CONTACT #:</td>
                    <td style="text-decoration:underline;">{{ $contact }}</td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;font-weight:bold;padding-right:5px;">DATE ISSUED:</td>
                    <td style="text-decoration:underline;">{{ $citizenId->created_at->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;font-weight:bold;padding-right:5px;">GENDER:</td>
                    <td style="text-decoration:underline;">{{ strtoupper($gender) }}</td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;font-weight:bold;padding-right:5px;">MEMBER SINCE:</td>
                    <td style="text-decoration:underline;">{{ $since }}</td>
                </tr>
            </table>
        </div>

        {{-- Signature overlay on built-in card --}}
        <div class="signature" id="sig-overlay">
            <img id="sig-img-inner" src="{{ $sigUrl ?? '' }}"
                 style="{{ $sigUrl ? 'display:block;' : 'display:none;' }}height:22px;">
        </div>

    </div>
</div>

{{-- BACK --}}
<div class="card-wrap card-wrap-back">
    <p class="card-label">Back</p>
    <div class="id-card" id="card-back">

        @if($tpl?->bg_back)
        <img class="id-card-bg" src="{{ asset(str_replace('public/','storage/',$tpl->bg_back)) }}" alt="">
        @endif

        <div style="position:absolute;left:10px;top:10px;z-index:1;width:80px;height:80px;">
            <img src="{{ $qrUrl }}" alt="QR" style="width:100%;height:100%;display:block;">
        </div>
        <div style="position:absolute;top:10px;right:10px;z-index:1;width:185px;
                    background:#000;color:#fff;text-align:center;
                    font-size:10px;font-weight:bold;text-decoration:underline;padding:2px 4px;">
            {{ $idNo }}
        </div>
        <div style="position:absolute;top:30px;right:10px;z-index:1;width:185px;
                    font-size:8.5px;text-transform:uppercase;">
            <div style="text-align:center;font-weight:bold;margin-bottom:3px;">IN CASE OF EMERGENCY</div>
            <table style="border-collapse:collapse;width:100%;line-height:1.3;">
                <tr>
                    <td style="font-weight:bold;padding-right:4px;white-space:nowrap;vertical-align:top;">NAME:</td>
                    <td style="text-decoration:underline;">{{ strtoupper($icName) }}</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;padding-right:4px;vertical-align:top;">ADDRESS:</td>
                    <td style="text-decoration:underline;">{{ strtoupper($icAddress) }}</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;padding-right:4px;white-space:nowrap;">CONTACT:</td>
                    <td style="text-decoration:underline;">{{ $icContact }}</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;padding-right:4px;white-space:nowrap;">RELATION:</td>
                    <td style="text-decoration:underline;">{{ strtoupper($icRel) }}</td>
                </tr>
            </table>
        </div>
        <div style="position:absolute;bottom:30px;left:10px;z-index:1;
                    font-size:8px;font-weight:bold;text-decoration:underline;
                    text-transform:uppercase;width:93px;text-align:center;">
            VALID UNTIL:<br>{{ strtoupper($validUntil) }}
        </div>
        <div style="position:absolute;bottom:10px;left:108px;right:8px;z-index:1;
                    font-size:6px;text-transform:uppercase;font-style:italic;line-height:1.4;">
            This is to certify that the person whose signature appears on this
            card is a bona fide resident of this Barangay.
        </div>
        <div style="position:absolute;bottom:6px;left:10px;z-index:1;
                    width:93px;text-align:center;font-size:6px;
                    font-weight:bold;text-transform:uppercase;
                    border-top:1px solid #333;padding-top:2px;line-height:1.3;">
            {{ strtoupper($captain) }}<br>Barangay Captain
        </div>

    </div>
</div>

@else
{{-- ══════════════════════════════════════════════════════ --}}
{{-- TEMPLATE MODE                                         --}}
{{-- ══════════════════════════════════════════════════════ --}}

@if($frontHtml !== '')
<div class="card-wrap card-wrap-front">
    <p class="card-label">Front</p>
    <div class="tpl-card tpl-front" id="card-front"
         style="width:{{ $frontW }};height:{{ $frontH }};position:relative;">
        {!! $frontHtml !!}
        {{-- Fallback sig overlay only if the compiled HTML has no signature box.
             (Checks the rendered output, not the raw template, so it's reliable
             whether the tag was on the front or back.) --}}
        @if(!str_contains($frontHtml . $backHtml, 'id="sig-overlay"'))
        <div class="signature" id="sig-overlay">
            <img id="sig-img-inner" src="{{ $sigUrl ?? '' }}"
                 style="{{ $sigUrl ? 'display:block;' : 'display:none;' }}height:22px;">
        </div>
        @endif
    </div>
</div>
@endif

@if($backHtml !== '')
<div class="card-wrap card-wrap-back">
    <p class="card-label">Back</p>
    <div class="tpl-card tpl-back" id="card-back"
         style="width:{{ $backW }};height:{{ $backH }};">
        {!! $backHtml !!}
    </div>
</div>
@endif

@if($sharedJs)
<script>{{ $sharedJs }}</script>
@endif

@endif

</div>{{-- end .cards-area --}}

@can('citizen_ids.signature')
{{-- ── Pen signing pad (screen only) ── --}}
<div class="sp-backdrop" id="sign-pad" role="dialog" aria-modal="true" aria-labelledby="sp-title">
    <div class="sp-box">
        <div class="sp-head">
            <div>
                <div class="sp-title" id="sp-title">Sign with pen</div>
                <div class="sp-sub">{{ $c?->full_name ?? 'Citizen' }} signs in the box using a signature pad, pen tablet, touchscreen or mouse.</div>
            </div>
            <button type="button" class="sp-x" id="sp-close" aria-label="Close">✕</button>
        </div>
        <div class="sp-pad">
            <div class="sp-line"></div>
            <div class="sp-hint" id="sp-hint">Sign here</div>
            <canvas id="sp-canvas"></canvas>
        </div>
        <div class="sp-tools">
            <span>Ink</span>
            <button type="button" class="sp-swatch on" data-color="#111827" style="background:#111827" title="Black" aria-label="Black ink"></button>
            <button type="button" class="sp-swatch" data-color="#1d3fae" style="background:#1d3fae" title="Blue" aria-label="Blue ink"></button>
            <label>Thickness <input type="range" id="sp-width" min="1.5" max="6" step="0.5" value="3"></label>
            <span class="sp-spacer"></span>
            <button type="button" class="sp-btn" id="sp-undo" disabled>↶ Undo</button>
            <button type="button" class="sp-btn" id="sp-clear">Clear</button>
        </div>
        <div class="sp-msg" id="sp-msg"></div>
        <div class="sp-tools">
            <span class="sp-spacer"></span>
            <button type="button" class="sp-btn" id="sp-cancel">Cancel</button>
            <button type="button" class="sp-btn sp-btn-primary" id="sp-save" disabled>Use this signature</button>
        </div>
    </div>
</div>
@endcan

<script>
const UPLOAD_URL = '{{ route('citizens.ids.upload-signature', $citizenId) }}';
const REMOVE_URL = '{{ route('citizens.ids.remove-signature', $citizenId) }}';
const CSRF       = '{{ csrf_token() }}';

// ── Signature helpers ─────────────────────────────────────────
function getSigOverlay()  { return document.getElementById('sig-overlay'); }
function getSigImgInner() { return document.getElementById('sig-img-inner'); }

function handleSigUpload(input) {
    if (!input.files[0]) return;

    // Preview immediately from local file (no round-trip wait)
    const reader = new FileReader();
    reader.onload = function(e) {
        applySigSrc(e.target.result);
    };
    reader.readAsDataURL(input.files[0]);

    // Also persist to server
    const fd = new FormData();
    fd.append('signature', input.files[0]);
    fd.append('_token', CSRF);
    fetch(UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => applySigSrc(d.url))   // update to stable storage URL
        .catch(() => {});                 // preview already shown, failure is silent

    document.getElementById('btn-remove').style.display = '';
    input.value = '';
}

function removeSig() {
    fetch(REMOVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({}),
    }).then(() => {
        applySigSrc(null);
        document.getElementById('btn-remove').style.display = 'none';
    });
}

function applySigSrc(url) {
    const img = getSigImgInner();
    if (!img) return;
    if (url) {
        img.src = url;
        img.style.display = 'block';
    } else {
        img.src = '';
        img.style.display = 'none';
    }
}

// ── Move controller ───────────────────────────────────────────
document.querySelectorAll('.move').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var el = getSigOverlay();
        if (!el) return;
        var dir  = this.dataset.dir;
        var top  = parseInt(el.style.top  || '158') || 158;
        var left = parseInt(el.style.left || '40')  || 40;
        var step = 1;
        if (dir === 'up')    top  -= step;
        if (dir === 'down')  top  += step;
        if (dir === 'left')  left -= step;
        if (dir === 'right') left += step;
        el.style.top  = top  + 'px';
        el.style.left = left + 'px';
    });
});

// ── Pen signing pad ───────────────────────────────────────────
// Works with signature pads / pen tablets (uses pen pressure), touchscreens
// and a mouse. Saved as a transparent PNG trimmed to the ink, through the
// same endpoint as Upload Signature.
(function () {
    var backdrop = document.getElementById('sign-pad');
    if (!backdrop) return;
    var canvas = document.getElementById('sp-canvas');
    var ctx    = canvas.getContext('2d');
    var hint   = document.getElementById('sp-hint');
    var msg    = document.getElementById('sp-msg');
    var btnSave = document.getElementById('sp-save');
    var btnUndo = document.getElementById('sp-undo');
    var color = '#111827', width = 3;
    var strokes = [], current = null;

    function resize() {
        var r = canvas.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
        canvas.width  = Math.round(r.width * dpr);
        canvas.height = Math.round(r.height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        redraw();
    }

    function drawStroke(st) {
        ctx.strokeStyle = st.color; ctx.fillStyle = st.color;
        ctx.lineCap = 'round'; ctx.lineJoin = 'round';
        var p = st.pts;
        if (p.length === 1) {
            ctx.beginPath(); ctx.arc(p[0].x, p[0].y, st.width * p[0].w / 2, 0, Math.PI * 2); ctx.fill();
            return;
        }
        // Segment by segment so the width can follow pen pressure; midpoints
        // with quadratic curves keep the line smooth.
        for (var i = 1; i < p.length; i++) {
            var a = p[i - 1], b = p[i];
            var m0 = i > 1 ? { x: (p[i - 2].x + a.x) / 2, y: (p[i - 2].y + a.y) / 2 } : a;
            var m1 = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
            ctx.beginPath();
            ctx.lineWidth = st.width * (a.w + b.w) / 2;
            ctx.moveTo(m0.x, m0.y);
            ctx.quadraticCurveTo(a.x, a.y, m1.x, m1.y);
            ctx.stroke();
        }
    }

    function redraw() {
        var r = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, r.width, r.height);
        strokes.forEach(drawStroke);
        if (current) drawStroke(current);
        var empty = !strokes.length && !current;
        hint.style.display = empty ? '' : 'none';
        btnSave.disabled = !strokes.length;
        btnUndo.disabled = !strokes.length;
    }

    function point(e) {
        var r = canvas.getBoundingClientRect();
        // Pens report real pressure; mouse/touch report 0.5 (or 0) → normal width.
        var pr = (e.pointerType === 'pen' && e.pressure > 0) ? e.pressure : 0.5;
        return { x: e.clientX - r.left, y: e.clientY - r.top, w: 0.4 + pr * 1.2 };
    }

    canvas.addEventListener('pointerdown', function (e) {
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        e.preventDefault();
        canvas.setPointerCapture(e.pointerId);
        current = { color: color, width: width, pts: [point(e)] };
        msg.textContent = '';
        redraw();
    });
    canvas.addEventListener('pointermove', function (e) {
        if (!current) return;
        e.preventDefault();
        var evs = e.getCoalescedEvents ? e.getCoalescedEvents() : [e];
        (evs.length ? evs : [e]).forEach(function (ev) { current.pts.push(point(ev)); });
        redraw();
    });
    function end() {
        if (!current) return;
        strokes.push(current); current = null; redraw();
    }
    canvas.addEventListener('pointerup', end);
    canvas.addEventListener('pointercancel', end);

    // Render the ink at 3× onto a transparent canvas, trimmed to its bounds.
    function exportBlob(cb) {
        var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity, maxW = 0;
        strokes.forEach(function (st) {
            st.pts.forEach(function (p) {
                minX = Math.min(minX, p.x); minY = Math.min(minY, p.y);
                maxX = Math.max(maxX, p.x); maxY = Math.max(maxY, p.y);
            });
            maxW = Math.max(maxW, st.width * 1.6);
        });
        var pad = maxW + 2, S = 3;
        var w = Math.ceil(maxX - minX + pad * 2), h = Math.ceil(maxY - minY + pad * 2);
        var out = document.createElement('canvas');
        out.width = w * S; out.height = h * S;
        var keep = ctx;
        ctx = out.getContext('2d');
        ctx.setTransform(S, 0, 0, S, (pad - minX) * S, (pad - minY) * S);
        strokes.forEach(drawStroke);
        ctx = keep;
        out.toBlob(cb, 'image/png');
    }

    window.openSignPad = function () {
        strokes = []; current = null; msg.textContent = '';
        backdrop.classList.add('open');
        resize();
    };
    function close() { backdrop.classList.remove('open'); }

    document.getElementById('sp-close').addEventListener('click', close);
    document.getElementById('sp-cancel').addEventListener('click', close);
    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
    document.addEventListener('keydown', function (e) {
        if (!backdrop.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') { e.preventDefault(); strokes.pop(); redraw(); }
    });
    window.addEventListener('resize', function () { if (backdrop.classList.contains('open')) resize(); });

    document.getElementById('sp-clear').addEventListener('click', function () { strokes = []; redraw(); });
    btnUndo.addEventListener('click', function () { strokes.pop(); redraw(); });
    backdrop.querySelectorAll('.sp-swatch').forEach(function (b) {
        b.addEventListener('click', function () {
            color = this.dataset.color;
            backdrop.querySelectorAll('.sp-swatch').forEach(function (x) { x.classList.toggle('on', x === b); });
        });
    });
    document.getElementById('sp-width').addEventListener('input', function () { width = parseFloat(this.value) || 3; });

    btnSave.addEventListener('click', function () {
        if (!strokes.length) return;
        btnSave.disabled = true; btnSave.textContent = 'Saving…';
        exportBlob(function (blob) {
            var fd = new FormData();
            fd.append('signature', blob, 'signature.png');
            fd.append('_token', CSRF);
            fetch(UPLOAD_URL, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (d) {
                    applySigSrc(d.url);
                    var rm = document.getElementById('btn-remove');
                    if (rm) rm.style.display = '';
                    close();
                })
                .catch(function () { msg.textContent = 'Could not save the signature. Check the connection and try again.'; })
                .finally(function () { btnSave.disabled = false; btnSave.textContent = 'Use this signature'; });
        });
    });
})();

// ── Print setup (card printer / plain paper, sides, fine-tune) ─
(function () {
    var SIZES = {
        front: { w: '{{ $pfW }}', h: '{{ $pfH }}' },
        back:  { w: '{{ $pbW }}', h: '{{ $pbH }}' },
    };
    var KEY = 'idprint.setup.v1';
    var DEF = { mode: 'card', sides: 'both', dx: 0, dy: 0, scale: 100 };
    var s = Object.assign({}, DEF);
    try { Object.assign(s, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}
    s.sides = 'both';   // sides is per print job, never remembered

    var $ = function (id) { return document.getElementById(id); };
    var num = function (v, d) { v = parseFloat(v); return isFinite(v) ? v : d; };

    function css() {
        var hasFront = !!document.querySelector('.card-wrap-front');
        var hasBack  = !!document.querySelector('.card-wrap-back');
        var showFront = hasFront && s.sides !== 'back';
        var showBack  = hasBack  && s.sides !== 'front';
        var first = showFront ? SIZES.front : SIZES.back;
        var fit = 'transform: translate(' + num(s.dx, 0) + 'mm,' + num(s.dy, 0) + 'mm) scale(' + (num(s.scale, 100) / 100) + ');'
                + 'transform-origin: center center;';
        var out = '@media print {'
            + 'html,body{margin:0!important;padding:0!important;background:#fff!important;min-height:0!important;width:auto!important;}'
            + '.controller,.card-label{display:none!important;}'
            + '.id-card,.tpl-card{box-shadow:none!important;' + fit + '}'
            + (showFront ? '' : '.card-wrap-front{display:none!important;}')
            + (showBack  ? '' : '.card-wrap-back{display:none!important;}');

        if (s.mode === 'paper') {
            // Actual size on a normal sheet, a thin cut line around each card.
            out += 'body{display:block!important;}'
                + '.cards-area{display:flex!important;flex-direction:row;flex-wrap:wrap;gap:8mm;align-items:flex-start;justify-content:flex-start;}'
                + '.card-wrap{break-inside:avoid;outline:0.2mm dashed #999;}'
                + '}'
                + '@page{size:auto;margin:12mm;}';
        } else {
            // One card per page, exactly CR80, no margins — what card printer drivers expect.
            out += 'body{display:block!important;}'
                + '.cards-area{display:block!important;gap:0!important;}'
                + '.card-wrap{overflow:hidden;position:relative;}'
                + '.card-wrap-front{page:idfront;width:' + SIZES.front.w + ';height:' + SIZES.front.h + ';}'
                + '.card-wrap-back{page:idback;width:' + SIZES.back.w + ';height:' + SIZES.back.h + ';}'
                + (showFront && showBack ? '.card-wrap-back{break-before:page;}' : '')
                + '}'
                + '@page{size:' + first.w + ' ' + first.h + ';margin:0;}'
                + '@page idfront{size:' + SIZES.front.w + ' ' + SIZES.front.h + ';margin:0;}'
                + '@page idback{size:' + SIZES.back.w + ' ' + SIZES.back.h + ';margin:0;}';
        }
        $('print-style').textContent = out;
        $('po-hint-card').style.display  = s.mode === 'paper' ? 'none' : '';
        $('po-hint-paper').style.display = s.mode === 'paper' ? '' : 'none';
    }

    function save() {
        try { localStorage.setItem(KEY, JSON.stringify({ mode: s.mode, dx: s.dx, dy: s.dy, scale: s.scale })); } catch (e) {}
    }

    function load() {
        $('po-mode').value  = s.mode;
        $('po-sides').value = s.sides;
        $('po-dx').value    = s.dx;
        $('po-dy').value    = s.dy;
        $('po-scale').value = s.scale;
        if (num(s.dx, 0) || num(s.dy, 0) || num(s.scale, 100) !== 100) $('po-fit').open = true;
    }

    ['po-mode', 'po-sides', 'po-dx', 'po-dy', 'po-scale'].forEach(function (id) {
        $(id).addEventListener('input', function () {
            var k = id.slice(3);
            s[k] = (k === 'mode' || k === 'sides') ? this.value : num(this.value, DEF[k]);
            css(); save();
        });
    });
    $('po-reset').addEventListener('click', function () {
        s.dx = 0; s.dy = 0; s.scale = 100; load(); css(); save();
    });

    load(); css();
})();

// ── Height controller ─────────────────────────────────────────
document.querySelectorAll('.resize').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var img = getSigImgInner();
        if (!img) return;
        var h    = parseInt(img.style.height) || 22;
        var dir  = this.dataset.dir;
        if (dir === 'up')   h += 1;
        if (dir === 'down') h  = Math.max(8, h - 1);
        img.style.height = h + 'px';
    });
});
</script>

</body>
</html>
