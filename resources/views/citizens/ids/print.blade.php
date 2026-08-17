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
    $address    = $c?->complete_address ?? ($c?->addressZone?->description ?? '—');
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
    $frontHtml  = trim($tpl?->html_front ?? '');
    $backHtml   = trim($tpl?->html_back ?? '');
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

        // Replace all fixed placeholders first
        foreach ($vals as $k => $v) {
            $frontHtml = str_replace('{{' . $k . '}}', $v, $frontHtml);
            $backHtml  = str_replace('{{' . $k . '}}', $v, $backHtml);
        }

        // Replace any {{qr_img_N}} dynamically — supports any pixel size.
        // QR is generated locally as an SVG data URI (no network dependency).
        $qrReplace = function(string $html) use ($qrValue): string {
            return preg_replace_callback('/\{\{qr_img_(\d+)\}\}/', function($m) use ($qrValue) {
                $px  = (int) $m[1];
                $uri = \App\Support\Qr::svgDataUri($qrValue, $px);
                return '<img src="' . $uri . '" width="' . $px . '" height="' . $px . '" style="display:inline-block;vertical-align:middle;" alt="QR">';
            }, $html);
        };
        $frontHtml = $qrReplace($frontHtml);
        $backHtml  = $qrReplace($backHtml);

        $frontBgUrl  = $tpl?->bg_front ? asset(str_replace('public/', 'storage/', $tpl->bg_front)) : '';
        $frontOrient = $tpl?->orientation_front ?? 'landscape';
        $backBgUrl   = $tpl?->bg_back  ? asset(str_replace('public/', 'storage/', $tpl->bg_back))  : '';
        $backOrient  = $tpl?->orientation_back ?? 'landscape';
        $frontW = ($frontOrient === 'portrait') ? '215mm' : '3.375in';
        $frontH = ($frontOrient === 'portrait') ? '340mm' : '2.125in';
        $backW  = ($backOrient  === 'portrait') ? '215mm' : '3.375in';
        $backH  = ($backOrient  === 'portrait') ? '340mm' : '2.125in';
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
}
.tpl-card * { box-sizing: border-box; }

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

/* ─── Print ─────────────────────────────────────────────── */
@page {
    /* CR80 standard ID card: 3.375in × 2.125in each, two per page vertically */
    size: 3.375in 4.75in;   /* width = 1 card, height = 2 cards + small gap */
    margin: 0.125in 0;      /* top/bottom breathing room, no side margin */
}
@media print {
    body {
        background: #fff;
        padding: 0;
        margin: 0;
        gap: 0.25in;
        flex-direction: column;
        align-items: center;
        width: 3.375in;
    }
    .controller { display: none !important; }
    .card-label  { display: none !important; }
    .id-card, .tpl-card { box-shadow: none; }
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
        <input type="file" id="sig-file" accept="image/*" style="display:none" onchange="handleSigUpload(this)">
        <button class="btn-upload" onclick="document.getElementById('sig-file').click()">📷 Upload Signature</button>
        <button class="btn-remove" id="btn-remove" onclick="removeSig()" style="{{ $sigUrl ? '' : 'display:none' }}">✕ Remove</button>
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
<div>
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
<div>
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
<div>
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
<div>
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
