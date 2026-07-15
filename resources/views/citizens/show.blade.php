@extends('layouts.vertical', ['title' => 'Citizen Profile', 'sub_title' => 'Citizen Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

@php
    $setting   = \App\Models\Setting::instance();
    $idFormat  = $setting->formatCitizenId($citizen->id);
    $age       = $citizen->bday ? $citizen->bday->age : null;
    $isSenior  = $age >= 60;

    // Address formatting (same logic as old controller)
    $zone = $citizen->addressZone;
    if ($zone && $zone->is_subd == 1) {
        $addrParts = [];
        if ($citizen->blk)   $addrParts[] = 'Blk ' . $citizen->blk;
        if ($citizen->lot)   $addrParts[] = 'Lot ' . $citizen->lot;
        if ($citizen->phase) $addrParts[] = 'Phase ' . $citizen->phase;
        $addrParts[] = $zone->description;
        $fullAddress = implode(' ', $addrParts);
    } elseif ($zone && $zone->is_subd == 2) {
        $fullAddress = $citizen->complete_address ?? $zone->description;
    } else {
        $addrParts = [];
        if ($citizen->no)     $addrParts[] = $citizen->no;
        if ($citizen->street) $addrParts[] = $citizen->street;
        if ($zone)            $addrParts[] = $zone->description;
        $fullAddress = implode(' ', array_filter($addrParts));
    }

    $approvalColor = match($citizen->approval_status) {
        1 => 'bg-success/15 text-success',
        2 => 'bg-warning/15 text-warning',
        3 => 'bg-danger/15 text-danger',
        default => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
    };
@endphp

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex items-center gap-3">
    <i class="mgc_check_circle_line text-success text-xl"></i>
    <p class="text-sm font-medium text-success">{{ session('success') }}</p>
</div>
@endif

<div class="grid grid-cols-12 gap-6">

    {{-- ── Left Column ── --}}
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">

        {{-- Personal Information --}}
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h5 class="card-title"><i class="mgc_user_3_line me-2 text-primary"></i>Personal Information</h5>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">First Name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->fname }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Middle Name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->mname ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Last Name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->lname }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Suffix</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->suffix ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Gender</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            @if($citizen->gender == 1)
                                <span class="inline-flex items-center gap-1 text-blue-600"><i class="mgc_male_line"></i> Male</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-pink-500"><i class="mgc_female_line"></i> Female</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Civil Status</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->civilStatus->description ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Birthday</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $citizen->bday ? $citizen->bday->format('F j, Y') : '—' }}
                            @if($age !== null)
                                <span class="text-gray-400 text-xs">({{ $age }} yrs old)</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Place of Birth</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->bplace ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Citizenship</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->citizenship ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Occupation</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->occupation ?: '—' }}</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Address & Residency --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_home_3_line me-2 text-primary"></i>Address & Residency</h5>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">

                    <div class="md:col-span-2">
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Complete Address</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $fullAddress ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Zone / Area</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->addressZone->description ?? '—' }}</p>
                    </div>

                    @if($zone && $zone->is_subd == 1)
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Blk / Lot / Phase</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $citizen->blk ? 'Blk '.$citizen->blk : '' }}
                            {{ $citizen->lot ? ' Lot '.$citizen->lot : '' }}
                            {{ $citizen->phase ? ' Phase '.$citizen->phase : '' }}
                        </p>
                    </div>
                    @else
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">House No. / Street</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ trim(($citizen->no ?? '') . ' ' . ($citizen->street ?? '')) ?: '—' }}
                        </p>
                    </div>
                    @endif

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Year / Month of Stay</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $citizen->year_stay ? $citizen->year_stay->format('F Y') : '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Owner Status</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $citizen->owner_status ? 'Owner' : 'Renter / Others' }}
                        </p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_phone_line me-2 text-primary"></i>Contact Information</h5>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Contact No.</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            @if($citizen->contact)
                                <i class="mgc_phone_line me-1 text-gray-400"></i>{{ $citizen->contact }}
                            @else
                                —
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Email Address</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            @if($citizen->email)
                                <i class="mgc_mail_line me-1 text-gray-400"></i>{{ $citizen->email }}
                            @else
                                —
                            @endif
                        </p>
                    </div>

                </div>
            </div>
        </div>

        {{-- In Case of Emergency --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_alert_line me-2 text-danger"></i>In Case of Emergency</h5>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Full Name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->ic_fullname ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Relationship</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->ic_relationship ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Contact No.</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->ic_contact ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Email</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->ic_email ?: '—' }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Address</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $citizen->ic_address ?: '—' }}</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Tags --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title"><i class="mgc_tag_line me-2 text-primary"></i>Tags</h5>
                <button onclick="openTagModal()" class="btn btn-sm bg-primary/10 text-primary flex items-center gap-1.5">
                    <i class="mgc_edit_line"></i> Manage Tags
                </button>
            </div>
            <div class="p-5">
                <div id="citizen-tags" class="flex flex-wrap gap-2 min-h-[32px]">
                    @forelse($citizen->tags as $tag)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold text-white"
                          style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                    @empty
                    <span class="text-sm text-gray-400" id="no-tags-msg">No tags assigned yet.</span>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Tag assign modal --}}
        <div id="tag-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h6 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <i class="mgc_tag_line text-primary"></i> Assign Tags
                    </h6>
                    <button onclick="closeTagModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i class="mgc_close_line text-xl"></i>
                    </button>
                </div>
                <div class="p-5 flex flex-col gap-4">
                    <div class="relative">
                        <input type="text" id="tag-search" placeholder="Search tags…"
                               oninput="filterTags()"
                               class="form-input ps-9 w-full">
                        <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div id="tag-checklist" class="flex flex-col gap-1 max-h-64 overflow-y-auto">
                        <p class="text-sm text-gray-400 text-center py-4">Loading…</p>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('tags.index') }}" target="_blank"
                           class="text-xs text-primary hover:underline flex items-center gap-1">
                            <i class="mgc_external_link_line"></i> Manage tags
                        </a>
                        <div class="flex gap-2">
                            <button onclick="closeTagModal()" class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                                Cancel
                            </button>
                            <button onclick="saveTags()" class="btn btn-sm bg-primary text-white">
                                <i class="mgc_save_line mr-1"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        @if($citizen->note)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_document_line me-2 text-primary"></i>Notes</h5>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $citizen->note }}</p>
            </div>
        </div>
        @endif

        {{-- ── Citizen History / Profiling ── --}}
        @php
            $blotterInvolvement = \App\Models\BlotterParty::with('blotter')
                ->where('citizen_id', $citizen->id)
                ->orderByDesc('created_at')
                ->get();

            $profileTabs = [
                ['id' => 'tab-events',    'label' => 'Events',    'icon' => 'mgc_calendar_line',    'count' => $eventAttendances->count(),   'color' => 'primary'],
                ['id' => 'tab-wins',      'label' => 'Raffle',    'icon' => 'mgc_gift_line',         'count' => $eventWins->count(),          'color' => 'warning'],
                ['id' => 'tab-inventory', 'label' => 'Releases',  'icon' => 'mgc_box_line',          'count' => $inventoryReleases->count(),  'color' => 'success'],
                ['id' => 'tab-docs',      'label' => 'Documents', 'icon' => 'mgc_file_line',         'count' => $documentRequests->count(),   'color' => 'info'],
                ['id' => 'tab-blotters',  'label' => 'Blotters',  'icon' => 'mgc_shield_line',       'count' => $blotterInvolvement->count(), 'color' => 'danger'],
            ];
        @endphp

        <div class="card">
            {{-- Tab header --}}
            <div class="border-b border-gray-200 dark:border-gray-700 px-5 pt-4">
                <div class="flex items-center gap-1 mb-0 overflow-x-auto">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 me-3 shrink-0">History</p>
                    @foreach($profileTabs as $tab)
                    <button type="button"
                        data-profile-tab="{{ $tab['id'] }}"
                        class="profile-tab-btn shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-medium border-b-2 transition-colors
                            {{ $loop->first ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        <i class="{{ $tab['icon'] }}"></i>
                        {{ $tab['label'] }}
                        @if($tab['count'] > 0)
                            <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold
                                bg-{{ $tab['color'] }}/15 text-{{ $tab['color'] }}">{{ $tab['count'] }}</span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- ── Tab: Events Attended ── --}}
            <div id="tab-events" class="profile-tab-panel">
                @if($eventAttendances->isEmpty())
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <i class="mgc_calendar_line text-4xl"></i>
                        <p class="text-sm">No event attendance on record.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Check-in</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Method</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($eventAttendances as $ea)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">
                                    {{ $ea->event?->title ?? '—' }}
                                    @if($ea->event?->category)
                                        <span class="block text-[10px] text-gray-400">{{ ucfirst($ea->event->category) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $ea->event?->event_date?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $ea->time_in?->format('h:i A') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @php $method = $ea->method ?? 'manual'; @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium
                                        {{ $method === 'qr' ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        <i class="{{ $method === 'qr' ? 'mgc_qrcode_line' : 'mgc_edit_line' }}"></i>
                                        {{ strtoupper($method) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($ea->event)
                                    <a href="{{ route('events.show', $ea->event) }}"
                                       class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition-colors inline-flex">
                                        <i class="mgc_eye_line text-sm"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- ── Tab: Raffle Wins ── --}}
            <div id="tab-wins" class="profile-tab-panel hidden">
                @if($eventWins->isEmpty())
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <i class="mgc_gift_line text-4xl"></i>
                        <p class="text-sm">No raffle wins on record.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Round</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Prize</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Drawn At</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($eventWins as $win)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">
                                    {{ $win->event?->title ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-warning/10 text-warning text-xs font-bold">
                                        {{ $win->round }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200">
                                    {{ $win->prize_label ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $win->drawn_at?->format('M d, Y h:i A') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($win->event)
                                    <a href="{{ route('events.show', $win->event) }}"
                                       class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition-colors inline-flex">
                                        <i class="mgc_eye_line text-sm"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- ── Tab: Inventory Releases ── --}}
            <div id="tab-inventory" class="profile-tab-panel hidden">
                @if($inventoryReleases->isEmpty())
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <i class="mgc_box_line text-4xl"></i>
                        <p class="text-sm">No inventory releases on record.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Purpose</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Released</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($inventoryReleases as $release)
                            @php
                                $releaseStatusColor = match($release->status) {
                                    'released' => 'bg-success/10 text-success',
                                    'approved' => 'bg-info/10 text-info',
                                    'rejected' => 'bg-danger/10 text-danger',
                                    default    => 'bg-warning/10 text-warning',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $release->purpose ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @foreach($release->items->take(3) as $ri)
                                        <span class="inline-block text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded px-1.5 py-0.5 me-0.5 mb-0.5">
                                            {{ $ri->item?->name ?? '?' }} ×{{ $ri->quantity }}
                                        </span>
                                    @endforeach
                                    @if($release->items->count() > 3)
                                        <span class="text-[10px] text-gray-400">+{{ $release->items->count() - 3 }} more</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $release->released_at?->format('M d, Y') ?? ($release->created_at?->format('M d, Y') ?? '—') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $releaseStatusColor }}">
                                        {{ ucfirst($release->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('inventory.releases.show', $release) }}"
                                       class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition-colors inline-flex">
                                        <i class="mgc_eye_line text-sm"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- ── Tab: Document Requests ── --}}
            <div id="tab-docs" class="profile-tab-panel hidden">
                @if($documentRequests->isEmpty())
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <i class="mgc_file_line text-4xl"></i>
                        <p class="text-sm">No document requests on record.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Document</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Requested</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Released</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Fee</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($documentRequests as $dr)
                            @php
                                $drStatusColor = match($dr->status) {
                                    'released' => 'bg-success/10 text-success',
                                    'approved' => 'bg-info/10 text-info',
                                    'rejected' => 'bg-danger/10 text-danger',
                                    default    => 'bg-warning/10 text-warning',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">
                                    {{ $dr->documentType?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $dr->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $dr->released_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($dr->fee > 0)
                                        <span class="{{ $dr->fee_paid ? 'text-success' : 'text-warning' }} text-xs font-semibold">
                                            ₱{{ number_format($dr->fee, 2) }}
                                            @if($dr->fee_paid) <i class="mgc_check_line text-[10px]"></i> @endif
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Free</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $drStatusColor }}">
                                        {{ ucfirst($dr->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('documents.requests.show', $dr) }}"
                                       class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition-colors inline-flex">
                                        <i class="mgc_eye_line text-sm"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- ── Tab: Blotter Involvement ── --}}
            <div id="tab-blotters" class="profile-tab-panel hidden">
                @if($blotterInvolvement->isEmpty())
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <i class="mgc_shield_line text-4xl"></i>
                        <p class="text-sm">No blotter records on file.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Blotter No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Nature / Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Date Filed</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($blotterInvolvement as $bp)
                            @if($bp->blotter)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-mono font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $bp->blotter->blotter_no }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($bp->role === 'complainant')
                                    <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[10px] font-medium bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success inline-block"></span> Complainant
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[10px] font-medium bg-danger/10 text-danger">
                                        <span class="w-1.5 h-1.5 rounded-full bg-danger inline-block"></span> Respondent
                                    </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $bp->blotter->typeLabel() }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $bp->blotter->filed_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $bp->blotter->statusColor() }}">
                                        {{ $bp->blotter->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('blotters.show', $bp->blotter) }}"
                                       class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition-colors inline-flex">
                                        <i class="mgc_eye_line text-sm"></i>
                                    </a>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>{{-- end profiling card --}}

    </div>

    {{-- ── Right Column ── --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

        {{-- Profile Card --}}
        <div class="card">
            <div class="p-6 flex flex-col items-center text-center gap-3">

                @if($citizen->profile)
                    <img src="{{ asset('storage/' . $citizen->profile) }}"
                        class="w-24 h-24 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow"
                        alt="{{ $citizen->fname }}">
                @else
                    <div class="w-24 h-24 rounded-full bg-primary/20 flex items-center justify-center shadow text-primary font-bold text-2xl">
                        {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                    </div>
                @endif

                <div>
                    <h5 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                        {{ $citizen->lname }}, {{ $citizen->fname }}
                        @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                        @if($citizen->suffix) {{ $citizen->suffix }} @endif
                    </h5>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $idFormat }}</p>
                </div>

                <span class="text-xs font-medium rounded-md px-3 py-1 {{ $approvalColor }}">
                    {{ $citizen->approvalStatus->description ?? 'Unknown' }}
                </span>

                <div class="flex flex-wrap justify-center gap-1.5">
                    @if($citizen->is_pwd)
                        <span class="text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded px-2 py-0.5">PWD</span>
                    @endif
                    @if($citizen->voters)
                        <span class="text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 rounded px-2 py-0.5">
                            Voter {{ $citizen->pricinct_no ? '· '.$citizen->pricinct_no : '' }}
                        </span>
                    @endif
                    @if($citizen->is_soloparents)
                        <span class="text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 rounded px-2 py-0.5">Solo Parent</span>
                    @endif
                    @if($isSenior)
                        <span class="text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 rounded px-2 py-0.5">Senior Citizen</span>
                    @endif
                    @if(!$citizen->is_active)
                        <span class="text-xs font-medium bg-red-100 text-red-600 rounded px-2 py-0.5">Inactive</span>
                    @endif
                </div>

                <div class="flex gap-2 mt-1 w-full">
                    <a href="{{ route('citizens.edit', $citizen->id) }}"
                        class="btn bg-primary text-white flex-1 text-sm">
                        <i class="mgc_edit_line me-1"></i> Edit
                    </a>
                    <a href="{{ route('citizens.index') }}"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm px-3">
                        <i class="mgc_arrow_left_line"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Household Card --}}
        @php
            $hhRoleLabel = match((int)$citizen->isHead) {
                2 => 'Head of Household',
                1 => 'Head of Family',
                default => 'Family Member',
            };
            $hhColor = match((int)$citizen->isHead) {
                2 => 'success',
                1 => 'primary',
                default => 'warning',
            };
            $hhIcon = match((int)$citizen->isHead) {
                2 => 'mgc_home_3_fill',
                1 => 'mgc_group_2_fill',
                default => 'mgc_user_3_fill',
            };
        @endphp
        <div class="card border border-{{ $currentHousehold ? $hhColor : 'gray-200 dark:border-gray-700' }}/30">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <i class="mgc_home_3_line text-{{ $currentHousehold ? $hhColor : 'gray-400' }}"></i>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Household</p>
                </div>
                @if($currentHousehold)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-{{ $hhColor }}/10 text-{{ $hhColor }}">
                        <i class="{{ $hhIcon }} text-xs"></i>
                        {{ $hhRoleLabel }}
                        @if($citizen->isHead == 0 && $citizen->relation)
                            · {{ $citizen->relation->description }}
                        @endif
                    </span>
                @endif
            </div>

            @if($currentHousehold)
                {{-- Household header info --}}
                <div class="px-5 pt-4 pb-3 flex items-start gap-3">
                    <span class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                        <i class="mgc_home_3_fill text-success text-lg"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-snug">{{ $currentHousehold->full_address }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $currentHousehold->formatted_id }}</p>
                        @if($currentHousehold->householdHead)
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="text-gray-400">Household Head:</span>
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $currentHousehold->householdHead->full_name }}</span>
                            </p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 shrink-0 mt-0.5">{{ $currentHousehold->families->count() }} {{ Str::plural('family', $currentHousehold->families->count()) }}</span>
                </div>

                {{-- All families in the household --}}
                @foreach($currentHousehold->families as $family)
                    @php
                        $isCitizenFamily = $currentFamily && $family->id === $currentFamily->id;
                    @endphp
                    <div class="mx-4 mb-3 rounded-xl border-2 {{ $isCitizenFamily ? 'border-'.$hhColor.'/50 bg-'.$hhColor.'/5' : 'border-gray-100 dark:border-gray-700' }}">
                        {{-- Family header --}}
                        <div class="flex items-center justify-between px-3 py-2 border-b border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center gap-1.5">
                                <i class="mgc_group_2_line text-gray-400 text-sm"></i>
                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $family->formatted_id }}</p>
                            </div>
                            @if($isCitizenFamily)
                                <span class="text-xs font-bold text-{{ $hhColor }} bg-{{ $hhColor }}/10 px-2 py-0.5 rounded-full">Your Family</span>
                            @endif
                        </div>

                        {{-- Family head --}}
                        @if($family->head)
                        @php $fh = $family->head; $fhIsMe = $fh->id === $citizen->id; @endphp
                        <div class="flex items-center gap-2 px-3 py-2 {{ $family->members->count() ? 'border-b border-gray-100 dark:border-gray-700/60' : '' }}">
                            @if($fh->profile)
                                <img src="{{ $fh->profile_photo }}" alt="{{ $fh->full_name }}"
                                    class="w-7 h-7 rounded-full object-cover shrink-0 ring-2 ring-{{ $fhIsMe ? $hhColor : 'primary' }}/30">
                            @else
                                <div class="w-7 h-7 rounded-full {{ $fhIsMe ? 'bg-'.$hhColor.'/20 text-'.$hhColor : 'bg-primary/10 text-primary' }} flex items-center justify-center shrink-0 text-xs font-bold">
                                    {{ strtoupper(substr($fh->fname,0,1)) }}{{ strtoupper(substr($fh->lname,0,1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">
                                    {{ $fh->full_name }}
                                    @if($fhIsMe)<span class="text-{{ $hhColor }}">(You)</span>@endif
                                </p>
                                <p class="text-xs text-gray-400">Family Head</p>
                            </div>
                            <i class="mgc_crown_line text-xs text-primary shrink-0"></i>
                        </div>
                        @endif

                        {{-- Members (indented under family head) --}}
                        @foreach($family->members as $member)
                        @php $mIsMe = $member->id === $citizen->id; @endphp
                        <div class="flex items-start gap-0 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700/40' : '' }}">
                            {{-- Tree indent --}}
                            <div class="flex flex-col items-center w-8 shrink-0 self-stretch">
                                <div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 ml-4"></div>
                                <div class="w-3 h-px bg-gray-200 dark:bg-gray-700 ml-4 mt-3.5"></div>
                                @if(!$loop->last)
                                <div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 ml-4"></div>
                                @else
                                <div class="flex-1"></div>
                                @endif
                            </div>
                            {{-- Member row --}}
                            <div class="flex items-center gap-2 pr-3 py-2 flex-1 min-w-0">
                                @if($member->profile)
                                    <img src="{{ $member->profile_photo }}" alt="{{ $member->full_name }}"
                                        class="w-6 h-6 rounded-full object-cover shrink-0 ring-2 ring-{{ $mIsMe ? $hhColor : 'warning' }}/40">
                                @else
                                    <div class="w-6 h-6 rounded-full {{ $mIsMe ? 'bg-'.$hhColor.'/20 text-'.$hhColor : 'bg-warning/10 text-warning' }} flex items-center justify-center shrink-0 text-[10px] font-bold">
                                        {{ strtoupper(substr($member->fname,0,1)) }}{{ strtoupper(substr($member->lname,0,1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">
                                        {{ $member->full_name }}
                                        @if($mIsMe)<span class="text-{{ $hhColor }}">(You)</span>@endif
                                    </p>
                                    <p class="text-[10px] text-gray-400">{{ $member->relation?->description ?? 'Member' }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        @if(!$family->head && !$family->members->count())
                        <p class="text-xs text-gray-400 px-3 py-2">No members.</p>
                        @endif
                    </div>
                @endforeach

                <div class="px-5 pb-4">
                    <a href="{{ route('households.index') }}" class="text-xs text-primary hover:underline">View all households</a>
                </div>

            @else
                <div class="px-5 py-8 text-center">
                    <i class="mgc_home_3_line text-3xl text-gray-200 dark:text-gray-700 mb-2 block"></i>
                    <p class="text-xs text-gray-400">Not assigned to any household.</p>
                    <a href="{{ route('citizens.edit', $citizen->id) }}"
                        class="text-xs text-primary hover:underline mt-1 inline-block">Assign via Edit</a>
                </div>
            @endif
        </div>

        {{-- QR Code --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_qrcode_line me-2 text-primary"></i>QR Code</h5>
            </div>
            <div class="p-6 flex flex-col items-center gap-3">
                <div class="p-3 bg-white border border-gray-200 dark:border-gray-600 rounded-lg inline-block">
                    <canvas id="citizen-qrcode" data-value="{{ $citizen->qrcode }}"></canvas>
                </div>
                <p class="text-xs text-gray-400 text-center break-all">{{ $citizen->qrcode }}</p>
            </div>
        </div>

        {{-- Record Info --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_information_line me-2 text-primary"></i>Record Info</h5>
            </div>
            <div class="p-5 flex flex-col divide-y divide-gray-100 dark:divide-gray-700">

                <div class="flex justify-between items-center py-2.5 text-sm">
                    <span class="text-gray-400">Registered</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ $citizen->date_created ? $citizen->date_created->format('M d, Y') : '—' }}
                    </span>
                </div>

                <div class="flex justify-between items-center py-2.5 text-sm">
                    <span class="text-gray-400">Last Updated</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ $citizen->date_last_updated ? $citizen->date_last_updated->format('M d, Y') : '—' }}
                    </span>
                </div>

                <div class="flex justify-between items-center py-2.5 text-sm">
                    <span class="text-gray-400">Date Approved</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ $citizen->date_approved ? $citizen->date_approved->format('M d, Y') : '—' }}
                    </span>
                </div>

                @if($citizen->approvedBy)
                <div class="flex justify-between items-center py-2.5 text-sm">
                    <span class="text-gray-400">Approved By</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $citizen->approvedBy->name }}</span>
                </div>
                @endif

                <div class="flex justify-between items-center py-2.5 text-sm">
                    <span class="text-gray-400">ID Release</span>
                    <span class="{{ $citizen->is_id_release ? 'text-success' : 'text-gray-400' }} font-medium">
                        {{ $citizen->is_id_release ? 'Released' : 'Not Released' }}
                    </span>
                </div>

                <div class="flex justify-between items-center py-2.5 text-sm">
                    <span class="text-gray-400">Verified</span>
                    <span class="{{ $citizen->is_verify ? 'text-success' : 'text-gray-400' }} font-medium">
                        {{ $citizen->is_verify ? 'Verified' : 'Unverified' }}
                    </span>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection

@section('script')
@vite(['resources/js/citizens/show.js'])
<script>
(function () {
    const btns   = document.querySelectorAll('.profile-tab-btn');
    const panels = document.querySelectorAll('.profile-tab-panel');

    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.profileTab;

            btns.forEach(b => {
                b.classList.remove('border-primary', 'text-primary');
                b.classList.add('border-transparent', 'text-gray-500');
            });
            btn.classList.add('border-primary', 'text-primary');
            btn.classList.remove('border-transparent', 'text-gray-500');

            panels.forEach(p => p.classList.toggle('hidden', p.id !== target));
        });
    });
})();
</script>

<script>
// ── Tags ─────────────────────────────────────────────────────────────
const TAG_ALL_URL  = '{{ route('tags.all') }}';
const TAG_SYNC_URL = '{{ route('citizens.tags.sync', $citizen->id) }}';
const CSRF         = '{{ csrf_token() }}';

let allTags         = [];
let selectedTagIds  = new Set({{ json_encode($citizen->tags->pluck('id')) }});

function openTagModal() {
    document.getElementById('tag-modal').classList.replace('hidden', 'flex');
    document.getElementById('tag-search').value = '';

    if (allTags.length === 0) {
        fetch(TAG_ALL_URL)
            .then(r => r.json())
            .then(tags => { allTags = tags; renderChecklist(''); });
    } else {
        renderChecklist('');
    }
}

function closeTagModal() {
    document.getElementById('tag-modal').classList.replace('flex', 'hidden');
}

function filterTags() {
    renderChecklist(document.getElementById('tag-search').value.trim().toLowerCase());
}

function renderChecklist(q) {
    const list    = document.getElementById('tag-checklist');
    const filtered = allTags.filter(t => !q || t.name.toLowerCase().includes(q));

    if (!filtered.length) {
        list.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">No tags found.</p>';
        return;
    }

    list.innerHTML = filtered.map(t => `
        <label class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
            <input type="checkbox" value="${t.id}" ${selectedTagIds.has(t.id) ? 'checked' : ''}
                   onchange="toggleTag(${t.id}, this.checked)"
                   class="rounded border-gray-300 text-primary focus:ring-primary">
            <div class="flex flex-col gap-0.5 min-w-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold text-white w-fit"
                      style="background:${t.color}">${t.name}</span>
                ${t.description ? `<span class="text-xs text-gray-400 leading-snug">${t.description}</span>` : ''}
            </div>
        </label>
    `).join('');
}

function toggleTag(id, checked) {
    if (checked) selectedTagIds.add(id);
    else selectedTagIds.delete(id);
}

function saveTags() {
    fetch(TAG_SYNC_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ tag_ids: [...selectedTagIds] }),
    })
    .then(r => r.json())
    .then(data => {
        closeTagModal();
        renderCitizenTags(data.tags);
    });
}

function renderCitizenTags(tags) {
    const el = document.getElementById('citizen-tags');
    if (!tags.length) {
        el.innerHTML = '<span class="text-sm text-gray-400" id="no-tags-msg">No tags assigned yet.</span>';
        return;
    }
    el.innerHTML = tags.map(t =>
        `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold text-white"
               style="background:${t.color}">${t.name}</span>`
    ).join('');
}

// Close modal on backdrop click
document.getElementById('tag-modal').addEventListener('click', function(e) {
    if (e.target === this) closeTagModal();
});
</script>
@endsection
