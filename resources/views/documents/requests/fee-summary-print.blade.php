@php
    $brgy     = $settings->barangay_name ?: 'Barangay';
    $location = collect([$settings->municipality, $settings->province])->filter()->implode(', ');
    $logo     = $settings->logo           ? asset('storage/' . str_replace('public/', '', $settings->logo))           : null;
    $munLogo  = $settings->municipal_logo ? asset('storage/' . str_replace('public/', '', $settings->municipal_logo)) : null;
    $peso     = fn($v) => '₱ ' . number_format($v, 2);
    $t        = $summary['totals'];
    $feeText  = fn($r) => $r['fee_min'] == $r['fee_max'] ? $peso($r['fee_min']) : $peso($r['fee_min']) . ' – ' . $peso($r['fee_max']);
    // For CSS content strings (page footer): raw output, minus anything that could end the string.
    $cssText  = fn($s) => str_replace(['"', '\\', "\n", "\r", '<', '>'], '', (string) $s);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document Fee Summary — {{ $period }}</title>
<style>
    /* Plain paper: page margins + running footer. A paper template overrides these. */
    @page {
        size: auto;
        margin: 14mm 15mm 16mm;
        @bottom-left  { content: "{!! $cssText('Document Fee Summary · ' . $period . ' · ' . $brgy) !!}"; font: 8pt Arial, sans-serif; color: #6b7280; }
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
    .title { text-align: center; margin: 16px 0 12px; }
    .title h1 { margin: 0; font: 700 17pt Georgia, serif; letter-spacing: 2.5px; color: #1e3a5f; }
    .title .period { font: 700 10.5pt Arial, sans-serif; margin-top: 4px; }
    .title .filters { font: 9pt Arial, sans-serif; color: #555; margin-top: 2px; }

    /* ── Totals ── */
    .boxes { display: grid; grid-template-columns: repeat(4, 1fr); border: 1.5px solid #1e3a5f; margin-bottom: 14px; font-family: Arial, sans-serif; }
    .box { padding: 6px 10px; border-right: 1px solid #c7ced6; }
    .box:last-child { border-right: 0; }
    .box .l { font-size: 7.5pt; letter-spacing: .6px; text-transform: uppercase; color: #555; }
    .box .v { font-size: 13pt; font-weight: 700; margin-top: 1px; }
    .box.main { background: #eef2f7; }

    /* ── Table ── */
    table.rows { width: 100%; border-collapse: collapse; font: 9.5pt Arial, sans-serif; }
    table.rows th { text-align: right; font: 700 7.5pt Arial, sans-serif; letter-spacing: .6px; text-transform: uppercase; color: #444; padding: 5px 6px; border-bottom: 1.5px solid #1e3a5f; }
    table.rows th:first-child, table.rows td:first-child { text-align: left; }
    table.rows td { padding: 5px 6px; border-bottom: 1px solid #d5dbe1; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    table.rows td:first-child { white-space: normal; }
    table.rows tr { break-inside: avoid; page-break-inside: avoid; }
    table.rows tfoot td { font-weight: 700; border-bottom: 0; border-top: 1.5px solid #1e3a5f; padding-top: 6px; }
    table.rows .strong { font-weight: 700; }
    table.rows .empty { text-align: center; padding: 18px; color: #666; }

    .notes { margin-top: 10px; font: 8.5pt Arial, sans-serif; color: #444; line-height: 1.6; }

    /* ── Signatures ── */
    .sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; break-inside: avoid; page-break-inside: avoid; }
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
    <a class="btn ghost" href="{{ route('documents.requests.index', request()->query()) }}">← Back to requests</a>
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
                <div class="office">Office of the Punong Barangay</div>
            </div>
            <div style="text-align:right">@if($munLogo)<img src="{{ $munLogo }}" alt="">@endif</div>
        </div>
        <div class="head-rule"></div>
    </div>

    <div class="title">
        <h1>DOCUMENT FEE SUMMARY</h1>
        <div class="period">{{ $period }}</div>
        <div class="filters">{{ $filters->map(fn($v, $k) => "$k: $v")->implode(' · ') ?: 'All document requests' }}</div>
    </div>

    <div class="boxes">
        <div class="box main"><div class="l">Possible income</div><div class="v">{{ $peso($t['possible']) }}</div></div>
        <div class="box"><div class="l">Collected</div><div class="v">{{ $peso($t['collected']) }}</div></div>
        <div class="box"><div class="l">Awaiting release</div><div class="v">{{ $peso($t['awaiting']) }}</div></div>
        <div class="box"><div class="l">Short</div><div class="v">{{ $peso($t['short']) }}</div></div>
    </div>

    <table class="rows">
        <thead>
            <tr>
                <th>Document type</th>
                <th>Fee</th>
                <th>Requests</th>
                <th>Released</th>
                <th>Possible income</th>
                <th>Collected</th>
                <th>Awaiting</th>
                <th>Short</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['rows'] as $r)
            <tr>
                <td>{{ $r['name'] }}</td>
                <td>{{ $feeText($r) }}</td>
                <td>{{ number_format($r['requests']) }}</td>
                <td>{{ number_format($r['released']) }}</td>
                <td class="strong">{{ $peso($r['possible']) }}</td>
                <td>{{ $peso($r['collected']) }}</td>
                <td>{{ $peso($r['awaiting']) }}</td>
                <td>{{ $r['short'] > 0 ? $peso($r['short']) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty">No paid document requests for this period.</td></tr>
            @endforelse
        </tbody>
        @if($summary['rows']->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td>{{ number_format($t['requests']) }}</td>
                <td>{{ number_format($t['released']) }}</td>
                <td>{{ $peso($t['possible']) }}</td>
                <td>{{ $peso($t['collected']) }}</td>
                <td>{{ $peso($t['awaiting']) }}</td>
                <td>{{ $t['short'] > 0 ? $peso($t['short']) : '—' }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="notes">
        <div>Possible income = all paid-document requests not rejected or voided (Collected + Awaiting release + Short).</div>
        @if($summary['free'])<div>{{ number_format($summary['free']) }} free document{{ $summary['free'] > 1 ? 's' : '' }} requested (no fee).</div>@endif
        @if($summary['excluded'])<div>{{ number_format($summary['excluded']) }} rejected or voided request{{ $summary['excluded'] > 1 ? 's' : '' }} ({{ $peso($summary['excluded_amount']) }}) not counted.</div>@endif
    </div>

    <div class="sigs">
        <div class="sig">
            <div class="cap">Prepared by:</div>
            <div class="line">{{ auth()->user()->name }}</div>
            <div class="role">Barangay Staff</div>
        </div>
        <div class="sig">
            <div class="cap">Noted by:</div>
            <div class="line">{{ $settings->captain_name }}</div>
            <div class="role">{{ $settings->captain_position ?: 'Punong Barangay' }}</div>
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
