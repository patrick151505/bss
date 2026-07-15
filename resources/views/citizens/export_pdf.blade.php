<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #1f2937; padding: 20px; }
    .report-header { margin-bottom: 14px; border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; }
    .report-header h1 { font-size: 14px; font-weight: bold; color: #1e3a8a; }
    .report-header p { font-size: 8px; color: #6b7280; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 0; }
    thead tr th {
        background-color: #1d4ed8;
        color: #ffffff;
        padding: 5px 6px;
        text-align: left;
        font-size: 7.5px;
        font-weight: bold;
        white-space: nowrap;
    }
    tbody tr td {
        padding: 4px 6px;
        font-size: 7.5px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
    }
    tbody tr:nth-child(even) td { background-color: #f9fafb; }
    tbody tr:nth-child(odd)  td { background-color: #ffffff; }
    .page-footer {
        position: fixed;
        bottom: 10px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 7px;
        color: #9ca3af;
        border-top: 1px solid #e5e7eb;
        padding-top: 4px;
    }
</style>
</head>
<body>

<div class="report-header">
    <h1>{{ $title ?? 'Citizen Records Export' }}</h1>
    <p>Generated: {{ now()->format('F d, Y g:i A') }}&nbsp;&nbsp;|&nbsp;&nbsp;Total Records: {{ count($citizens) }}</p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            @foreach($fields as $field)
                <th>{{ $field['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($citizens as $i => $citizen)
        <tr>
            <td>{{ $i + 1 }}</td>
            @foreach($fields as $fieldDef)
                <td>{{ ($fieldDef['value'])($citizen) }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

<div class="page-footer">
    Barangay Management System &mdash; Confidential
</div>

</body>
</html>
