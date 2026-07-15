@extends('layouts.vertical', ['title' => 'Blotter ' . $blotter->blotter_no, 'sub_title' => 'Barangay', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 text-sm text-success font-medium flex gap-2">
    <i class="mgc_check_circle_line shrink-0 mt-0.5"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger font-medium flex gap-2">
    <i class="mgc_alert_line shrink-0 mt-0.5"></i> {{ session('error') }}
</div>
@endif

{{-- ── Hero Header ── --}}
<div class="card mb-5 overflow-hidden">
    <div class="h-1.5 w-full {{ match($blotter->status) {
        'filed'     => 'bg-primary',
        'ongoing'   => 'bg-warning',
        'settled'   => 'bg-success',
        'referred'  => 'bg-gray-400',
        'dismissed' => 'bg-danger',
        'closed'    => 'bg-gray-500',
        default     => 'bg-gray-300',
    } }}"></div>
    <div class="p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('blotters.index') }}" class="text-gray-400 hover:text-primary shrink-0">
                <i class="mgc_arrow_left_line text-xl"></i>
            </a>
            <div class="w-12 h-12 rounded-xl {{ match($blotter->status) {
                'filed'     => 'bg-primary/10',
                'ongoing'   => 'bg-warning/10',
                'settled'   => 'bg-success/10',
                'dismissed' => 'bg-danger/10',
                default     => 'bg-gray-100 dark:bg-gray-800',
            } }} flex items-center justify-center shrink-0">
                <i class="mgc_gavel_line text-xl text-gray {{ match($blotter->status) {
                    'filed'     => 'text-primary',
                    'ongoing'   => 'text-warning',
                    'settled'   => 'text-success',
                    'dismissed' => 'text-danger',
                    default     => 'text-gray-400',
                } }}"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-100 font-mono tracking-tight">{{ $blotter->blotter_no }}</h4>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $blotter->statusColor() }}">
                        {{ $blotter->statusLabel() }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                        {{ $blotter->typeLabel() }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">
                    Filed {{ $blotter->filed_date->format('F d, Y') }}
                    @if($blotter->incident_location)
                    &nbsp;&middot;&nbsp;<i class="mgc_location_line"></i> {{ $blotter->incident_location }}
                    @endif
                </p>
            </div>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('blotters.edit', $blotter) }}" class="btn border-gray-300 dark:border-gray-600 text-sm">
                <i class="mgc_edit_2_line me-1"></i> Edit
            </a>
            <button type="button" onclick="window.print()" class="btn border-gray-300 dark:border-gray-600 text-sm">
                <i class="mgc_print_line me-1"></i> Print
            </button>
            <form action="{{ route('blotters.destroy', $blotter) }}" method="POST" id="delete-blotter-form">
                @csrf @method('DELETE')
                <button type="button" onclick="swalDeleteBlotter()" class="btn border-danger/40 text-danger text-sm hover:bg-danger/5">
                    <i class="mgc_delete_2_line me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-5">

