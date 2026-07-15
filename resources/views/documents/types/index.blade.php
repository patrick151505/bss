@extends('layouts.vertical', ['title' => 'Document Types', 'sub_title' => 'Documents', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

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

<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Document Types</h4>
        <p class="text-sm text-gray-400 mt-0.5">Manage certificate/document types and their templates.</p>
    </div>
    <div class="flex items-center gap-2 flex-nowrap shrink-0">
        <label for="sort-types" class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Sort by</label>
        <select id="sort-types" class="form-select text-sm py-1.5" onchange="sortTypeCards(this.value)">
            <option value="updated" selected>Last Updated</option>
            <option value="name">Name (A–Z)</option>
            <option value="fee">Fee (Low–High)</option>
            <option value="requests">Most Requests</option>
        </select>
        <a href="{{ route('documents.types.create') }}" class="btn bg-primary text-white flex items-center gap-2 whitespace-nowrap">
            <i class="mgc_add_line"></i> Add Document Type
        </a>
    </div>
</div>

@if($types->isEmpty())
<div class="card p-12 text-center">
    <i class="mgc_document_2_line text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
    <p class="text-gray-500 dark:text-gray-400 text-sm">No document types yet.</p>
    <a href="{{ route('documents.types.create') }}" class="btn bg-primary text-white mt-4 inline-flex items-center gap-2">
        <i class="mgc_add_line"></i> Create First Document Type
    </a>
</div>
@else
<div id="types-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach($types as $type)
    <div class="card p-5 flex flex-col gap-3 type-card"
         data-name="{{ strtolower($type->name) }}"
         data-fee="{{ $type->is_paid ? $type->fee : 0 }}"
         data-requests="{{ $type->requests_count }}"
         data-updated="{{ $type->updated_at->timestamp }}">
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                    <i class="mgc_document_2_line text-primary text-lg"></i>
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

        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
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
            $ver = $type->pinnedVersion;  // the version THIS document type is pinned to
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
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-primary/10 text-primary">
                        Using v{{ $ver->version }}
                    </span>
                    <span class="text-[10px] text-gray-400">/ {{ $tpl->versions_count }} version(s)</span>
                    @else
                    <span class="text-[10px] text-warning font-medium">No version pinned</span>
                    @endif
                </div>
                <p class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                    <i class="mgc_time_line"></i> Updated {{ $type->updated_at->diffForHumans() }}
                </p>
            </div>
            <a href="{{ route('documents.types.edit', $type) }}"
               class="shrink-0 text-gray-400 hover:text-primary transition" title="Change version">
                <i class="mgc_edit_2_line text-sm"></i>
            </a>
        </div>
        @else
        <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700">
            <i class="mgc_paper_line text-gray-300 dark:text-gray-600"></i>
            <span class="text-xs text-gray-400 italic">No paper template assigned</span>
            <a href="{{ route('documents.types.edit', $type) }}"
               class="ml-auto text-xs text-primary hover:underline">Assign →</a>
        </div>
        @endif

        <div class="flex items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-700 mt-1">
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
