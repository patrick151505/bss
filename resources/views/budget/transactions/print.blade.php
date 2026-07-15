<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $transaction->voucher_no }} — Disbursement Voucher</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    font-size: 10pt;
    color: #000;
    background: #fff;
}
.page {
    width: 215mm;
    min-height: 280mm;
    margin: 0 auto;
    padding: 8mm 10mm;
}

/* Header */
.header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4mm; }
.header .logo { width: 18mm; height: 18mm; object-fit: contain; }
.header .center { text-align: center; flex: 1; }
.header .center p { font-size: 9pt; line-height: 1.5; }
.header .center .title { font-size: 13pt; font-weight: bold; margin-top: 2mm; }

/* Main form table */
table.main { width: 100%; border-collapse: collapse; }
table.main td, table.main th {
    border: 1px solid #000;
    padding: 1.5mm 2mm;
    vertical-align: top;
    font-size: 9pt;
}
table.main th { text-align: center; font-weight: bold; }

.label { font-size: 7.5pt; color: #333; }
.value { font-weight: bold; border-bottom: 1px solid #000; display: inline-block; min-width: 40mm; }
.value-line { border-bottom: 1px solid #000; width: 100%; display: block; min-height: 4mm; }

/* Section titles */
.section-title { font-weight: bold; text-align: center; padding: 1mm 0; }

/* Particulars table inside */
table.inner { width: 100%; border-collapse: collapse; }
table.inner td { border: none; padding: 0.5mm 1mm; font-size: 9pt; }

/* Tax rows */
.tax-row td { font-size: 8.5pt; padding: 0.3mm 2mm; }

/* Signature section */
table.sigs { width: 100%; border-collapse: collapse; margin-top: 0; }
table.sigs td {
    border: 1px solid #000;
    padding: 2mm;
    vertical-align: top;
    font-size: 8.5pt;
    width: 33.33%;
}
.sig-label { font-size: 7.5pt; font-weight: bold; }
.sig-line { border-top: 1px solid #000; margin-top: 6mm; padding-top: 1mm; font-size: 8pt; }

/* Accounting entries */
table.entries { width: 100%; border-collapse: collapse; }
table.entries th, table.entries td {
    border: 1px solid #000;
    padding: 1mm 2mm;
    font-size: 8.5pt;
}
table.entries th { background: #f5f5f5; text-align: center; }

/* Payment section */
table.payment { width: 100%; border-collapse: collapse; }
table.payment td {
    border: 1px solid #000;
    padding: 1.5mm 2mm;
    font-size: 9pt;
    vertical-align: top;
}

/* Print button — hidden on print */
.no-print {
    position: fixed; top: 10px; right: 10px;
    display: flex; gap: 8px; z-index: 9999;
}
.no-print button {
    padding: 6px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 10pt;
}
.btn-print { background: #1a56db; color: #fff; }
.btn-close { background: #e5e7eb; color: #374151; }

@media print {
    .no-print { display: none; }
    .page { margin: 0; padding: 6mm 8mm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>

@php
    $s            = $setting;
    $punongBrgy   = $siteSettings->captain_name ?? $s->punong_barangay ?? '';
    $net          = $transaction->netAmount();
    $tax       = (float) $transaction->tax_withheld;
    $gross     = (float) $transaction->amount;
    $lines     = $transaction->lines;
    $firstLine = $lines->first();
    $fund      = $firstLine?->lineItem?->program?->name ?? '';
    $tin       = $transaction->payee_tin ?? '';
    $address   = $transaction->payee_address ?? '';

    // Which BIR tax row carries the withheld amount
    $taxType = $transaction->tax_type ?? '';
    $taxRows = ['sales_vat' => 0.00, 'sales_nv' => 0.00, 'ewt_services' => 0.00, 'ewt_goods' => 0.00, 'other' => 0.00];
    if ($taxType === 'nv_3')           { $taxRows['sales_nv']     = $tax; }
    elseif ($taxType === 'ewt_services_2') { $taxRows['ewt_services'] = $tax; }
    elseif (in_array($taxType, ['ewt_goods_1', 'ewt_both_1'])) { $taxRows['ewt_goods'] = $tax; }
    elseif ($taxType === 'vat_0')      { $taxRows['sales_vat']    = 0.00; }
    else                               { $taxRows['other']         = $tax; }
@endphp

<div class="page">

    {{-- ===== HEADER ===== --}}
    <div class="header">
        @if($brgyLogoB64)
            <img src="{{ $brgyLogoB64 }}" class="logo" alt="Barangay Seal">
        @else
            <div class="logo"></div>
        @endif
        <div class="center">
            <p>Republic of the Philippines</p>
            <p>Province of {{ $siteSettings->province ?: ($s->province ?: '') }}</p>
            <p>{{ strtoupper($siteSettings->municipality ?: ($s->municipality ?: '')) }}</p>
            <p class="title">{{ strtoupper($siteSettings->barangay_name ?: ($s->barangay_name ?: '')) }}</p>
        </div>
        @if($muniLogoB64)
            <img src="{{ $muniLogoB64 }}" class="logo" alt="Municipal Seal">
        @else
            <div class="logo"></div>
        @endif
    </div>

    {{-- ===== MAIN FORM ===== --}}
    <table class="main">

        {{-- Row 1: DV title + DV No + Date --}}
        <tr>
            <td rowspan="2" style="width:60%; text-align:center; font-size:14pt; font-weight:bold; vertical-align:middle; border-right:1px solid #000;">
                DISBURSEMENT VOUCHER
            </td>
            <td style="width:30%;">
                <span class="label">DV No :</span>
                <span style="float:right; font-weight:bold;">{{ $transaction->voucher_no }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Date :</span>
                <span style="float:right;">{{ $transaction->transaction_date?->format('F d, Y') }}</span>
            </td>
        </tr>

        {{-- Row 2: Payee + TIN | Fund --}}
        <tr>
            <td style="padding: 2mm;">
                <div class="label">Payee/Office:</div>
                <div>
                    {{ strtoupper($transaction->payee) }}
                </div>
                <div style="margin-top:2mm;" class="label">TIN :</div>
                <div style="border-bottom:1px solid #000; min-height:4mm; font-weight:bold;">{{ $tin }}</div>
                <div style="margin-top:2mm;" class="label">Address:</div>
                <div style="border-bottom:1px solid #000; min-height:4mm;">{{ strtoupper($address) }}</div>
            </td>
            <td style="padding: 2mm;">
                <div class="label">Fund :</div>
                <div style="border-bottom:1px solid #000; min-height:4mm; font-size:8.5pt;">
                    {{ strtoupper($fund) }}
                </div>
                <div style="margin-top:2mm;" class="label">OBR No. :</div>
                <div style="border-bottom:1px solid #000; min-height:4mm;"></div>
            </td>
        </tr>

        {{-- Row 4: Particulars header --}}
        <tr>
            <td class="section-title">Particulars</td>
            <td class="section-title">Amount</td>
        </tr>

        {{-- Row 5: Particulars + Amount --}}
        <tr>
            {{-- PARTICULARS column --}}
            <td style="padding:3mm 3mm; vertical-align:top;">
                {{-- Main description --}}
                <div style="font-weight:bold; font-size:9.5pt; margin-bottom:3mm; line-height:1.5;">
                    {{ strtoupper($transaction->description ?? '') }}
                </div>
                @foreach($lines as $line)
                    @if($line->description)
                    <div style="font-size:8.5pt; margin-bottom:1mm;">{{ $line->description }}</div>
                    @endif
                @endforeach

                {{-- Spacer --}}
                <div style="min-height:2mm;"></div>

                {{-- Tax section --}}
                <div style="font-size:8.5pt;">
                    <div style="margin-bottom:1mm;"><strong>W/O</strong></div>
                    <div style="margin-bottom:1mm;">TAXES: &nbsp; SALES TO GOVT - VAT 0% &nbsp;&nbsp; F</div>
                    <table style="width:90%; border-collapse:collapse; margin-left:4mm;">
                        <tr>
                            <td style="padding:0.5mm 1mm; font-size:8pt;">SALES TO GOVT - VAT 0%</td>
                            <td style="padding:0.5mm 1mm; font-size:8pt; text-align:right; width:20mm;">{{ number_format($taxRows['sales_vat'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0.5mm 1mm; font-size:8pt;">SALES TO GOVT - NV 3%</td>
                            <td style="padding:0.5mm 1mm; font-size:8pt; text-align:right;">{{ number_format($taxRows['sales_nv'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0.5mm 1mm; font-size:8pt;">EXPANDED WT-SERVICES 2%</td>
                            <td style="padding:0.5mm 1mm; font-size:8pt; text-align:right;">{{ number_format($taxRows['ewt_services'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0.5mm 1mm; font-size:8pt;">EXPANDED WT-GOODS 1%</td>
                            <td style="padding:0.5mm 1mm; font-size:8pt; text-align:right;">{{ number_format($taxRows['ewt_goods'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0.5mm 1mm; font-size:8pt;">OTHER TAXES</td>
                            <td style="padding:0.5mm 1mm; font-size:8pt; text-align:right;">{{ number_format($taxRows['other'], 2) }}</td>
                        </tr>
                    </table>
                    <div style="border-top:1px solid #000; margin-top:1.5mm; padding-top:1mm; display:flex; justify-content:space-between; padding-right:2mm;">
                        <span>TOTAL TAX DEDUCTION</span>
                        <span>{{ number_format($tax, 2) }}</span>
                    </div>
                </div>
            </td>

            {{-- AMOUNT column --}}
            <td style="padding:3mm 3mm; vertical-align:top;">
                {{-- Gross at top --}}
                <div style="display:flex; justify-content:space-between; align-items:baseline;">
                    <span style="font-size:9pt;">P</span>
                    <span style="font-weight:bold; font-size:9.5pt;">{{ number_format($gross, 2) }}</span>
                </div>

                {{-- Spacer to push tax/net to bottom --}}
                <div style="min-height:32mm;"></div>

                {{-- Tax deduction --}}
                <div style="border-top:1px solid #000; padding-top:1mm; margin-bottom:1.5mm;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-size:8.5pt;"></span>
                        <span style="font-size:8.5pt;">{{ number_format($tax, 2) }}</span>
                    </div>
                </div>

                {{-- Net amount --}}
                <div style="display:flex; justify-content:space-between; align-items:baseline; border-top:1px solid #000; padding-top:1mm;">
                    <span style="font-size:9pt;">P</span>
                    <span style="font-weight:bold; font-size:9.5pt;">{{ number_format($net, 2) }}</span>
                </div>
            </td>
        </tr>

        {{-- Row: Check amount --}}
        <tr>
            <td style="text-align:right; font-weight:bold; font-size:9pt;">CHECK AMOUNT</td>
            <td style="text-align:right; font-weight:bold; padding:1.5mm 2mm;">
                P &nbsp;&nbsp; {{ number_format($net, 2) }}
            </td>
        </tr>
    </table>

    {{-- ===== SIGNATURE SECTION ===== --}}
    <table class="sigs" style="margin-top:-1px;">
        <tr>
            <td>
                <div class="sig-label">A. Certified:</div>
                <div style="font-size:7.5pt; margin-top:1mm; line-height:1.4;">
                    As to availability of appropriation. As to obligation of appropriation.
                </div>
                <div style="margin-top:6mm;">
                    <div class="sig-line">Signature:</div>
                    <div class="sig-line" style="margin-top:3mm;">Printed Name: <strong>{{ strtoupper($s->appropriation_chairman ?: '') }}</strong></div>
                    <div class="sig-line" style="margin-top:2mm;">Position: {{ $s->appropriation_chairman_position ?: 'Chairman, Committee on Appropriations' }}</div>
                    <div class="sig-line" style="margin-top:2mm;">Date:</div>
                </div>
            </td>
            <td>
                <div class="sig-label">B. Certified:</div>
                <div style="font-size:7.5pt; margin-top:1mm; line-height:1.4;">
                    As to availability of funds. As to completeness and propriety of supporting documents.
                </div>
                <div style="margin-top:6mm;">
                    <div class="sig-line">Signature:</div>
                    <div class="sig-line" style="margin-top:3mm;">Printed Name: <strong>{{ strtoupper($s->treasurer_name ?: '') }}</strong></div>
                    <div class="sig-line" style="margin-top:2mm;">Position: {{ $s->treasurer_position ?: 'Barangay Treasurer' }}</div>
                    <div class="sig-line" style="margin-top:2mm;">Date:</div>
                </div>
            </td>
            <td>
                <div class="sig-label">C. Certified:</div>
                <div style="font-size:7.5pt; margin-top:1mm; line-height:1.4;">
                    As to validity, propriety and legality of claim.
                </div>
                <div style="margin-top:2mm; font-weight:bold;">Approved for Payment:</div>
                <div style="margin-top:3mm;">
                    <div class="sig-line">Signature:</div>
                    <div class="sig-line" style="margin-top:3mm;">Printed Name: <strong>{{ strtoupper($punongBrgy) }}</strong></div>
                    <div class="sig-line" style="margin-top:2mm;">Position: Punong Barangay</div>
                    <div class="sig-line" style="margin-top:2mm;">Date:</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== ACCOUNTING ENTRIES ===== --}}
    <table class="entries" style="margin-top:-2px;">
        <tr>
            <th colspan="5" style="text-align:left; padding:1.5mm 2mm;">D. Accounting Entries:</th>
        </tr>
        <tr>
            <th style="width:30%;">Account Title</th>
            <th style="width:10%;">Account Code</th>
            <th style="width:10%;">Debit</th>
            <th style="width:10%;">Credit</th>
            <th rowspan='4' style="width:30%; border-left:1px solid #000; font-size:8pt; font-weight:normal; text-align:left; padding:1mm;">
                Entries Checked by:</br>
                Assigned Bookkeeper per<br>
                Date:
                <div style="margin-top:3mm;">Approved by:</div>
                <div style="margin-top:5mm; font-weight:bold;">{{ strtoupper($punongBrgy) }}</div>
                <div>City Accountant</div>
                <div style="margin-top:2mm;">Date:</div>
            </th>
        </tr>
        @foreach($lines as $line)
        <tr>
            <td>{{ $line->lineItem?->name }}</td>
            <td style="text-align:center;">{{ $line->lineItem?->object_code }}</td>
            <td style="text-align:right;">{{ number_format($line->amount, 2) }}</td>
        </tr>
        @endforeach
        <tr>
            <td>Cash in Bank</td>
            <td style="text-align:center;">1-01-02-010</td>
            <td></td>
            <td style="text-align:right;">{{ number_format($net, 2) }}</td>
        </tr>
        <tr>
            <td colspan="5" style="padding:3mm;"></td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Total</td>
            <td></td>
            <td style="text-align:right; font-weight:bold;">{{ number_format($gross, 2) }}</td>
            <td style="text-align:right; font-weight:bold;">{{ number_format($net, 2) }}</td>
            <td></td>
        </tr>
    </table>

    {{-- ===== RECEIVED PAYMENT ===== --}}
    <table class="payment" style="margin-top:-1px;">
        <tr>
            <td rowspan="5" style="width:40%; text-align:center;">
                <div style="font-weight:bold; margin-bottom:2mm;">E. Received Payment :</div>
                <div style="margin-top:2mm; font-size:8.5pt; border-bottom:1px solid #000; min-height:4mm;">
                    {{ strtoupper($transaction->payee) }}
                </div>
                <div style="font-size:7.5pt; margin-top:1mm;">Signature over Printed Name / Authorised Rep.</div>
                <div style="margin-top:5mm; font-size:8.5pt; border-bottom:1px solid #000; min-height:4mm;"></div>
                <div style="font-size:7.5pt; margin-top:1mm;">Date</div>
            </td>
            <td style="width:30%;">
                <span class="label">Check No. :</span>
                <div style="font-weight:bold; min-height:4mm;">{{ $transaction->check_no ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Check Date :</span>
                <div style="min-height:4mm;">{{ $transaction->check_date?->format('F d, Y') ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Bank Name :</span>
                <div style="min-height:4mm;">{{ $transaction->bank_name ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">OR Number :</span>
                <div style="font-weight:bold; min-height:4mm;">{{ $transaction->or_number ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Date :</span>
                <div style="min-height:4mm;">{{ $transaction->or_date?->format('F d, Y') ?? '' }}</div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
