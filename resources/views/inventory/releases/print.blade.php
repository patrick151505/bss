@php
    $c         = $release->citizen;
    $brgy      = $settings->barangay_name ?: 'Barangay';
    $location  = collect([$settings->municipality, $settings->province])->filter()->implode(', ');
    $logo      = $settings->logo           ? asset('storage/' . str_replace('public/', '', $settings->logo))           : null;
    $munLogo   = $settings->municipal_logo ? asset('storage/' . str_replace('public/', '', $settings->municipal_logo)) : null;
    $slipNo    = str_pad($release->id, 6, '0', STR_PAD_LEFT);
    $color     = ['released' => '#15803d', 'approved' => '#1d4ed8', 'pending' => '#b45309', 'rejected' => '#b91c1c'][$release->status] ?? '#374151';
    $items     = $release->items;
    $totalQty  = $items->sum('quantity');
    $address   = $c?->full_address ?: null;
    $when      = fn($d) => $d ? $d->format('M d, Y g:i A') : null;
    // For CSS content strings (page footer): raw output, minus anything that could end the string.
    $cssText   = fn($s) => str_replace(['"', '\\', "\n", "\r", '<', '>'], '', (string) $s);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Release Slip #{{ $slipNo }}</title>
<style>
    /* Plain paper: page margins + running footer (slip no. / page x of y). */
    @page {
        size: auto;
        margin: 14mm 15mm 16mm;
        @bottom-left  { content: "{!! $cssText('Release Slip #' . $slipNo . ' · ' . $brgy) !!}"; font: 8pt Arial, sans-serif; color: #6b7280; }
        @bottom-right { content: "Page " counter(page) " of " counter(pages); font: 8pt Arial, sans-serif; color: #6b7280; }
    }
    * { box-sizing: border-box; }
    html, body { background: #e5e7eb; }
    body {
        margin: 0;
        font-family: Georgia, 'Times New Roman', Times, serif;
        font-size: 11pt;
        line-height: 1.45;
        color: #111;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        counter-reset: sec;
    }

    /* ── Screen-only toolbar ── */
    .toolbar {
        position: sticky; top: 0; z-index: 10;
        display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 10px; background: #1f2937; color: #e5e7eb;
        font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
    .toolbar button, .toolbar a.btn {
        font: 600 13px system-ui, sans-serif; padding: 8px 16px; border-radius: 6px;
        border: 0; cursor: pointer; text-decoration: none;
    }
    .toolbar .primary { background: #727cf5 !important; color: #fff; }
    .toolbar .ghost   { background: #374151 !important; color: #e5e7eb; }

    /* ── Sheet ── */
    .sheet {
        width: 8.5in; max-width: 100%; min-height: 11in;
        margin: 20px auto; padding: 0.6in 0.65in;
        background: #fff; box-shadow: 0 2px 14px rgba(0,0,0,.15);
    }

    /* ── Letterhead (hidden when a paper template is used) ── */
    .head { display: grid; grid-template-columns: 78px 1fr 78px; align-items: center; gap: 8px; }
    .head img { width: 72px; height: 72px; object-fit: contain; }
    .head .center { text-align: center; line-height: 1.25; }
    .head .rep  { font-size: 9.5pt; letter-spacing: .6px; }
    .head .loc  { font-size: 10pt; }
    .head .brgy { font-size: 15pt; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }
    .head .office { font-size: 9.5pt; font-style: italic; color: #333; }
    .head .contact { font: 8.5pt Arial, sans-serif; color: #555; margin-top: 2px; }
    .head-rule { height: 0; border-top: 2.5px solid #1e3a5f; border-bottom: 1px solid #1e3a5f; padding-top: 2px; margin: 8px 0 0; }

    /* ── Title row ── */
    .title-row { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin: 16px 0 4px; }
    .title-row h1 { margin: 0; font: 700 18pt Georgia, serif; letter-spacing: 2.5px; color: #1e3a5f; }
    .title-row .sub { font: 9pt Arial, sans-serif; color: #555; letter-spacing: .4px; margin-top: 2px; }
    .casebox { border: 1.5px solid #1e3a5f; border-collapse: collapse; font-family: Arial, sans-serif; min-width: 2.6in; }
    .casebox td { padding: 3px 8px; font-size: 9pt; border-bottom: 1px solid #c7ced6; }
    .casebox tr:last-child td { border-bottom: 0; }
    .casebox td.k { color: #555; text-transform: uppercase; font-size: 7.5pt; letter-spacing: .6px; width: 1%; white-space: nowrap; }
    .casebox td.v { font-weight: 700; }
    .casebox .no { font-family: 'Courier New', monospace; font-size: 11pt; letter-spacing: .5px; }
    .pill { display: inline-block; padding: 0 8px; border-radius: 99px; color: #fff; font-size: 8pt; letter-spacing: .6px; text-transform: uppercase; }

    /* ── Numbered sections (I, II, III … via CSS counters) ── */
    .sec { margin-top: 13px; }
    .sec > h2 {
        counter-increment: sec;
        display: flex; align-items: center; gap: 8px;
        margin: 0 0 8px; padding-bottom: 3px;
        font: 700 9.5pt Arial, sans-serif; letter-spacing: 1.5px; text-transform: uppercase; color: #1e3a5f;
        border-bottom: 1.5px solid #1e3a5f;
        break-after: avoid; page-break-after: avoid;
    }
    .sec > h2::before { content: counter(sec, upper-roman) "."; min-width: 22px; }

    /* ── Form-style fields ── */
    .fields { display: grid; grid-template-columns: 1fr 1fr; column-gap: 26px; row-gap: 5px; }
    .f { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
    .f.full { grid-column: 1 / -1; }
    .f .l { font: 700 7.5pt Arial, sans-serif; letter-spacing: .6px; text-transform: uppercase; color: #444; white-space: nowrap; }
    .f .v { flex: 1; min-width: 0; border-bottom: 1px solid #555; padding: 0 3px; min-height: 1.35em; }

    /* ── Items table ── */
    table.items { width: 100%; border-collapse: collapse; font-size: 10.5pt; }
    table.items th {
        text-align: left; font: 700 7.5pt Arial, sans-serif; letter-spacing: .8px; text-transform: uppercase; color: #444;
        padding: 5px 6px; border-bottom: 1.5px solid #1e3a5f;
    }
    table.items td { padding: 5px 6px; border-bottom: 1px solid #d5dbe1; vertical-align: top; }
    table.items tr { break-inside: avoid; page-break-inside: avoid; }
    table.items .c { text-align: center; }
    table.items .num { width: 30px; text-align: center; color: #555; font: 9pt Arial, sans-serif; }
    table.items .sku { font: 8.5pt 'Courier New', monospace; color: #444; white-space: nowrap; }
    table.items .cat { font: 9pt Arial, sans-serif; color: #444; }
    table.items .qty { font-weight: 700; font-size: 11.5pt; }
    table.items tfoot td {
        border-bottom: 0; border-top: 1.5px solid #1e3a5f; padding-top: 6px;
        font: 700 8.5pt Arial, sans-serif; letter-spacing: .5px; text-transform: uppercase; color: #1e3a5f;
    }
    table.items tfoot .qty { font: 700 11.5pt Georgia, serif; color: #111; }

    .note { min-height: 2.6em; padding: 2px 4px; border-bottom: 1px solid #555; white-space: pre-line; }
    .muted { color: #6b7280; font: italic 9.5pt Georgia, serif; }

    /* ── Processing timeline ── */
    .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .step { border: 1px solid #c7ced6; border-top: 3px solid #c7ced6; padding: 6px 8px; font-family: Arial, sans-serif; }
    .step.done { border-top-color: #1e3a5f; }
    .step .t { font: 700 7.5pt Arial, sans-serif; letter-spacing: .8px; text-transform: uppercase; color: #1e3a5f; }
    .step .d { font-size: 9.5pt; font-weight: 700; color: #111; margin-top: 1px; }
    .step .b { font-size: 8.5pt; color: #555; }
    .step:not(.done) .d { color: #9ca3af; font-weight: 400; }

    /* ── Acknowledgement + signatures ── */
    .ack { position: relative; margin-top: 16px; }
    /* room on the right for the rotated status stamp */
    .ack p { margin: 0; font-style: italic; font-size: 10pt; text-align: justify; padding-right: 2.25in; }
    .stamp {
        position: absolute; right: 6px; top: -10px; transform: rotate(-8deg);
        border: 2.5px solid currentColor; border-radius: 6px; padding: 2px 12px;
        font: 800 13pt Arial, sans-serif; letter-spacing: 3px; text-transform: uppercase; opacity: .85;
    }
    .sigs { display: grid; grid-template-columns: repeat(3, 1fr); column-gap: 26px; margin-top: 24px; }
    .sig .cap { font: 700 7.5pt Arial, sans-serif; letter-spacing: .8px; text-transform: uppercase; color: #444; margin-bottom: 24px; }
    .sig .line { border-top: 1px solid #111; padding-top: 3px; text-align: center; font-weight: 700; text-transform: uppercase; min-height: 1.4em; font-size: 10pt; }
    .sig .role { text-align: center; font: 8.5pt Arial, sans-serif; color: #444; }
    .sig .date { margin-top: 8px; font: 8.5pt Arial, sans-serif; color: #444; }
    .sig .date span { display: inline-block; min-width: 1.1in; border-bottom: 1px solid #999; }

    .doc-foot { margin-top: 16px; padding-top: 5px; border-top: 1px solid #c7ced6; display: flex; justify-content: space-between; gap: 12px;
                font: 8pt Arial, sans-serif; color: #6b7280; }

    .keep { break-inside: avoid; page-break-inside: avoid; }

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
    <a class="btn ghost" href="{{ route('inventory.releases.show', $release) }}">← Back to release</a>
    @include('partials.paper.picker')
</div>

<div class="sheet">
@include('partials.paper.open')

    {{-- ── Letterhead ── --}}
    <div class="builtin-letterhead">
        <div class="head">
            <div>@if($logo)<img src="{{ $logo }}" alt="">@endif</div>
            <div class="center">
                <div class="rep">Republic of the Philippines</div>
                @if($settings->province)<div class="loc">Province of {{ $settings->province }}</div>@endif
                @if($settings->municipality)<div class="loc">{{ $settings->municipality }}</div>@endif
                <div class="brgy">{{ $brgy }}</div>
                <div class="office">Office of the Punong Barangay</div>
                @if($settings->contact)<div class="contact">Tel: {{ $settings->contact }}</div>@endif
            </div>
            <div style="text-align:right">@if($munLogo)<img src="{{ $munLogo }}" alt="">@endif</div>
        </div>
        <div class="head-rule"></div>
    </div>

    {{-- ── Title + slip box ── --}}
    <div class="title-row keep">
        <div>
            <h1>INVENTORY RELEASE SLIP</h1>
            <div class="sub">Acknowledgement of items received from the barangay</div>
        </div>
        <table class="casebox">
            <tr><td class="k">Slip No.</td><td class="v no">#{{ $slipNo }}</td></tr>
            <tr><td class="k">Date</td><td class="v">{{ ($release->released_at ?? $release->created_at)->format('F d, Y') }}</td></tr>
            <tr><td class="k">Status</td><td class="v"><span class="pill" style="background: {{ $color }} !important;">{{ $release->status }}</span></td></tr>
        </table>
    </div>

    {{-- ── I. Recipient ── --}}
    <div class="sec keep">
        <h2>Recipient</h2>
        <div class="fields">
            <div class="f"><span class="l">Name</span><span class="v" style="font-weight:700;text-transform:uppercase;">{{ $c?->full_name ?? '' }}</span></div>
            <div class="f"><span class="l">Citizen ID</span><span class="v" style="font-family:'Courier New',monospace;">{{ $c ? $settings->formatCitizenId($c->id) : '' }}</span></div>
            <div class="f full"><span class="l">Address</span><span class="v">{{ $address }}</span></div>
            <div class="f"><span class="l">Contact No.</span><span class="v">{{ $c?->contact }}</span></div>
            <div class="f"><span class="l">Requested</span><span class="v">{{ $release->created_at?->format('M d, Y') }}</span></div>
        </div>
    </div>

    {{-- ── II. Items ── --}}
    <div class="sec">
        <h2>Items Released</h2>
        <table class="items">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th>Item / Description</th>
                    <th style="width:16%">SKU</th>
                    <th style="width:20%">Category</th>
                    <th class="c" style="width:10%">Qty</th>
                    <th class="c" style="width:11%">Unit</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $i => $ri)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>
                        {{ $ri->item?->name ?? '—' }}
                        @if($ri->item?->description)<div class="cat">{{ \Illuminate\Support\Str::limit($ri->item->description, 90) }}</div>@endif
                    </td>
                    <td class="sku">{{ $ri->item?->sku ?: '—' }}</td>
                    <td class="cat">{{ $ri->item?->category?->name ?? '—' }}</td>
                    <td class="c qty">{{ number_format($ri->quantity) }}</td>
                    <td class="c">{{ $ri->item?->unit ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="text-align:center;padding:14px;">No items on this slip.</td></tr>
            @endforelse
            </tbody>
            @if($items->isNotEmpty())
            <tfoot>
                <tr>
                    <td></td>
                    <td colspan="3" style="text-align:right;">Total: {{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}</td>
                    <td class="c qty">{{ number_format($totalQty) }}</td>
                    <td class="c" style="text-transform:none;font-weight:400;color:#555;">{{ \Illuminate\Support\Str::plural('unit', $totalQty) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- ── III. Purpose / remarks ── --}}
    <div class="sec keep">
        <h2>Purpose / Remarks</h2>
        <div class="fields">
            <div class="f full"><span class="l">Purpose</span><span class="v">{{ $release->purpose }}</span></div>
            <div class="f full"><span class="l">Remarks</span><span class="v">{{ $release->remarks }}</span></div>
        </div>
    </div>

    {{-- ── IV. Processing ── --}}
    <div class="sec keep">
        <h2>Processing</h2>
        <div class="steps">
            <div class="step done">
                <div class="t">Requested</div>
                <div class="d">{{ $when($release->created_at) }}</div>
            </div>
            <div class="step {{ $release->approved_at ? 'done' : '' }}">
                <div class="t">{{ $release->status === 'rejected' ? 'Rejected' : 'Approved' }}</div>
                <div class="d">{{ $when($release->approved_at) ?? 'Not yet' }}</div>
                @if($release->approvedBy)<div class="b">by {{ $release->approvedBy->name }}</div>@endif
            </div>
            <div class="step {{ $release->released_at ? 'done' : '' }}">
                <div class="t">Released</div>
                <div class="d">{{ $when($release->released_at) ?? 'Not yet' }}</div>
                @if($release->releasedBy)<div class="b">by {{ $release->releasedBy->name }}</div>@endif
            </div>
        </div>
    </div>

    {{-- ── Acknowledgement + signatures ── --}}
    <div class="keep">
        <div class="ack">
            <span class="stamp" style="color: {{ $color }};">{{ $release->status }}</span>
            <p>
                I hereby acknowledge receipt of the {{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}
                ({{ number_format($totalQty) }} {{ \Illuminate\Support\Str::plural('unit', $totalQty) }}) listed above, in good order and condition.
            </p>
        </div>
        <div class="sigs">
            <div class="sig">
                <div class="cap">Received by</div>
                <div class="line">{{ $c?->full_name ?? '' }}</div>
                <div class="role">Signature over printed name</div>
                <div class="date">Date: <span></span></div>
            </div>
            <div class="sig">
                <div class="cap">Released by</div>
                <div class="line">{{ $release->releasedBy?->name ?? '' }}</div>
                <div class="role">Barangay Staff</div>
            </div>
            <div class="sig">
                <div class="cap">Approved by</div>
                <div class="line">{{ $release->approvedBy?->name ?? $settings->captain_name ?? '' }}</div>
                <div class="role">{{ $release->approvedBy ? 'Approving Officer' : ($settings->captain_position ?: 'Punong Barangay') }}</div>
            </div>
        </div>
    </div>

    <div class="doc-foot">
        <span>{{ $brgy }}{{ $location ? ', ' . $location : '' }} · This slip is the official record of the items released.</span>
        <span>Printed {{ now()->format('M d, Y g:i A') }} by {{ auth()->user()->name ?? '—' }}</span>
    </div>

@include('partials.paper.close')
</div>
</body>
</html>
