<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Slip #{{ $release->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 18mm 16mm;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #111;
            margin-bottom: 16px;
        }
        .logo-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            width: 76px;
            shrink: 0;
        }
        .logo-col img {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }
        .logo-col .logo-placeholder {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            color: #aaa;
        }
        .logo-col .logo-label {
            font-size: 8px;
            color: #888;
            text-align: center;
            line-height: 1.3;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .header-text { flex: 1; text-align: center; }
        .header-text .republic { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #444; }
        .header-text .brgy { font-size: 17px; font-weight: 700; text-transform: uppercase; margin: 4px 0 2px; }
        .header-text .address { font-size: 10px; color: #555; }
        .header-text .contact { font-size: 10px; color: #555; margin-top: 2px; }

        /* ── Title banner ── */
        .slip-title {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 0;
            border-top: 1.5px solid #111;
            border-bottom: 1.5px solid #111;
            margin-bottom: 16px;
        }

        /* ── Meta row ── */
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 11px;
        }
        .meta-row .field { display: flex; gap: 4px; }
        .meta-row .label { color: #555; }
        .meta-row .value { font-weight: 600; }

        /* ── Section heading ── */
        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
            margin-bottom: 8px;
            margin-top: 14px;
        }

        /* ── Citizen info ── */
        .citizen-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .citizen-grid .row { display: flex; gap: 4px; }
        .citizen-grid .lbl { color: #555; white-space: nowrap; }
        .citizen-grid .val { font-weight: 600; }

        /* ── Items table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 4px;
        }
        thead tr { background: #f0f0f0; }
        th, td {
            border: 1px solid #bbb;
            padding: 5px 8px;
            text-align: left;
        }
        th { font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
        td.center, th.center { text-align: center; }
        tr.total-row td { font-weight: 700; background: #f8f8f8; }

        /* ── Purpose ── */
        .purpose-box {
            border: 1px solid #bbb;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 11px;
            min-height: 36px;
            margin-top: 4px;
        }

        /* ── Signatures ── */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }
        .sig-block { text-align: center; }
        .sig-line {
            border-top: 1px solid #111;
            margin-bottom: 4px;
            padding-top: 2px;
        }
        .sig-name { font-weight: 700; font-size: 11px; text-transform: uppercase; }
        .sig-role { font-size: 10px; color: #555; }

        /* ── Footer note ── */
        .footer-note {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        /* ── Status stamp ── */
        .stamp {
            position: absolute;
            top: 220px;
            right: 60px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transform: rotate(-20deg);
            opacity: 0.18;
            pointer-events: none;
        }
        .stamp.released { border-color: #16a34a; color: #16a34a; }
        .stamp.rejected { border-color: #dc2626; color: #dc2626; }
        .stamp.approved { border-color: #2563eb; color: #2563eb; }
        .stamp.pending  { border-color: #d97706; color: #d97706; }

        /* ── Print controls (hidden on print) ── */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            font-size: 13px;
            gap: 12px;
        }
        .print-bar a { color: #94a3b8; text-decoration: none; font-size: 12px; }
        .print-bar a:hover { color: #fff; }
        .print-bar button {
            background: #4361ee;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media print {
            .print-bar { display: none !important; }
            body { margin: 0; }
            .page { padding: 12mm 14mm; margin: 0; width: 100%; }
        }

        @media screen {
            body { background: #e2e8f0; padding-top: 52px; }
            .page {
                box-shadow: 0 4px 24px rgba(0,0,0,.15);
                margin: 20px auto 40px;
                position: relative;
            }
        }
    </style>
</head>
<body>

{{-- Print bar (screen only) --}}
<div class="print-bar">
    <div style="display:flex;align-items:center;gap:16px;">
        <a href="{{ route('inventory.releases.show', $release) }}">← Back to Release</a>
        <span style="color:#64748b">Release Slip #{{ $release->id }}</span>
    </div>
    <button onclick="window.print()">🖨 Print</button>
</div>

<div class="page">

    {{-- Status stamp --}}
    <div class="stamp {{ $release->status }}">{{ $release->status }}</div>

    {{-- Header --}}
    @php
        $logoPath     = $settings->logo         ? asset('storage/' . str_replace('public/', '', $settings->logo))         : null;
        $muniLogoPath = $settings->municipal_logo ? asset('storage/' . str_replace('public/', '', $settings->municipal_logo)) : null;
    @endphp
    <div class="header">
        {{-- Barangay logo --}}
        <div class="logo-col">
            @if($logoPath)
                <img src="{{ $logoPath }}" alt="Barangay Logo">
            @else
                <div class="logo-placeholder">B</div>
            @endif
            <span class="logo-label">Barangay<br>Logo / Seal</span>
        </div>

        <div class="header-text">
            <p class="republic">Republic of the Philippines</p>
            <p class="brgy">{{ $settings->barangay_name ?? 'Barangay' }}</p>
            <p class="address">{{ $settings->fullAddress() }}</p>
            @if($settings->contact)<p class="contact">Tel: {{ $settings->contact }}</p>@endif
        </div>

        {{-- Municipal logo --}}
        <div class="logo-col">
            @if($muniLogoPath)
                <img src="{{ $muniLogoPath }}" alt="Municipal Logo">
            @else
                <div class="logo-placeholder">M</div>
            @endif
            <span class="logo-label">Municipal<br>Logo / Seal</span>
        </div>
    </div>

    {{-- Title --}}
    <div class="slip-title">Inventory Release Slip</div>

    {{-- Meta --}}
    <div class="meta-row">
        <div class="field">
            <span class="label">Release No.:</span>
            <span class="value">#{{ str_pad($release->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="field">
            <span class="label">Date:</span>
            <span class="value">
                {{ ($release->released_at ?? $release->created_at)->format('F j, Y') }}
            </span>
        </div>
        <div class="field">
            <span class="label">Status:</span>
            <span class="value" style="text-transform:capitalize">{{ $release->status }}</span>
        </div>
    </div>

    {{-- Citizen --}}
    <div class="section-label">Recipient / Citizen</div>
    @php $c = $release->citizen; @endphp
    <div class="citizen-grid">
        <div class="row">
            <span class="lbl">Name:</span>
            <span class="val">{{ $c?->full_name ?? 'Unknown' }}</span>
        </div>
        <div class="row">
            <span class="lbl">Contact:</span>
            <span class="val">{{ $c?->contact ?: '—' }}</span>
        </div>
        <div class="row" style="grid-column: span 2">
            <span class="lbl">Address:</span>
            <span class="val">{{ $c?->complete_address ?: '—' }}</span>
        </div>
    </div>

    {{-- Items --}}
    <div class="section-label">Items Released</div>
    <table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Item / Description</th>
                <th>Category</th>
                <th class="center" style="width:70px">Qty</th>
                <th class="center" style="width:60px">Unit</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($release->items as $i => $ri)
            @php $totalQty += $ri->quantity; @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $ri->item?->name ?? '—' }}</td>
                <td>{{ $ri->item?->category?->name ?? '—' }}</td>
                <td class="center">{{ number_format($ri->quantity) }}</td>
                <td class="center">{{ $ri->item?->unit ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align:right; font-size:10px; text-transform:uppercase; letter-spacing:.3px;">Total Quantity</td>
                <td class="center">{{ number_format($totalQty) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- Purpose --}}
    <div class="section-label">Purpose / Remarks</div>
    <div class="purpose-box">{{ $release->purpose ?: ($release->remarks ?: '—') }}</div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-block">
            <div style="height:48px;"></div>
            <div class="sig-line"></div>
            <p class="sig-name">{{ $c?->full_name ?? 'Recipient' }}</p>
            <p class="sig-role">Recipient's Signature</p>
        </div>
        <div class="sig-block">
            <div style="height:48px;"></div>
            <div class="sig-line"></div>
            <p class="sig-name">{{ $release->releasedBy?->name ?? '_______________' }}</p>
            <p class="sig-role">Released By</p>
        </div>
        <div class="sig-block">
            <div style="height:48px;"></div>
            <div class="sig-line"></div>
            <p class="sig-name">{{ $release->approvedBy?->name ?? $settings->captain_name ?? '_______________' }}</p>
            <p class="sig-role">Approved By / Barangay Captain</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer-note">
        Generated on {{ now()->format('F j, Y \a\t g:i A') }} &nbsp;·&nbsp;
        {{ $settings->barangay_name ?? 'Barangay' }} Inventory System
        &nbsp;·&nbsp; This slip serves as an official record of the items released.
    </div>

</div>
</body>
</html>
