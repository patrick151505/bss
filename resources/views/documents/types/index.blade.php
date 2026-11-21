@extends('layouts.vertical', [
    'title'         => 'Document Types',
    'sub_title'     => 'Documents',
    'sub_title_url' => route('documents.requests.index'),
    'tagline'       => 'Manage certificate/document types and their templates.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

{{-- Heading comes from the layout's `tagline`; repeating it here duplicated
     the page title. flex-wrap lets the controls stack on a phone instead of
     overflowing — this row holds a select and two long buttons. --}}
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $types->count() }}</span>
        document {{ \Illuminate\Support\Str::plural('type', $types->count()) }}
    </p>
    <div class="flex items-center gap-2 flex-wrap">
        {{-- w-auto: form-select is full-width by default, which pushed the
             buttons onto their own line. --}}
        <label for="sort-types" class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Sort by</label>
        <select id="sort-types" class="form-select w-auto text-sm py-1.5" onchange="sortTypeCards(this.value)">
            <option value="updated" selected>Last Updated</option>
            <option value="name">Name (A–Z)</option>
            <option value="fee">Fee (Low–High)</option>
            <option value="requests">Most Requests</option>
        </select>
        <a href="{{ route('documents.types.samples') }}" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-2 whitespace-nowrap">
            <i class="mgc_copy_line"></i> Template Samples
        </a>
        @can('documents.create')
        <a href="{{ route('documents.types.create') }}" class="btn bg-primary text-white flex items-center gap-2 whitespace-nowrap">
            <i class="mgc_add_line"></i> Add Document Type
        </a>
        @endcan
    </div>
</div>

@if($types->isEmpty())
<div class="card p-12 text-center">
    <i class="mgc_document_2_line text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
    <p class="text-gray-500 dark:text-gray-400 text-sm">No document types yet.</p>
    @can('documents.create')
    <a href="{{ route('documents.types.create') }}" class="btn bg-primary text-white mt-4 inline-flex items-center gap-2">
        <i class="mgc_add_line"></i> Create First Document Type
    </a>
    @endcan