{{-- ══ LEFT COLUMN ══ --}}
<div class="col-span-12 lg:col-span-8 flex flex-col gap-5">

    {{-- Incident Details strip --}}
    <div class="card p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 text-sm">
            <div>
                <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-0.5">Incident Date</p>
                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $blotter->incident_date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-0.5">Incident Time</p>
                <p class="font-medium text-gray-800 dark:text-gray-100">
                    {{ $blotter->incident_time ? \Carbon\Carbon::parse($blotter->incident_time)->format('g:i A') : '—' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-0.5">Date Filed</p>
                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $blotter->filed_date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-0.5">Attending Officer</p>
                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $blotter->attending_officer ?: '—' }}</p>
            </div>
            @if($blotter->incident_location)
            <div class="col-span-2 sm:col-span-4">
                <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-0.5">Location</p>
                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $blotter->incident_location }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Parties — VS layout --}}
    <div class="card overflow-hidden">
        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
            @foreach([
                ['success', 'Complainant', $complainants, 'mgc_user_heart_line'],
                ['danger',  'Respondent',  $respondents,  'mgc_user_warning_line'],
            ] as [$color, $label, $partyList, $icon])
            <div>
                <div class="px-5 py-3 bg-{{ $color }}/5 border-b border-{{ $color }}/10 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="{{ $icon }} text-{{ $color }} text-sm"></i>
                        <span class="text-xs font-semibold text-{{ $color }} uppercase tracking-wide">{{ $label }}</span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $partyList->count() }} {{ Str::plural('person', $partyList->count()) }}</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($partyList as $i => $party)
                    <div class="px-5 py-3 flex items-start gap-3">
                        <span class="shrink-0 w-7 h-7 rounded-full bg-{{ $color }}/10 text-{{ $color }} text-xs font-bold flex items-center justify-center mt-0.5">
                            {{ $i + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $party->name }}</p>
                            @if($party->address)
                            <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                <i class="mgc_location_line shrink-0"></i>{{ $party->address }}
                            </p>
                            @endif
                            @if($party->contact)
                            <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                <i class="mgc_phone_line shrink-0"></i>{{ $party->contact }}
                            </p>
                            @endif
                            @if($party->citizen_id)
                            <a href="{{ route('citizens.show', $party->citizen_id) }}"
                               class="inline-flex items-center gap-1 text-xs text-primary hover:underline mt-1">
                                <i class="mgc_external_link_line text-[10px]"></i> Profile
                            </a>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-4 text-xs text-gray-400 italic">No {{ strtolower($label) }} on record.</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Narrative --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title text-sm"><i class="mgc_document_line me-2 text-primary"></i>Narrative</h5>
        </div>
        <div class="p-5 text-sm text-gray-700 dark:text-gray-300 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
            {!! $blotter->narrative !!}
        </div>
    </div>

    {{-- Witnesses --}}
    @if($blotter->witnesses)
    <div class="card p-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-1.5">
            <i class="mgc_group_line text-primary"></i> Witnesses
        </p>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $blotter->witnesses }}</p>
    </div>
    @endif

    {{-- Incident Photos --}}
    @if(!empty($blotter->photos))
    <div class="card p-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-1.5">
            <i class="mgc_photo_album_line text-primary"></i> Incident Photos
            <span class="text-gray-300 font-normal">({{ count($blotter->photos) }})</span>
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach($blotter->photos as $i => $photo)
            <button type="button" onclick="openLightbox('blotter', {{ $i }})"
                    class="block rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-primary/40 transition aspect-square">
                <img src="{{ asset('storage/' . $photo) }}" alt="Photo {{ $i+1 }}" class="w-full h-full object-cover">
            </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Actions & Hearings — Timeline --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h5 class="card-title text-sm"><i class="mgc_calendar_line me-2 text-primary"></i>Actions & Hearings</h5>
            <button type="button" onclick="openModal('add-action-modal')"
                    class="btn btn-sm bg-primary text-white gap-1">
                <i class="mgc_add_line"></i> Add
            </button>
        </div>

        @if($actions->isEmpty())
        <div class="px-5 py-10 text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                <i class="mgc_calendar_line text-2xl text-gray-300"></i>
            </div>
            <p class="text-sm text-gray-400">No actions scheduled yet.</p>
            <button type="button" onclick="openModal('add-action-modal')"
                    class="btn btn-sm bg-primary/10 text-primary mt-3">
                <i class="mgc_add_line me-1"></i> Schedule first action
            </button>
        </div>
        @else
        <div class="px-5 pb-5 pt-2">
            <ol class="relative border-l-2 border-gray-100 dark:border-gray-700 ms-3 space-y-0">
                @foreach($actions as $act)
                @php
                    [$badgeCls, $dotCls] = $act->outcomeBadge();
                    $cAtt = is_array($act->complainant_attendance) ? $act->complainant_attendance : [];
                    $rAtt = is_array($act->respondent_attendance)  ? $act->respondent_attendance  : [];
                    $typeColors = [
                        'hearing'       => ['bg-primary',   'text-white',   'mgc_mic_line'],
                        'summons'       => ['bg-warning',   'text-warning',   'mgc_mail_send_line text-white'],
                        'mediation'     => ['bg-indigo-100 dark:bg-indigo-900/30', 'text-indigo-600 dark:text-indigo-400', 'mgc_shake_line'],
                        'conciliation'  => ['bg-teal-100 dark:bg-teal-900/30',    'text-teal-600 dark:text-teal-400',     'mgc_hand_line'],
                        'investigation' => ['bg-orange-100 dark:bg-orange-900/30','text-orange-600 dark:text-orange-400', 'mgc_search_line'],
                        'other'         => ['bg-gray-100 dark:bg-gray-800',       'text-gray-500',                        'mgc_more_line'],
                    ];
                    [$typeBg, $typeText, $typeIcon] = $typeColors[$act->action_type] ?? $typeColors['other'];
                @endphp
                <li class="ms-6 pt-5 pb-1">
                    {{-- Timeline dot --}}
                    <span class="absolute -start-[1.1rem] flex items-center justify-center w-8 h-8 rounded-full {{ $typeBg }}  dark:border-gray-900">
                        <i class="{{ $typeIcon }} {{ $typeText }} text-sm"></i>
                    </span>

                    <div class="card border border-gray-100 dark:border-gray-700 shadow-none">
                        {{-- Action header --}}
                        <div class="px-4 py-3 flex items-start justify-between gap-3 border-b border-gray-50 dark:border-gray-800">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $act->actionLabel() }}</span>
                                    @if($act->outcome !== 'pending')
                                    <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[11px] font-medium {{ $badgeCls }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dotCls }}"></span>
                                        {{ $act->outcomeLabel() }}
                                    </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-400 flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <i class="mgc_calendar_line"></i>
                                        {{ $act->scheduled_date->format('M d, Y') }}
                                        @if($act->scheduled_time)
                                        &nbsp;{{ \Carbon\Carbon::parse($act->scheduled_time)->format('g:i A') }}
                                        @endif
                                    </span>
                                    @if($act->venue)
                                    <span class="flex items-center gap-1">
                                        <i class="mgc_location_line"></i>{{ $act->venue }}
                                    </span>
                                    @endif
                                    @if($act->conducted_by)
                                    <span class="flex items-center gap-1">
                                        <i class="mgc_user_line"></i>{{ $act->conducted_by }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-1 shrink-0">
                                <button type="button"
                                        onclick="openOutcomeModal({{ $act->id }})"
                                        class="btn btn-sm border-gray-200 dark:border-gray-700 text-gray-500 hover:text-primary hover:border-primary/40"
                                        title="Edit outcome">
                                    <i class="mgc_edit_line text-xs"></i>
                                </button>
                                <button type="button"
                                        onclick="swalDeleteAction({{ $act->id }}, '{{ $act->actionLabel() }}')"
                                        class="btn btn-sm border-gray-200 dark:border-gray-700 text-gray-500 hover:text-danger hover:border-danger/40"
                                        title="Delete">
                                    <i class="mgc_delete_line text-xs"></i>
                                </button>
                                <form id="delete-action-{{ $act->id }}"
                                      action="{{ route('blotters.actions.destroy', [$blotter, $act]) }}"
                                      method="POST" class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </div>

                        {{-- Attendance per party --}}
                        <div class="px-4 py-3 grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-gray-50 dark:border-gray-800">
                            {{-- Complainants --}}
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-success mb-1.5 flex items-center gap-1">
                                    <i class="mgc_user_heart_line"></i> Complainant(s)
                                </p>
                                <div class="space-y-1">
                                    @foreach($complainants as $ci => $cp)
                                    @php
                                        $cv = $cAtt[$ci] ?? 'present';
                                        $isPresent = $cv === 'present';
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="shrink-0 w-4 h-4 rounded-full flex items-center justify-center {{ $isPresent ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' }}">
                                            <i class="{{ $isPresent ? 'mgc_check_line' : 'mgc_close_line' }} text-[9px]"></i>
                                        </span>
                                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate flex-1" title="{{ $cp->name }}">
                                            {{ Str::limit($cp->name, 22) }}
                                        </span>
                                        <span class="text-[10px] font-semibold shrink-0 {{ $isPresent ? 'text-success' : 'text-danger' }}">
                                            {{ $isPresent ? 'Present' : 'Absent' }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Respondents --}}
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-danger mb-1.5 flex items-center gap-1">
                                    <i class="mgc_user_warning_line"></i> Respondent(s)
                                </p>
                                <div class="space-y-1">
                                    @foreach($respondents as $ri => $rp)
                                    @php
                                        $rv = $rAtt[$ri] ?? 'present';
                                        $isPresent = $rv === 'present';
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="shrink-0 w-4 h-4 rounded-full flex items-center justify-center {{ $isPresent ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' }}">
                                            <i class="{{ $isPresent ? 'mgc_check_line' : 'mgc_close_line' }} text-[9px]"></i>
                                        </span>
                                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate flex-1" title="{{ $rp->name }}">
                                            {{ Str::limit($rp->name, 22) }}
                                        </span>
                                        <span class="text-[10px] font-semibold shrink-0 {{ $isPresent ? 'text-success' : 'text-danger' }}">
                                            {{ $isPresent ? 'Present' : 'Absent' }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        @if($act->notes)
                        <div class="px-4 pb-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-50 dark:border-gray-800 pt-2 prose prose-xs max-w-none">
                            {!! $act->notes !!}
                        </div>
                        @endif

                        {{-- Action photos --}}
                        @if(!empty($act->photos))
                        <div class="px-4 pb-3 border-t border-gray-50 dark:border-gray-800 pt-2">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($act->photos as $pi => $ap)
                                <button type="button" onclick="openLightbox('action_{{ $act->id }}', {{ $pi }})"
                                        class="w-14 h-14 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-primary/40 transition shrink-0">
                                    <img src="{{ asset('storage/' . $ap) }}" alt="Photo" class="w-full h-full object-cover">
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Version history --}}
                        @if($act->versions->isNotEmpty())
                        <div class="border-t border-gray-100 dark:border-gray-800">
                            <button type="button"
                                    onclick="toggleVersions('versions-{{ $act->id }}')"
                                    class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <span class="flex items-center gap-1.5">
                                    <i class="mgc_history_line"></i>
                                    {{ $act->versions->count() }} previous {{ Str::plural('version', $act->versions->count()) }}
                                </span>
                                <i class="mgc_down_line text-[11px] transition-transform" id="versions-chevron-{{ $act->id }}"></i>
                            </button>
                            <div id="versions-{{ $act->id }}" class="hidden">
                                @foreach($act->versions as $vi => $ver)
                                @php
                                    [$vBadgeCls, $vDotCls] = $ver->outcomeBadge();
                                    $vCAtt = is_array($ver->complainant_attendance) ? $ver->complainant_attendance : [];
                                    $vRAtt = is_array($ver->respondent_attendance)  ? $ver->respondent_attendance  : [];
                                    $versionNum = $act->versions->count() - $vi;
                                @endphp
                                <div class="px-4 py-3 border-t border-dashed border-gray-100 dark:border-gray-800 {{ $vi % 2 === 0 ? 'bg-gray-50/50 dark:bg-gray-800/20' : '' }}">
                                    {{-- Version header --}}
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-semibold bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded px-1.5 py-0.5">
                                                v{{ $versionNum }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[11px] font-medium {{ $vBadgeCls }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $vDotCls }}"></span>
                                                {{ $ver->outcomeLabel() }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] text-gray-400">{{ $ver->created_at->format('M d, Y g:i A') }}</p>
                                            @if($ver->savedBy)
                                            <p class="text-[10px] text-gray-400">by {{ $ver->savedBy->name }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Attendance snapshot --}}
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <div>
                                            <p class="text-[9px] font-semibold uppercase tracking-widest text-success mb-1">Complainant(s)</p>
                                            @foreach($complainants as $ci => $cp)
                                            @php $cv = $vCAtt[$ci] ?? 'present'; $isp = $cv === 'present'; @endphp
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center shrink-0 {{ $isp ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' }}">
                                                    <i class="{{ $isp ? 'mgc_check_line' : 'mgc_close_line' }} text-[8px]"></i>
                                                </span>
                                                <span class="text-[11px] text-gray-600 dark:text-gray-300 truncate">{{ Str::limit($cp->name, 18) }}</span>
                                                <span class="text-[10px] font-medium {{ $isp ? 'text-success' : 'text-danger' }} ml-auto shrink-0">{{ $isp ? 'Present' : 'Absent' }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-semibold uppercase tracking-widest text-danger mb-1">Respondent(s)</p>
                                            @foreach($respondents as $ri => $rp)
                                            @php $rv = $vRAtt[$ri] ?? 'present'; $isp = $rv === 'present'; @endphp
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center shrink-0 {{ $isp ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' }}">
                                                    <i class="{{ $isp ? 'mgc_check_line' : 'mgc_close_line' }} text-[8px]"></i>
                                                </span>
                                                <span class="text-[11px] text-gray-600 dark:text-gray-300 truncate">{{ Str::limit($rp->name, 18) }}</span>
                                                <span class="text-[10px] font-medium {{ $isp ? 'text-success' : 'text-danger' }} ml-auto shrink-0">{{ $isp ? 'Present' : 'Absent' }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Notes snapshot --}}
                                    @if($ver->notes)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-700 px-2.5 py-1.5 prose prose-xs max-w-none mb-2">
                                        {!! $ver->notes !!}
                                    </div>
                                    @else
                                    <p class="text-[11px] text-gray-400 italic mb-2">No notes.</p>
                                    @endif

                                    {{-- Photos snapshot --}}
                                    @if(!empty($ver->photos))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($ver->photos as $vp)
                                        <img src="{{ asset('storage/' . $vp) }}"
                                             class="w-10 h-10 rounded object-cover border border-gray-200 dark:border-gray-700"
                                             alt="photo">
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                    </div>
                </li>
                @endforeach
            </ol>
        </div>
        @endif
    </div>

</div>

{{-- ══ RIGHT COLUMN ══ --}}
<div class="col-span-12 lg:col-span-4 flex flex-col gap-5">

    {{-- Quick Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="card p-3 text-center">
            <p class="text-xl font-bold text-primary">{{ $actions->count() }}</p>
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mt-0.5">Actions</p>
        </div>
        <div class="card p-3 text-center">
            <p class="text-xl font-bold text-success">{{ $complainants->count() }}</p>
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mt-0.5">Complainants</p>
        </div>
        <div class="card p-3 text-center">
            <p class="text-xl font-bold text-danger">{{ $respondents->count() }}</p>
            <p class="text-[10px] text-gray-400 uppercase tracking-wide mt-0.5">Respondents</p>
        </div>
    </div>

    {{-- Update Status --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title text-sm"><i class="mgc_transfer_3_line me-2 text-primary"></i>Update Status</h5>
        </div>
        <div class="p-4">
            <form action="{{ route('blotters.status', $blotter) }}" method="POST" class="flex flex-col gap-3">
                @csrf @method('PATCH')
                <div>
                    <label class="form-label text-xs">Status</label>
                    <select name="status" class="form-select text-sm" id="status-select">
                        @foreach(\App\Models\Blotter::STATUSES as $val => $lbl)
                        <option value="{{ $val }}" {{ $blotter->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Action Taken</label>
                    <textarea name="action_taken" rows="2" placeholder="Describe action taken…"
                        class="form-input text-sm">{{ $blotter->action_taken }}</textarea>
                </div>
                <div id="settled-date-wrap" class="{{ $blotter->status === 'settled' ? '' : 'hidden' }}">
                    <label class="form-label text-xs">Date Settled</label>
                    <input type="date" name="settled_date"
                        value="{{ $blotter->settled_date?->format('Y-m-d') ?? date('Y-m-d') }}"
                        class="form-input text-sm">
                </div>
                <div id="referred-to-wrap" class="{{ $blotter->status === 'referred' ? '' : 'hidden' }}">
                    <label class="form-label text-xs">Referred To</label>
                    <input type="text" name="referred_to" value="{{ $blotter->referred_to }}"
                        placeholder="e.g. PNP, DSWD, MTC" class="form-input text-sm">
                </div>
                <div>
                    <label class="form-label text-xs">Remarks</label>
                    <textarea name="remarks" rows="2" placeholder="Additional notes…"
                        class="form-input text-sm">{{ $blotter->remarks }}</textarea>
                </div>
                <button type="submit" class="btn bg-primary text-white text-sm w-full">
                    <i class="mgc_check_line me-1"></i> Update Status
                </button>
            </form>
        </div>
    </div>

    {{-- Resolution (if any) --}}
    @if($blotter->action_taken || $blotter->settled_date || $blotter->referred_to)
    <div class="card p-4 border-l-4 border-success">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-1.5">
            <i class="mgc_check_circle_line text-success"></i> Resolution
        </p>
        <div class="flex flex-col gap-2.5 text-sm">
            @if($blotter->action_taken)
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">Action Taken</p>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line text-xs">{{ $blotter->action_taken }}</p>
            </div>
            @endif
            @if($blotter->settled_date)
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">Date Settled</p>
                <p class="text-gray-700 dark:text-gray-300 text-xs">{{ $blotter->settled_date->format('F d, Y') }}</p>
            </div>
            @endif
            @if($blotter->referred_to)
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">Referred To</p>
                <p class="text-gray-700 dark:text-gray-300 text-xs">{{ $blotter->referred_to }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Record Details --}}
    <div class="card p-4">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">Record Details</p>
        <div class="flex flex-col gap-2 text-xs">
            <div class="flex justify-between">
                <span class="text-gray-400">Recorded by</span>
                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $blotter->creator?->name ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Last updated</span>
                <span class="text-gray-700 dark:text-gray-300">{{ $blotter->updated_at->format('M d, Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Blotter No.</span>
                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $blotter->blotter_no }}</span>
            </div>
        </div>
    </div>

    {{-- History Log --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title text-sm"><i class="mgc_history_line me-2 text-primary"></i>History Log</h5>
        </div>
        <div class="px-4 pb-4 pt-2">
            @if($logs->isEmpty())
            <p class="text-xs text-gray-400 py-4 text-center">No history yet.</p>
            @else
            <ol class="relative border-l border-gray-200 dark:border-gray-700 ms-2 space-y-3">
                @foreach($logs as $log)
                @php
                    $iconMap = [
                        'created'        => ['mgc_add_circle_line',  'text-success',  'bg-success/10'],
                        'updated'        => ['mgc_edit_line',        'text-primary',  'bg-primary/10'],
                        'status_updated' => ['mgc_transfer_3_line',  'text-warning',  'bg-warning/10'],
                        'action_added'   => ['mgc_calendar_line',    'text-indigo-500','bg-indigo-50 dark:bg-indigo-900/30'],
                        'action_updated' => ['mgc_edit_2_line',      'text-primary',  'bg-primary/10'],
                        'action_deleted' => ['mgc_delete_line',      'text-danger',   'bg-danger/10'],
                        'deleted'        => ['mgc_delete_line',      'text-danger',   'bg-danger/10'],
                    ];
                    [$icon, $iconColor, $iconBg] = $iconMap[$log->action] ?? ['mgc_information_line','text-gray-400','bg-gray-100'];
                @endphp
                <li class="ms-5 pb-1">
                    <span class="absolute -start-[0.9rem] flex items-center justify-center w-7 h-7 rounded-full {{ $iconBg }}">
                        <i class="{{ $icon }} {{ $iconColor }} text-sm"></i>
                    </span>
                    <div class="ms-1">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $log->description }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->format('M d, Y g:i A') }}
                        </p>
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
    </div>

</div>
</div>

{{-- ══ LIGHTBOX ══ --}}
<div id="lightbox" class="hidden fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4" onclick="closeLightbox()">
    <button type="button" onclick="lightboxNav(-1); event.stopPropagation()"
            class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center">
        <i class="mgc_arrow_left_line text-lg"></i>
    </button>
    <img id="lightbox-img" src="" alt="" class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain" onclick="event.stopPropagation()">
    <button type="button" onclick="lightboxNav(1); event.stopPropagation()"
            class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center">
        <i class="mgc_arrow_right_line text-lg"></i>
    </button>
    <button type="button" onclick="closeLightbox()"
            class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center">
        <i class="mgc_close_line text-lg"></i>
    </button>
    <p id="lightbox-counter" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-xs text-white/60"></p>
</div>

{{-- ══ ADD ACTION MODAL ══ --}}
<div id="add-action-modal" class="hidden fixed inset-0 z-50 flex flex-col bg-white dark:bg-gray-900 overflow-y-auto">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 sticky top-0 z-10">
        <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i class="mgc_calendar_line text-primary text-base"></i> Schedule Action
            <span class="text-xs font-normal text-gray-400 ms-1">{{ $blotter->blotter_no }}</span>
        </h6>
        <button type="button" onclick="closeModal('add-action-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <i class="mgc_close_line text-xl"></i>
        </button>
    </div>

    <form id="add-action-form" action="{{ route('blotters.actions.store', $blotter) }}" method="POST" enctype="multipart/form-data" class="flex-1 p-6">
        @csrf
        <div class="max-w-3xl mx-auto flex flex-col gap-5">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label text-xs">Action Type <span class="text-danger">*</span></label>
                    <select name="action_type" class="form-select text-sm" required>
                        @foreach(\App\Models\BlotterAction::ACTION_TYPES as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Date <span class="text-danger">*</span></label>
                    <input type="date" name="scheduled_date" class="form-input text-sm" required value="{{ date('Y-m-d') }}">
                </div>
                <div>
                    <label class="form-label text-xs">Time</label>
                    <input type="time" name="scheduled_time" class="form-input text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Venue</label>
                    <input type="text" name="venue" class="form-input text-sm" placeholder="e.g. Barangay Hall, Room 1">
                </div>
                <div>
                    <label class="form-label text-xs">Conducted By</label>
                    <input type="text" name="conducted_by" class="form-input text-sm" placeholder="Officer / Punong Barangay">
                </div>
            </div>

            {{-- Attendance per person --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Attendance</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
                    <div class="p-4 space-y-2">
                        <p class="text-xs font-semibold text-success uppercase tracking-wide mb-2">Complainant(s)</p>
                        @foreach($complainants as $ci => $cp)
                        <div class="flex items-center justify-between bg-success/5 rounded-lg px-3 py-2 gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate flex-1">{{ $cp->name }}</span>
                            <div class="flex gap-3 shrink-0">
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="complainants_attendance[{{ $ci }}]" value="present" checked class="accent-green-500"> Present
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="complainants_attendance[{{ $ci }}]" value="absent" class="accent-red-500"> Absent
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-4 space-y-2">
                        <p class="text-xs font-semibold text-danger uppercase tracking-wide mb-2">Respondent(s)</p>
                        @foreach($respondents as $ri => $rp)
                        <div class="flex items-center justify-between bg-danger/5 rounded-lg px-3 py-2 gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate flex-1">{{ $rp->name }}</span>
                            <div class="flex gap-3 shrink-0">
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="respondents_attendance[{{ $ri }}]" value="present" checked class="accent-green-500"> Present
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="respondents_attendance[{{ $ri }}]" value="absent" class="accent-red-500"> Absent
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label class="form-label text-xs">Notes</label>
                <input type="hidden" name="notes" id="add-notes-input">
                <div id="add-notes-editor" class="quill-editor-wrap"></div>
            </div>

            <div>
                <label class="form-label text-xs">Attach Photos <span class="text-gray-400">(optional, max 4 MB each)</span></label>
                <input type="file" id="add-photos-input" name="photos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                <div id="add-photos-preview" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-3"></div>
            </div>

            <div class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                <button type="button" onclick="submitAddAction()" class="btn bg-primary text-white px-8">
                    <i class="mgc_save_line me-1"></i> Submit Action
                </button>
                <button type="button" onclick="closeModal('add-action-modal')"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </form>
</div>

{{-- ══ UPDATE OUTCOME MODAL ══ --}}
<div id="outcome-modal" class="hidden fixed inset-0 z-50 flex flex-col bg-white dark:bg-gray-900 overflow-y-auto">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 sticky top-0 z-10">
        <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i class="mgc_edit_2_line text-primary text-base"></i> Update Outcome
            <span class="text-xs font-normal text-gray-400 ms-1" id="outcome-modal-subtitle"></span>
        </h6>
        <button type="button" onclick="closeModal('outcome-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <i class="mgc_close_line text-xl"></i>
        </button>
    </div>

    <form id="outcome-form" method="POST" enctype="multipart/form-data" class="flex-1 p-6">
        @csrf @method('PATCH')
        <div class="max-w-3xl mx-auto flex flex-col gap-5">

            <div class="max-w-xs">
                <label class="form-label text-xs">Outcome <span class="text-danger">*</span></label>
                <select name="outcome" id="outcome-select" class="form-select text-sm" required>
                    @foreach(\App\Models\BlotterAction::OUTCOMES as $val => $lbl)
                    <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Attendance per person --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Attendance</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
                    <div class="p-4 space-y-2" id="oc-complainants-wrap">
                        <p class="text-xs font-semibold text-success uppercase tracking-wide mb-2">Complainant(s)</p>
                        @foreach($complainants as $ci => $cp)
                        <div class="flex items-center justify-between bg-success/5 rounded-lg px-3 py-2 gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate flex-1">{{ $cp->name }}</span>
                            <div class="flex gap-3 shrink-0">
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="complainants_attendance[{{ $ci }}]" class="oc-cp-radio" data-idx="{{ $ci }}" value="present" checked class="accent-green-500"> Present
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="complainants_attendance[{{ $ci }}]" class="oc-cp-radio" data-idx="{{ $ci }}" value="absent" class="accent-red-500"> Absent
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-4 space-y-2" id="oc-respondents-wrap">
                        <p class="text-xs font-semibold text-danger uppercase tracking-wide mb-2">Respondent(s)</p>
                        @foreach($respondents as $ri => $rp)
                        <div class="flex items-center justify-between bg-danger/5 rounded-lg px-3 py-2 gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate flex-1">{{ $rp->name }}</span>
                            <div class="flex gap-3 shrink-0">
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="respondents_attendance[{{ $ri }}]" class="oc-rp-radio" data-idx="{{ $ri }}" value="present" checked class="accent-green-500"> Present
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="respondents_attendance[{{ $ri }}]" class="oc-rp-radio" data-idx="{{ $ri }}" value="absent" class="accent-red-500"> Absent
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label class="form-label text-xs">Notes / Result</label>
                <input type="hidden" name="notes" id="outcome-notes-input">
                <div id="outcome-notes-editor" class="quill-editor-wrap"></div>
            </div>

            <div>
                <label class="form-label text-xs">Photos</label>
                <div id="outcome-existing-photos" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-3"></div>
                <label class="block text-xs text-gray-400 mb-1.5">Add more photos</label>
                <input type="file" id="outcome-photos-input" name="photos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                <div id="outcome-photos-preview" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-3"></div>
            </div>

            <div class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                <button type="button" id="outcome-submit-btn" onclick="submitOutcomeForm()" class="btn bg-primary text-white px-8">
                    <i class="mgc_check_line me-1"></i> Save Outcome
                </button>
                <button type="button" onclick="closeModal('outcome-modal')"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@section('script')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
html .quill-editor-wrap .ql-toolbar,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar {
    background:#ffffff!important;border-color:#e2e8f0!important;border-radius:.5rem .5rem 0 0;
}
html .quill-editor-wrap .ql-container,
html[data-mode=dark] .quill-editor-wrap .ql-container {
    background:#ffffff!important;border-color:#e2e8f0!important;border-radius:0 0 .5rem .5rem;
    min-height:140px;font-size:14px;color:#1e293b!important;
}
html .quill-editor-wrap .ql-editor,
html[data-mode=dark] .quill-editor-wrap .ql-editor{min-height:120px;color:#1e293b!important;background:#ffffff!important;}
html .quill-editor-wrap .ql-editor.ql-blank::before,
html[data-mode=dark] .quill-editor-wrap .ql-editor.ql-blank::before{color:#94a3b8!important;font-style:normal;}
html .quill-editor-wrap .ql-toolbar .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-stroke{stroke:#475569!important;}
html .quill-editor-wrap .ql-toolbar .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-fill{fill:#475569!important;}
html .quill-editor-wrap .ql-toolbar button:hover .ql-stroke,
html .quill-editor-wrap .ql-toolbar button.ql-active .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button:hover .ql-stroke,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button.ql-active .ql-stroke{stroke:#4361ee!important;}
html .quill-editor-wrap .ql-toolbar button:hover .ql-fill,
html .quill-editor-wrap .ql-toolbar button.ql-active .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button:hover .ql-fill,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar button.ql-active .ql-fill{fill:#4361ee!important;}
html .quill-editor-wrap .ql-toolbar .ql-picker-label,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-label{color:#475569!important;}
html .quill-editor-wrap .ql-toolbar .ql-picker-options,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-options{background:#ffffff!important;border-color:#e2e8f0!important;color:#1e293b!important;}
html .quill-editor-wrap .ql-toolbar .ql-picker-item,
html[data-mode=dark] .quill-editor-wrap .ql-toolbar .ql-picker-item{color:#1e293b!important;}
</style>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const QUILL_TOOLBAR = [
    [{ 'size': ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline'],
    [{ 'align': [] }],
    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
    ['clean'],
];

let addNotesQuill     = null;
let outcomeNotesQuill = null;

document.addEventListener('DOMContentLoaded', function () {
    addNotesQuill = new Quill('#add-notes-editor', {
        theme: 'snow', placeholder: 'Additional instructions or details…',
        modules: { toolbar: QUILL_TOOLBAR },
    });
    outcomeNotesQuill = new Quill('#outcome-notes-editor', {
        theme: 'snow', placeholder: 'What happened during this session?',
        modules: { toolbar: QUILL_TOOLBAR },
    });

    setupPhotoPreviews('add-photos-input',     'add-photos-preview');
    setupPhotoPreviews('outcome-photos-input', 'outcome-photos-preview');
});

function setupPhotoPreviews(inputId, previewId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function () {
        preview.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'relative group aspect-square';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover rounded-lg border border-gray-200">
                    <span class="absolute bottom-0 inset-x-0 text-[9px] text-white bg-black/50 rounded-b-lg px-1 py-0.5 truncate">${file.name}</span>`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });
}

// ── Modal ─────────────────────────────────────────────────────────
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
function openModal(id)  { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }

function toggleVersions(id) {
    const el       = document.getElementById(id);
    const actionId = id.replace('versions-', '');
    const chevron  = document.getElementById('versions-chevron-' + actionId);
    const hidden   = el.classList.toggle('hidden');
    if (chevron) chevron.style.transform = hidden ? '' : 'rotate(180deg)';
}

// ── Status conditional ────────────────────────────────────────────
document.getElementById('status-select').addEventListener('change', function () {
    document.getElementById('settled-date-wrap').classList.toggle('hidden', this.value !== 'settled');
    document.getElementById('referred-to-wrap').classList.toggle('hidden',  this.value !== 'referred');
});

// ── Add Action submit ─────────────────────────────────────────────
function submitAddAction() {
    document.getElementById('add-notes-input').value = addNotesQuill ? addNotesQuill.root.innerHTML : '';
    document.getElementById('add-action-form').submit();
}

// ── Outcome submit (AJAX) ─────────────────────────────────────────
function submitOutcomeForm() {
    document.getElementById('outcome-notes-input').value = outcomeNotesQuill ? outcomeNotesQuill.root.innerHTML : '';
    const form = document.getElementById('outcome-form');
    const btn  = document.getElementById('outcome-submit-btn');

    if (!form.action || form.action === window.location.href) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Action URL not set. Please reopen the modal.' });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_4_line me-1 animate-spin"></i> Saving…';

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form),
    })
    .then(async res => {
        const text = await res.text();
        let json = {};
        try { json = JSON.parse(text); } catch (e) { /* not JSON */ }
        if (res.ok && json.success) { window.location.reload(); return; }
        const msgs = json.errors
            ? Object.values(json.errors).flat().join('\n')
            : (json.message || `Server error ${res.status}. Check console.`);
        console.error('[outcome]', res.status, text);
        Swal.fire({ icon: 'error', title: 'Error', text: msgs });
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_check_line me-1"></i> Save Outcome';
    })
    .catch(err => {
        console.error('[outcome] network', err);
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_check_line me-1"></i> Save Outcome';
    });
}

// ── Open outcome modal ────────────────────────────────────────────
function openOutcomeModal(actionId) {
    const d              = actionDataMap[actionId] || {};
    const currentOutcome = d.outcome || 'pending';
    const cAtt           = d.cAtt   || {};
    const rAtt           = d.rAtt   || {};
    const currentNotes   = d.notes  || '';
    const existingPhotos = d.photos || [];

    const base = '{{ route('blotters.actions.outcome', [$blotter, '__ID__']) }}';
    const url  = base.replace('__ID__', actionId);
    document.getElementById('outcome-form').action = url;
    document.getElementById('outcome-select').value = currentOutcome;
    if (outcomeNotesQuill) outcomeNotesQuill.root.innerHTML = currentNotes || '';

    document.querySelectorAll('.oc-cp-radio').forEach(r => {
        r.checked = (r.value === ((cAtt && cAtt[r.dataset.idx]) ? cAtt[r.dataset.idx] : 'present'));
    });
    document.querySelectorAll('.oc-rp-radio').forEach(r => {
        r.checked = (r.value === ((rAtt && rAtt[r.dataset.idx]) ? rAtt[r.dataset.idx] : 'present'));
    });

    // Render existing photos
    const wrap = document.getElementById('outcome-existing-photos');
    wrap.innerHTML = '';
    const storageBase = '{{ rtrim(asset('storage'), '/') }}/';
    (existingPhotos || []).forEach(path => {
        const div = document.createElement('div');
        div.className = 'relative group aspect-square';
        div.dataset.path = path;
        div.innerHTML = `
            <img src="${storageBase}${path}" class="w-full h-full object-cover rounded-lg border border-gray-200 dark:border-gray-700">
            <button type="button" onclick="removeExistingPhoto(this)"
                    class="absolute top-1 right-1 w-5 h-5 rounded-full bg-danger text-white text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <i class="mgc_close_line"></i>
            </button>
            <input type="hidden" name="keep_photos[]" value="${path}">`;
        wrap.appendChild(div);
    });

    const inp = document.getElementById('outcome-photos-input');
    if (inp) inp.value = '';
    const prev = document.getElementById('outcome-photos-preview');
    if (prev) prev.innerHTML = '';

    const btn = document.getElementById('outcome-submit-btn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="mgc_check_line me-1"></i> Save Outcome'; }

    openModal('outcome-modal');
}

function removeExistingPhoto(btn) { btn.closest('[data-path]').remove(); }

// ── SweetAlert helpers ────────────────────────────────────────────
function swalDeleteBlotter() {
    Swal.fire({
        title: 'Delete Blotter?',
        text: 'Blotter {{ $blotter->blotter_no }} will be permanently deleted.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Yes, delete it', confirmButtonColor: '#fa5c7c',
    }).then(r => { if (r.isConfirmed) document.getElementById('delete-blotter-form').submit(); });
}

function swalDeleteAction(actionId, label) {
    Swal.fire({
        title: 'Remove Action?', text: `"${label}" will be permanently deleted.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Yes, delete it', confirmButtonColor: '#fa5c7c',
    }).then(r => { if (r.isConfirmed) document.getElementById('delete-action-' + actionId).submit(); });
}

// ── Action data map (keyed by action ID) ─────────────────────────
const actionDataMap = {
    @foreach($actions as $act)
    @php
        $cAttJs = is_array($act->complainant_attendance) ? $act->complainant_attendance : [];
        $rAttJs = is_array($act->respondent_attendance)  ? $act->respondent_attendance  : [];
    @endphp
    {{ $act->id }}: {
        outcome: @json($act->outcome),
        cAtt:    @json($cAttJs),
        rAtt:    @json($rAttJs),
        notes:   @json($act->notes ?? ''),
        photos:  @json($act->photos ?? []),
    },
    @endforeach
};

// ── Lightbox ──────────────────────────────────────────────────────
const lightboxSets = {
    blotter: @json(collect($blotter->photos ?? [])->map(fn($p) => asset('storage/'.$p))->values()),
    @foreach($actions as $act)
    @if(!empty($act->photos))
    action_{{ $act->id }}: @json(collect($act->photos)->map(fn($p) => asset('storage/'.$p))->values()),
    @endif
    @endforeach
};
let lbSet = [], lbIdx = 0;

function openLightbox(set, idx) {
    lbSet = lightboxSets[set] || [];
    lbIdx = idx;
    showLightboxImg();
    document.getElementById('lightbox').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.body.style.overflow = '';
}
function lightboxNav(dir) {
    lbIdx = (lbIdx + dir + lbSet.length) % lbSet.length;
    showLightboxImg();
}
function showLightboxImg() {
    document.getElementById('lightbox-img').src = lbSet[lbIdx];
    document.getElementById('lightbox-counter').textContent = lbSet.length > 1 ? `${lbIdx + 1} / ${lbSet.length}` : '';
}
document.addEventListener('keydown', e => {
    if (document.getElementById('lightbox').classList.contains('hidden')) return;
    if (e.key === 'ArrowLeft')  lightboxNav(-1);
    if (e.key === 'ArrowRight') lightboxNav(1);
    if (e.key === 'Escape')     closeLightbox();
});
</script>
@endsection
