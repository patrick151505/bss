@php
    $C        = $checkup;
    $brgy     = $settings->barangay_name ?: 'Barangay';
    $logo     = $settings->logo           ? asset('storage/' . str_replace('public/', '', $settings->logo))           : null;
    $munLogo  = $settings->municipal_logo ? asset('storage/' . str_replace('public/', '', $settings->municipal_logo)) : null;
    $age      = $C->patientAge();
    $sex      = $C->patientSex();
    $address  = $C->citizen?->full_address;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reseta — {{ $C->patientName() }}</title>
{{-- Page size: set by the size picker (half letter by default, the usual reseta pad). --}}
<style id="page-size">@page { size: 5.5in 8.5in; margin: 0; }</style>
<style>
    * { box-sizing: border-box; }
    html, body { background: #e5e7eb; margin: 0; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .toolbar {
        position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 10px; background: #1f2937; color: #e5e7eb; font: 13px system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
    .toolbar button, .toolbar a.btn { font: 600 13px system-ui, sans-serif; padding: 8px 16px; border-radius: 6px; border: 0; cursor: pointer; text-decoration: none; }
    .toolbar .primary { background: #727cf5 !important; color: #fff; }
    .toolbar .ghost   { background: #374151 !important; color: #e5e7eb; }
    .toolbar select { font: 13px system-ui, sans-serif; padding: 7px 8px; border-radius: 6px; border: 0; background: #374151; color: #e5e7eb; }

    /* The reseta. Its size follows the picked paper (CSS variables set by the script). */
    .rx {
        --w: 5.5in; --h: 8.5in;
        width: var(--w); min-height: var(--h); margin: 20px auto; padding: 0.4in 0.45in 0.35in;
        background: #fff; box-shadow: 0 2px 14px rgba(0,0,0,.15);
        display: flex; flex-direction: column; font-size: 10.5pt;
    }

    .head { display: grid; grid-template-columns: 52px 1fr 52px; align-items: center; gap: 6px; }
    .head img { width: 50px; height: 50px; object-fit: contain; }
    .head .c { text-align: center; line-height: 1.2; }
    .head .rep { font-size: 7.5pt; letter-spacing: .4px; }
    .head .loc { font-size: 8pt; }
    .head .brgy { font: 700 11.5pt Georgia, serif; text-transform: uppercase; letter-spacing: .4px; margin-top: 1px; }
    .head .office { font-size: 8pt; font-style: italic; color: #333; }
    .rule { border-top: 2px solid #1e3a5f; border-bottom: 1px solid #1e3a5f; height: 3px; margin: 6px 0 10px; }

    .patient { display: grid; grid-template-columns: auto 1fr auto 1fr; gap: 3px 6px; font-size: 9.5pt; align-items: end; }
    .patient .l { color: #555; font-size: 8pt; text-transform: uppercase; letter-spacing: .3px; white-space: nowrap; }
    .patient .v { border-bottom: 1px solid #999; font-weight: 600; min-height: 1.3em; padding: 0 2px; }
    .patient .wide { grid-column: span 3; }

    .symbol { font: 700 36pt Georgia, 'Times New Roman', serif; color: #1e3a5f; line-height: 1; margin: 12px 0 4px; }

    ol.meds { margin: 0; padding: 0 0 0 4px; list-style: none; counter-reset: rx; flex: 1; }
    ol.meds li { counter-increment: rx; padding: 6px 0 8px 24px; position: relative; break-inside: avoid; page-break-inside: avoid; }
    ol.meds li::before { content: counter(rx) "."; position: absolute; left: 0; top: 6px; font-weight: 700; color: #1e3a5f; }
    .med { display: flex; justify-content: space-between; gap: 10px; font-weight: 700; font-size: 11pt; }
    .med .qty { white-space: nowrap; font-weight: 700; }
    .sig { margin-top: 2px; font-size: 10pt; }
    .sig b { font-style: italic; color: #1e3a5f; }

    .follow { font-size: 9pt; margin-top: 6px; color: #333; }

    .signer { margin-top: 16px; margin-left: auto; width: 58%; text-align: center; font-size: 9pt; break-inside: avoid; page-break-inside: avoid; }
    .signer .line { border-bottom: 1px solid #111; min-height: 1.5em; font-weight: 700; text-transform: uppercase; font-size: 10pt; padding-top: 22px; }
    .signer .meta { margin-top: 2px; color: #333; line-height: 1.35; text-align: left; display: inline-block; }

    .foot { margin-top: 10px; padding-top: 4px; border-top: 1px solid #d5dbe1; font-size: 7pt; color: #6b7280; display: flex; justify-content: space-between; gap: 8px; }

    @media print {
        html, body { background: #fff; }
        .toolbar { display: none !important; }
        .rx { margin: 0; box-shadow: none; width: var(--w); min-height: var(--h); height: var(--h); overflow: hidden; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="primary" onclick="window.print()">🖨 Print reseta</button>
    <label>Paper
        <select id="rx-size">
            <option value="5.5in 8.5in">Half letter (5.5 × 8.5 in)</option>
            <option value="148mm 210mm">A5 (148 × 210 mm)</option>
            <option value="8.5in 11in">Letter (8.5 × 11 in)</option>
            <option value="210mm 297mm">A4</option>
        </select>
    </label>
    <a class="btn ghost" href="{{ route('health.show', $C) }}">← Back to check-up</a>
</div>

<div class="rx" id="rx">
    <div class="head">
        <div>@if($logo)<img src="{{ $logo }}" alt="">@endif</div>
        <div class="c">
            <div class="rep">Republic of the Philippines</div>
            @if($settings->municipality || $settings->province)
            <div class="loc">{{ collect([$settings->municipality, $settings->province])->filter()->implode(', ') }}</div>
            @endif
            <div class="brgy">{{ $brgy }}</div>
            <div class="office">Barangay Health Station</div>
        </div>
        <div style="text-align:right">@if($munLogo)<img src="{{ $munLogo }}" alt="">@endif</div>
    </div>
    <div class="rule"></div>

    <div class="patient">
        <span class="l">Name</span><span class="v wide">{{ $C->patientName() }}</span>
        <span class="l">Age</span><span class="v">{{ $age !== null ? $age : '' }}</span>
        <span class="l">Sex</span><span class="v">{{ $sex }}</span>
        <span class="l">Address</span><span class="v wide">{{ $address }}</span>
        <span class="l">Date</span><span class="v wide">{{ $C->checked_at->format('F d, Y') }}</span>
    </div>

    <div class="symbol">℞</div>

    <ol class="meds">
        @foreach($C->prescriptions as $rx)
        <li>
            <div class="med"><span>{{ $rx->medicine }}</span>@if($rx->quantity)<span class="qty">{{ $rx->quantity }}</span>@endif</div>
            @if($rx->sig)<div class="sig"><b>Sig:</b> {{ $rx->sig }}</div>@endif
        </li>
        @endforeach
    </ol>

    @if($C->follow_up_on)
    <div class="follow">Follow-up: <b>{{ $C->follow_up_on->format('F d, Y') }}</b></div>
    @endif

    <div class="signer">
        <div class="line">{{ $C->attended_by }}</div>
        <div>Signature over printed name</div>
        @if($C->prescriber_license || $C->prescriber_ptr)
        <div class="meta">
            @if($C->prescriber_license)Lic. No. {{ $C->prescriber_license }}<br>@endif
            @if($C->prescriber_ptr)PTR No. {{ $C->prescriber_ptr }}@endif
        </div>
        @endif
    </div>

    <div class="foot">
        <span>{{ $brgy }} Health Station</span>
        <span>Check-up #{{ $C->id }}</span>
    </div>
</div>

<script>
(function () {
    // Paper size: remembered on this computer.
    var KEY = 'health.reseta.size', sel = document.getElementById('rx-size');
    var sheet = document.getElementById('rx'), style = document.getElementById('page-size');
    function apply(v) {
        var p = v.split(' ');
        style.textContent = '@page { size: ' + v + '; margin: 0; }';
        sheet.style.setProperty('--w', p[0]); sheet.style.setProperty('--h', p[1]);
    }
    try { var saved = localStorage.getItem(KEY); if (saved && [].some.call(sel.options, function (o) { return o.value === saved; })) sel.value = saved; } catch (e) {}
    apply(sel.value);
    sel.addEventListener('change', function () { apply(sel.value); try { localStorage.setItem(KEY, sel.value); } catch (e) {} });
})();
</script>
</body>
</html>