</div>
@else
<div id="types-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach($types as $type)
    {{-- h-full + the footer's mt-auto keep cards in a row the same height
         with their Edit/Deactivate buttons on one line. --}}
    <div class="card p-5 flex flex-col gap-3 h-full type-card"
         data-name="{{ strtolower($type->name) }}"
         data-fee="{{ $type->is_paid ? $type->fee : 0 }}"
         data-requests="{{ $type->requests_count }}"
         data-updated="{{ $type->updated_at->timestamp }}">
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3">
                {{-- Tinted with the type's own color --}}
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                     style="background-color: {{ $type->badge_color }}26; color: {{ $type->badge_color }};"
                     title="Color {{ $type->badge_color }}">
                    <i class="mgc_document_2_line text-lg"></i>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 dark:text-gray-100 leading-tight">{{ $type->name }}</h6>
                    @if($type->short_name)
                    <p class="text-xs text-gray-400">{{ $type->short_name }}</p>
                    @endif
                </div>
            </div>
            @if($type->is_active)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success/15 text-success shrink-0">Active</span>
            @else
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-400 shrink-0">Inactive</span>
            @endif
        </div>

        @if($type->description)
        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $type->description }}</p>
        @endif

        <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1">
                <i class="mgc_wallet_3_line"></i>
                @if($type->is_paid)
                    <span class="font-semibold text-warning">₱ {{ number_format($type->fee, 2) }}</span>
                @else
                    <span class="font-semibold text-success">FREE</span>
                @endif
            </span>
            <span class="flex items-center gap-1">
                <i class="mgc_box_line"></i>
                {{ $type->fields_count ?? $type->fields->count() }} field(s)
            </span>
            <span class="flex items-center gap-1">
                <i class="mgc_inbox_line"></i>
                {{ $type->requests_count }} request(s)
            </span>
        </div>

        @if($type->requires_approval)
        <div class="text-[10px] text-info flex items-center gap-1">
            <i class="mgc_shield_check_line"></i> Requires approval before release
        </div>
        @endif

        {{-- Paper Template info --}}
        @if($type->template)
        @php
            $tpl = $type->template;
            // The pinned version, or — on "Latest" — the template's current version.
            $isLatest = ! $type->document_template_version_id;
            $ver = $isLatest ? $tpl->currentVersion : $type->pinnedVersion;
        @endphp
        <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
            {{-- Thumbnail of the pinned version --}}
            <div class="w-8 h-10 rounded border border-gray-200 dark:border-gray-600 overflow-hidden shrink-0 bg-white">
                @if($ver?->paper_bg)
                <img src="{{ asset('storage/' . $ver->paper_bg) }}"
                     class="w-full h-full object-cover object-top" alt="">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="mgc_paper_line text-gray-300 text-[10px]"></i>
                </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">{{ $tpl->name }}</span>
                    @if($ver)
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-primary/10 text-primary"
                          title="{{ $isLatest ? 'Follows the template’s current version' : 'Pinned to this version' }}">
                        @if($isLatest)<i class="mgc_refresh_2_line"></i> Latest · v{{ $ver->version }}@else Pinned v{{ $ver->version }}@endif
                    </span>
                    <span class="text-[10px] text-gray-400">/ {{ $tpl->versions_count }} version(s)</span>
                    @else
                    <span class="text-[10px] text-warning font-medium">Template has no version yet</span>
                    @endif
                </div>
                <p class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                    <i class="mgc_time_line"></i> Updated {{ $type->updated_at->diffForHumans() }}
                </p>
            </div>
            @can('documents.edit')
            <a href="{{ route('documents.types.edit', $type) }}"
               class="shrink-0 text-gray-400 hover:text-primary transition" title="Change version">
                <i class="mgc_edit_2_line text-sm"></i>
            </a>
            @endcan
        </div>
        @else
        <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700">
            <i class="mgc_paper_line text-gray-300 dark:text-gray-600"></i>
            <span class="text-xs text-gray-400 italic">No paper template assigned</span>
            @can('documents.edit')
            <a href="{{ route('documents.types.edit', $type) }}"
               class="ml-auto text-xs text-primary hover:underline">Assign →</a>
            @endcan
        </div>
        @endif

        @can('documents.edit')
        <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700 mt-auto">
            <a href="{{ route('documents.types.edit', $type) }}"
               class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3 flex items-center gap-1 flex-1 justify-center">
                <i class="mgc_edit_2_line"></i> Edit
            </a>
            <form action="{{ route('documents.types.toggle', $type) }}" method="POST">
                @csrf @method('PATCH')
                @if($type->is_active)
                <button type="submit"
                        class="btn border-warning/40 text-warning text-sm py-1.5 px-3 hover:bg-warning/10 flex items-center gap-1"
                        title="Deactivate">
                    <i class="mgc_pause_circle_line"></i> Deactivate
                </button>
                @else
                <button type="submit"
                        class="btn border-success/40 text-success text-sm py-1.5 px-3 hover:bg-success/10 flex items-center gap-1"
                        title="Activate">
                    <i class="mgc_play_circle_line"></i> Activate
                </button>
                @endif
            </form>
        </div>
        @endcan
    </div>
    @endforeach
</div>
@endif

@endsection

@push('inline-scripts')
<script>
function sortTypeCards(criteria) {
    const grid  = document.getElementById('types-grid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.type-card'));

    const getters = {
        updated:  card => -parseInt(card.dataset.updated, 10),
        name:     card => card.dataset.name,
        fee:      card => parseFloat(card.dataset.fee),
        requests: card => -parseInt(card.dataset.requests, 10),
    };
    const getValue = getters[criteria] || getters.updated;

    cards.sort((a, b) => {
        const va = getValue(a), vb = getValue(b);
        if (va < vb) return -1;
        if (va > vb) return 1;
        return 0;
    });

    cards.forEach(card => grid.appendChild(card));
}

document.addEventListener('DOMContentLoaded', () => sortTypeCards('updated'));
</script>
@endpush
