@php
    $brgy      = $settings->barangay_name ?: 'Barangay';
    $location  = collect([$settings->municipality, $settings->province])->filter()->implode(', ');
    $logo      = $settings->logo ? asset('storage/' . $settings->logo) : null;
    $munLogo   = $settings->municipal_logo ? asset('storage/' . $settings->municipal_logo) : null;
    $time      = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : null;
    $stamp     = [
        'filed'     => '#1e3a8a', 'ongoing'  => '#b45309', 'settled' => '#15803d',
        'referred'  => '#6d28d9', 'dismissed' => '#b91c1c', 'closed' => '#374151',
    ][$blotter->status] ?? '#374151';
    $hasResolution = $blotter->action_taken || $blotter->settled_date || $blotter->referred_to || $blotter->remarks;
    // For CSS content strings (page footer): raw output, minus anything that could end the string.
    $cssText   = fn($s) => str_replace(['"', '\\', "\n", "\r", '<', '>'], '', (string) $s);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Blotter {{ $blotter->blotter_no }}</title>
<style>
    /* Plain paper: page margins + running footer (entry no. / page x of y). */
    @page {
        size: auto;
        margin: 14mm 15mm 16mm;
        @bottom-left  { content: "{!! $cssText($blotter->blotter_no . ' · ' . $brgy) !!}"; font: 8pt Arial, sans-serif; color: #6b7280; }
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
        display: flex; justify-content: center; align-items: center; gap: 10px;
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
    .head-rule { height: 0; border-top: 2.5px solid #1e3a5f; border-bottom: 1px solid #1e3a5f; padding-top: 2px; margin: 8px 0 0; }

    /* ── Title row: title on the left, case box on the right ── */
    .title-row { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin: 18px 0 6px; }
    .title-row h1 { margin: 0; font: 700 19pt Georgia, serif; letter-spacing: 3px; color: #1e3a5f; }
    .title-row .sub { font: 9pt Arial, sans-serif; color: #555; letter-spacing: .4px; margin-top: 2px; }
    .casebox { border: 1.5px solid #1e3a5f; border-collapse: collapse; font-family: Arial, sans-serif; min-width: 2.7in; }
    .casebox td { padding: 3px 8px; font-size: 9pt; border-bottom: 1px solid #c7ced6; }
    .casebox tr:last-child td { border-bottom: 0; }
    .casebox td.k { color: #555; text-transform: uppercase; font-size: 7.5pt; letter-spacing: .6px; width: 1%; white-space: nowrap; }
    .casebox td.v { font-weight: 700; }
    .casebox .no { font-family: 'Courier New', monospace; font-size: 11pt; letter-spacing: .5px; }
    .pill { display: inline-block; padding: 0 8px; border-radius: 99px; color: #fff; font-size: 8pt; letter-spacing: .6px; text-transform: uppercase; }

    /* ── Numbered sections (I, II, III … via CSS counters) ── */
    .sec { margin-top: 16px; }
    .sec > h2 {
        counter-increment: sec;
        display: flex; align-items: center; gap: 8px;
        margin: 0 0 8px; padding-bottom: 3px;
        font: 700 9.5pt Arial, sans-serif; letter-spacing: 1.5px; text-transform: uppercase; color: #1e3a5f;
        border-bottom: 1.5px solid #1e3a5f;
        break-after: avoid; page-break-after: avoid;
    }
    .sec > h2::before { content: counter(sec, upper-roman) "."; min-width: 22px; }

    /* ── Form-style fields: label + underlined value ── */
    .fields { display: grid; grid-template-columns: 1fr 1fr; column-gap: 26px; row-gap: 7px; }
    .f { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
    .f.full { grid-column: 1 / -1; }
    .f .l { font: 700 7.5pt Arial, sans-serif; letter-spacing: .6px; text-transform: uppercase; color: #444; white-space: nowrap; }
    .f .v { flex: 1; min-width: 0; border-bottom: 1px solid #555; padding: 0 3px; min-height: 1.35em; }

    /* ── Parties: complainants | respondents ── */
    .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .side h3 {
        margin: 0 0 6px; padding: 4px 8px; font: 700 8.5pt Arial, sans-serif; letter-spacing: 1px; text-transform: uppercase;
        color: #fff; background: #1e3a5f !important; display: flex; justify-content: space-between;
    }
    .side h3 small { font-weight: 400; opacity: .85; letter-spacing: 0; text-transform: none; }
    .party { display: grid; grid-template-columns: 22px 1fr; gap: 6px; padding: 5px 0; border-bottom: 1px dotted #9aa5b1; }
    .party:last-child { border-bottom: 0; }
    .party .n { width: 20px; height: 20px; border-radius: 50%; border: 1px solid #1e3a5f; color: #1e3a5f;
                font: 700 8.5pt Arial, sans-serif; display: flex; align-items: center; justify-content: center; margin-top: 1px; }
    .party .name { font-weight: 700; text-transform: uppercase; letter-spacing: .2px; }
    .party .meta { font: 8.5pt Arial, sans-serif; color: #444; }
    .party .blank { display: inline-block; min-width: 1.6in; border-bottom: 1px solid #999; }

    /* ── Narrative on ruled lines ── */
    .ruled {
        --lh: 1.65em;
        line-height: var(--lh);
        min-height: calc(var(--lh) * 7);
        padding: 0 4px;
        background-image: repeating-linear-gradient(to bottom, transparent 0, transparent calc(var(--lh) - 1px), #c3cbd4 calc(var(--lh) - 1px), #c3cbd4 var(--lh));
        text-align: justify;
    }
    .ruled p { margin: 0; }
    .ruled ul, .ruled ol { margin: 0 0 0 20px; padding: 0; }

    /* ── Hearings ── */
    table.log { width: 100%; border-collapse: collapse; font-size: 10pt; }
    table.log th {
        text-align: left; font: 700 7.5pt Arial, sans-serif; letter-spacing: .8px; text-transform: uppercase; color: #444;
        padding: 4px 6px; border-bottom: 1.5px solid #1e3a5f;
    }
    table.log td { padding: 5px 6px; border-bottom: 1px solid #d5dbe1; vertical-align: top; }
    table.log tr { break-inside: avoid; page-break-inside: avoid; }
    table.log .muted, .muted { color: #555; font: 8.5pt Arial, sans-serif; }
    table.log .absent { color: #b91c1c; font: italic 9pt Arial, sans-serif; }
    table.log .outcome { font: 700 8.5pt Arial, sans-serif; text-transform: uppercase; letter-spacing: .4px; }

    /* ── Resolution with status stamp ── */
    .resolution { position: relative; }
    .stamp {
        position: absolute; right: 4px; top: -6px; transform: rotate(-8deg);
        border: 2.5px solid currentColor; border-radius: 6px; padding: 2px 12px;
        font: 800 13pt Arial, sans-serif; letter-spacing: 3px; text-transform: uppercase; opacity: .85;
    }
    .resolution .fields { padding-right: 1.9in; }

    /* ── Photos ── */
    .photos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .photos figure { margin: 0; text-align: center; font: 8pt Arial, sans-serif; color: #555; }
    .photos img { width: 100%; height: 1.15in; object-fit: cover; border: 1px solid #9aa5b1; display: block; margin-bottom: 2px; }

    /* ── Certification + signatures ── */
    .certify { margin-top: 20px; font-style: italic; font-size: 10pt; text-align: justify; }
    .sigs { display: grid; grid-template-columns: 1fr 1fr; column-gap: 44px; row-gap: 26px; margin-top: 30px; }
    .sig .cap { font: 700 7.5pt Arial, sans-serif; letter-spacing: .8px; text-transform: uppercase; color: #444; margin-bottom: 26px; }
    .sig .line { border-top: 1px solid #111; padding-top: 3px; text-align: center; font-weight: 700; text-transform: uppercase; min-height: 1.4em; }
    .sig .role { text-align: center; font: 8.5pt Arial, sans-serif; color: #444; }
    .sig .date { margin-top: 8px; font: 8.5pt Arial, sans-serif; color: #444; }
    .sig .date span { display: inline-block; min-width: 1.3in; border-bottom: 1px solid #999; }

    .doc-foot { margin-top: 22px; padding-top: 5px; border-top: 1px solid #c7ced6; display: flex; justify-content: space-between;
                font: 8pt Arial, sans-serif; color: #6b7280; }

    /* Keep blocks from splitting awkwardly across pages */
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
    <a class="btn ghost" href="{{ route('blotters.show', $blotter) }}">← Back to blotter</a>
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
                <div class="office">Office of the Lupong Tagapamayapa</div>
            </div>
            <div style="text-align:right">@if($munLogo)<img src="{{ $munLogo }}" alt="">@endif</div>
        </div>
        <div class="head-rule"></div>
    </div>

    {{-- ── Title + case box ── --}}
    <div class="title-row keep">
        <div>
            <h1>BARANGAY BLOTTER</h1>
            <div class="sub">Incident Record · Office of the Lupong Tagapamayapa</div>
        </div>
        <table class="casebox">
            <tr><td class="k">Entry No.</td><td class="v no">{{ $blotter->blotter_no }}</td></tr>
            <tr><td class="k">Date Filed</td><td class="v">{{ $blotter->filed_date?->format('F d, Y') }}</td></tr>
            <tr><td class="k">Status</td><td class="v"><span class="pill" style="background: {{ $stamp }} !important;">{{ $blotter->statusLabel() }}</span></td></tr>
        </table>
    </div>

    {{-- ── I. Incident details ── --}}
    <div class="sec keep">
        <h2>Incident Details</h2>
        <div class="fields">
            <div class="f"><span class="l">Nature / Type</span><span class="v">{{ $blotter->typeLabel() }}</span></div>
            <div class="f"><span class="l">Date of Incident</span><span class="v">{{ $blotter->incident_date?->format('F d, Y') }}</span></div>
            <div class="f"><span class="l">Time of Incident</span><span class="v">{{ $time($blotter->incident_time) ?? '' }}</span></div>
            <div class="f"><span class="l">Attending Officer</span><span class="v">{{ $blotter->attending_officer }}</span></div>
            <div class="f full"><span class="l">Place of Incident</span><span class="v">{{ $blotter->incident_location }}</span></div>
        </div>
    </div>

    {{-- ── II. Parties ── --}}
    <div class="sec">
        <h2>Parties Involved</h2>
        <div class="parties">
            @foreach([['Complainant', $complainants], ['Respondent', $respondents]] as [$label, $list])
            <div class="side keep">
                <h3>{{ \Illuminate\Support\Str::plural($label, max(1, $list->count())) }} <small>{{ $list->count() }} {{ \Illuminate\Support\Str::plural('person', $list->count()) }}</small></h3>
                @forelse($list as $i => $p)
                <div class="party">
                    <span class="n">{{ $i + 1 }}</span>
                    <div>
                        <div class="name">{{ $p->name }}</div>
                        <div class="meta">Address: @if($p->address){{ $p->address }}@else<span class="blank"></span>@endif</div>
                        <div class="meta">Contact No.: @if($p->contact){{ $p->contact }}@else<span class="blank"></span>@endif</div>
                    </div>
                </div>
                @empty
                <div class="party"><span class="n">1</span><div><div class="name"><span class="blank"></span></div></div></div>
                @endforelse
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── III. Narrative ── --}}
    <div class="sec">
        <h2>Narrative of the Incident</h2>
        <div class="ruled">{!! $blotter->narrative ?: '&nbsp;' !!}</div>
    </div>

    {{-- ── IV. Witnesses ── --}}
    @if($blotter->witnesses)
    <div class="sec keep">
        <h2>Witnesses</h2>
        <div style="white-space: pre-line;">{{ $blotter->witnesses }}</div>
    </div>
    @endif

    {{-- ── V. Actions & hearings ── --}}
    <div class="sec">
        <h2>Actions &amp; Hearings</h2>
        @if($actions->isEmpty())
            <p class="muted" style="margin:2px 0">No actions or hearings recorded.</p>
        @else
        <table class="log">
            <thead>
                <tr>
                    <th style="width:16%">Date / Time</th>
                    <th style="width:14%">Action</th>
                    <th style="width:20%">Venue / Conducted by</th>
                    <th>Attendance</th>
                    <th style="width:14%">Outcome</th>
                </tr>
            </thead>
            <tbody>
            @foreach($actions as $act)
                @php
                    $cAtt = is_array($act->complainant_attendance) ? $act->complainant_attendance : [];
                    $rAtt = is_array($act->respondent_attendance)  ? $act->respondent_attendance  : [];
                    $absent = collect()
                        ->merge($complainants->filter(fn($p, $i) => ($cAtt[$i] ?? 'present') !== 'present')->pluck('name'))
                        ->merge($respondents->filter(fn($p, $i) => ($rAtt[$i] ?? 'present') !== 'present')->pluck('name'));
                @endphp
                <tr>
                    <td>{{ $act->scheduled_date?->format('M d, Y') }}@if($time($act->scheduled_time))<br><span class="muted">{{ $time($act->scheduled_time) }}</span>@endif</td>
                    <td>{{ $act->actionLabel() }}</td>
                    <td>{{ $act->venue ?: '—' }}@if($act->conducted_by)<br><span class="muted">{{ $act->conducted_by }}</span>@endif</td>
                    <td>
                        @if($absent->isEmpty())
                            All parties present
                        @else
                            <span class="absent">Absent: {{ $absent->implode(', ') }}</span>
                        @endif
                    </td>
                    <td class="outcome">{{ $act->outcomeLabel() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- ── VI. Resolution ── --}}
    @if($hasResolution)
    <div class="sec keep resolution">
        <h2>Resolution</h2>
        <span class="stamp" style="color: {{ $stamp }};">{{ $blotter->statusLabel() }}</span>
        <div class="fields">
            @if($blotter->action_taken)<div class="f full"><span class="l">Action Taken</span><span class="v">{{ strip_tags($blotter->action_taken) }}</span></div>@endif
            @if($blotter->settled_date)<div class="f full"><span class="l">Date Settled</span><span class="v">{{ $blotter->settled_date->format('F d, Y') }}</span></div>@endif
            @if($blotter->referred_to)<div class="f full"><span class="l">Referred To</span><span class="v">{{ $blotter->referred_to }}</span></div>@endif
            @if($blotter->remarks)<div class="f full"><span class="l">Remarks</span><span class="v">{{ strip_tags($blotter->remarks) }}</span></div>@endif
        </div>
    </div>
    @endif

    {{-- ── VII. Photos ── --}}
    @if(!empty($blotter->photos))
    <div class="sec keep">
        <h2>Attached Photos</h2>
        <div class="photos">
            @foreach($blotter->photos as $i => $photo)
            <figure><img src="{{ asset('storage/' . $photo) }}" alt="">Photo {{ $i + 1 }}</figure>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Certification + signatures ── --}}
    <div class="keep">
        <p class="certify">
            I hereby certify that the foregoing is a true and correct record of the blotter entry
            {{ $blotter->blotter_no }} as recorded in the logbook of this office.
        </p>
        <div class="sigs">
            <div class="sig">
                <div class="cap">Complainant</div>
                <div class="line">{{ $complainants->first()->name ?? '' }}</div>
                <div class="role">Signature over printed name</div>
                <div class="date">Date: <span></span></div>
            </div>
            <div class="sig">
                <div class="cap">Respondent</div>
                <div class="line">{{ $respondents->first()->name ?? '' }}</div>
                <div class="role">Signature over printed name</div>
                <div class="date">Date: <span></span></div>
            </div>
            <div class="sig">
                <div class="cap">Recorded by</div>
                <div class="line">{{ $blotter->attending_officer ?? '' }}</div>
                <div class="role">Attending Officer</div>
            </div>
            <div class="sig">
                <div class="cap">Attested by</div>
                <div class="line">{{ $settings->captain_name ?? '' }}</div>
                <div class="role">{{ $settings->captain_position ?: 'Punong Barangay' }} / Lupon Chairman</div>
            </div>
        </div>
    </div>

    <div class="doc-foot">
        <span>{{ $brgy }}{{ $location ? ', ' . $location : '' }}</span>
        <span>Printed {{ now()->format('M d, Y g:i A') }} by {{ auth()->user()->name ?? '—' }}</span>
    </div>

@include('partials.paper.close')
</div>

<script>
    // Open the print dialog once the page (incl. logos/photos) has loaded.
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
