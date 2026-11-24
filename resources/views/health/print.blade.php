@php
    $C        = $checkup;
    $brgy     = $settings->barangay_name ?: 'Barangay';
    $location = collect([$settings->municipality, $settings->province])->filter()->implode(', ');
    $logo     = $settings->logo           ? asset('storage/' . str_replace('public/', '', $settings->logo))           : null;
    $munLogo  = $settings->municipal_logo ? asset('storage/' . str_replace('public/', '', $settings->municipal_logo)) : null;
    $fmt      = fn($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $vitals   = collect([
        ['Blood pressure',    $C->bloodPressure(),       'mmHg',  $C->bpFlag()],
        ['Temperature',       $fmt($C->temperature),     '°C',    $C->tempFlag()],
        ['Pulse',             $C->pulse,                 'bpm',   $C->pulseFlag()],
        ['Respiratory rate',  $C->resp_rate,             '/min',  null],
        ['Oxygen saturation', $C->o2_sat,                '%',     $C->o2Flag()],
        ['Blood sugar',       $fmt($C->blood_sugar),     'mg/dL' . ($C->blood_sugar ? ($C->sugar_fasting ? ' (fasting)' : ' (random)') : ''), $C->sugarFlag()],
        ['Weight',            $fmt($C->weight_kg),       'kg',    null],
        ['Height',            $fmt($C->height_cm),       'cm',    null],
        ['BMI',               $C->bmi(),                 '',      $C->bmiFlag()],
    ])->filter(fn($v) => $v[1] !== null && $v[1] !== '');
    $patientId = $C->citizen_id ? $settings->formatCitizenId($C->citizen_id) : 'Walk-in';
    // For CSS content strings (page footer): raw output, minus anything that could end the string.
    $cssText  = fn($s) => str_replace(['"', '\\', "\n", "\r", '<', '>'], '', (string) $s);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Health Check-up — {{ $C->patientName() }}</title>
<style>
    /* Plain paper: page margins + running footer. A paper template overrides these. */
    @page {
        size: auto;
        margin: 14mm 15mm 16mm;
        @bottom-left  { content: "{!! $cssText('Health Check-up Record · ' . $C->patientName() . ' · ' . $brgy) !!}"; font: 8pt Arial, sans-serif; color: #6b7280; }
        @bottom-right { content: "Page " counter(page) " of " counter(pages); font: 8pt Arial, sans-serif; color: #6b7280; }
    }
    * { box-sizing: border-box; }
    html, body { background: #e5e7eb; }
    body {
        margin: 0;
        font-family: Georgia, 'Times New Roman', Times, serif;
        font-size: 11pt; line-height: 1.45; color: #111;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }

    /* ── Screen-only toolbar ── */
    .toolbar {
        position: sticky; top: 0; z-index: 10;
        display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 10px; background: #1f2937; color: #e5e7eb;
        font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
    .toolbar button, .toolbar a.btn { font: 600 13px system-ui, sans-serif; padding: 8px 16px; border-radius: 6px; border: 0; cursor: pointer; text-decoration: none; }
    .toolbar .primary { background: #727cf5 !important; color: #fff; }
    .toolbar .ghost   { background: #374151 !important; color: #e5e7eb; }

    /* ── Sheet ── */
    .sheet { width: 8.5in; max-width: 100%; min-height: 11in; margin: 20px auto; padding: 0.6in 0.65in; background: #fff; box-shadow: 0 2px 14px rgba(0,0,0,.15); }

    /* ── Built-in letterhead (hidden when a paper template is used) ── */
    .head { display: grid; grid-template-columns: 78px 1fr 78px; align-items: center; gap: 8px; }
    .head img { width: 72px; height: 72px; object-fit: contain; }
    .head .center { text-align: center; line-height: 1.25; }
    .head .rep  { font-size: 9.5pt; letter-spacing: .6px; }
    .head .loc  { font-size: 10pt; }
    .head .brgy { font-size: 15pt; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }
    .head .office { font-size: 9.5pt; font-style: italic; color: #333; }
    .head-rule { height: 0; border-top: 2.5px solid #1e3a5f; border-bottom: 1px solid #1e3a5f; padding-top: 2px; margin: 8px 0 0; }

    /* ── Title ── */
    .title { text-align: center; margin: 16px 0 14px; }
    .title h1 { margin: 0; font: 700 17pt Georgia, serif; letter-spacing: 2.5px; color: #1e3a5f; }
    .title .sub { font: 9.5pt Arial, sans-serif; color: #555; margin-top: 3px; }

    /* ── Sections ── */
    .section { margin-bottom: 14px; break-inside: avoid; page-break-inside: avoid; }
    .section h2 { margin: 0 0 6px; padding-bottom: 3px; border-bottom: 1.5px solid #1e3a5f; font: 700 8.5pt Arial, sans-serif; letter-spacing: .8px; text-transform: uppercase; color: #1e3a5f; }

    table.info { width: 100%; border-collapse: collapse; font: 10pt Arial, sans-serif; }
    table.info td { padding: 3px 6px 3px 0; vertical-align: top; }
    table.info td.l { width: 18%; color: #555; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .4px; padding-top: 5px; }
    table.info td.v { width: 32%; font-weight: 600; }

    table.rows { width: 100%; border-collapse: collapse; font: 9.5pt Arial, sans-serif; }
    table.rows th { text-align: left; font: 700 7.5pt Arial, sans-serif; letter-spacing: .6px; text-transform: uppercase; color: #444; padding: 4px 6px; border-bottom: 1px solid #1e3a5f; }
    table.rows td { padding: 5px 6px; border-bottom: 1px solid #d5dbe1; vertical-align: top; }
    table.rows tr { break-inside: avoid; page-break-inside: avoid; }
    table.rows .num { white-space: nowrap; font-weight: 700; font-variant-numeric: tabular-nums; }
    table.rows .flag-danger  { font-weight: 700; }
    table.rows .flag-warning { font-style: italic; }
    .empty { font: 9.5pt Arial, sans-serif; color: #666; }

    dl.notes { margin: 0; font: 10pt Arial, sans-serif; }
    dl.notes dt { font-size: 8pt; text-transform: uppercase; letter-spacing: .4px; color: #555; margin-top: 7px; }
    dl.notes dt:first-child { margin-top: 0; }
    dl.notes dd { margin: 1px 0 0; white-space: pre-line; }

    .disclaimer { font: 8pt Arial, sans-serif; color: #555; margin-top: 4px; }

    /* ── Signatures ── */
    .sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 36px; break-inside: avoid; page-break-inside: avoid; }
    .sig .cap { font: 9pt Arial, sans-serif; color: #444; }
    .sig .line { border-bottom: 1px solid #111; margin-top: 30px; text-align: center; font-weight: 700; text-transform: uppercase; min-height: 1.4em; }
    .sig .role { text-align: center; font: 8.5pt Arial, sans-serif; color: #444; margin-top: 2px; }

    .doc-foot { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 26px; padding-top: 6px; border-top: 1px solid #d5dbe1; font: 7.5pt Arial, sans-serif; color: #6b7280; }

    @media print {
        html, body { background: #fff; }
        .toolbar { display: none !important; }
        .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    }
</style>
@include('partials.paper.styles', ['sheet' => '.sheet'])
</head>
<body>
@include('partials.paper.background')

<div class="toolbar">
    <button type="button" class="primary" onclick="window.print()">🖨 Print</button>
    <a class="btn ghost" href="{{ route('health.show', $C) }}">← Back to check-up</a>
    @include('partials.paper.picker')
</div>

<div class="sheet">
@include('partials.paper.open')

    {{-- ── Built-in letterhead ── --}}
    <div class="builtin-letterhead">
        <div class="head">
            <div>@if($logo)<img src="{{ $logo }}" alt="">@endif</div>
            <div class="center">
                <div class="rep">Republic of the Philippines</div>
                @if($settings->province)<div class="loc">Province of {{ $settings->province }}</div>@endif
                @if($settings->municipality)<div class="loc">{{ $settings->municipality }}</div>@endif
                <div class="brgy">{{ $brgy }}</div>
                <div class="office">Barangay Health Station</div>
            </div>
            <div style="text-align:right">@if($munLogo)<img src="{{ $munLogo }}" alt="">@endif</div>
        </div>
        <div class="head-rule"></div>
    </div>

    <div class="title">
        <h1>HEALTH CHECK-UP RECORD</h1>
        <div class="sub">{{ $C->checked_at->format('F d, Y · g:i A') }}</div>
    </div>

    {{-- Patient --}}
    <div class="section">
        <h2>Patient</h2>
        <table class="info">
            <tr>
                <td class="l">Name</td><td class="v">{{ $C->patientName() }}</td>
                <td class="l">Citizen ID</td><td class="v">{{ $patientId }}</td>
            </tr>
            <tr>
                <td class="l">Age / Sex</td>
                <td class="v">{{ collect([$C->patientAge() !== null ? $C->patientAge() . ' yrs old' : null, $C->patientSex()])->filter()->implode(' · ') ?: '—' }}</td>
                <td class="l">Attended by</td><td class="v">{{ $C->attended_by ?: '—' }}</td>
            </tr>
            <tr>
                <td class="l">Type of check-up</td><td class="v" colspan="3">{{ $C->checkup_type ?: '—' }}</td>
            </tr>
            @if($C->citizen)
            <tr>
                <td class="l">Address</td><td class="v" colspan="3">{{ $C->citizen->full_address ?: '—' }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Vital signs --}}
    <div class="section">
        <h2>Vital signs</h2>
        @if($vitals->isEmpty())
            <p class="empty">No vital signs recorded.</p>
        @else
        <table class="rows">
            <thead><tr><th style="width:34%">Measurement</th><th style="width:30%">Reading</th><th>Screening flag</th></tr></thead>
            <tbody>
                @foreach($vitals as [$label, $value, $unit, $flag])
                <tr>
                    <td>{{ $label }}</td>
                    <td class="num">{{ $value }} <span style="font-weight:400">{{ $unit }}</span></td>
                    <td class="{{ $flag ? 'flag-' . $flag[1] : '' }}">{{ $flag[0] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="disclaimer">Flags follow common adult screening guides and are not a diagnosis.</p>
        @endif
    </div>

    {{-- Consultation --}}
    <div class="section">
        <h2>Consultation</h2>
        <dl class="notes">
            <dt>Complaint</dt><dd>{{ $C->complaint ?: '—' }}</dd>
            <dt>Findings / assessment</dt><dd>{{ $C->findings ?: '—' }}</dd>
            <dt>Advice / treatment</dt><dd>{{ $C->advice ?: '—' }}</dd>
            @if($C->referred_to)<dt>Referred to</dt><dd>{{ $C->referred_to }}</dd>@endif
            @if($C->follow_up_on)<dt>Follow-up</dt><dd>{{ $C->follow_up_on->format('F d, Y') }}</dd>@endif
        </dl>
    </div>

    {{-- Medicines --}}
    <div class="section">
        <h2>Medicines given</h2>
        @if($C->medicines->isEmpty())
            <p class="empty">No medicines given from stock.</p>
        @else
        <table class="rows">
            <thead><tr><th style="width:40%">Medicine</th><th style="width:18%">Quantity</th><th>How to take</th></tr></thead>
            <tbody>
                @foreach($C->medicines as $m)
                <tr>
                    <td>{{ $m->name }}</td>
                    <td class="num">{{ $m->quantity }} <span style="font-weight:400">{{ $m->unit }}</span></td>
                    <td>{{ $m->instructions ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="sigs">
        <div class="sig">
            <div class="cap">Attended by:</div>
            <div class="line">{{ $C->attended_by }}</div>
            <div class="role">Health Worker</div>
        </div>
        <div class="sig">
            <div class="cap">Received by:</div>
            <div class="line">{{ $C->patientName() }}</div>
            <div class="role">Patient</div>
        </div>
    </div>

    <div class="doc-foot">
        <span>{{ $brgy }}{{ $location ? ', ' . $location : '' }}</span>
        <span>Printed {{ now()->format('M d, Y g:i A') }} by {{ auth()->user()->name ?? '—' }}</span>
    </div>

@include('partials.paper.close')
</div>
</body>
</html>
