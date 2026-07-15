@extends('layouts.vertical', [
    'title'         => 'Events',
    'sub_title'     => 'Services',
    'sub_title_url' => route('events.index'),
    'tagline'       => 'Event Attendance & Raffle Management',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

{{-- KPI Row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <i class="mgc_calendar_line text-xl text-primary"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Events</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-total">—</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
            <i class="mgc_check_circle_line text-xl text-success"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Open</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-open">—</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-dark/10 flex items-center justify-center shrink-0">
            <i class="mgc_close_circle_line text-xl text-dark"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Closed</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-closed">—</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
            <i class="mgc_user_3_line text-xl text-info"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Attendance</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-attendance">—</p>
        </div>
    </div>
</div>

{{-- Filter + action bar --}}
<div class="card mb-5">
    <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
        <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">
            <i class="mgc_search_line text-xs"></i>
        </span>
        <span class="text-sm font-semibold text-primary uppercase tracking-wide">Filter</span>
    </div>
    <div class="p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5">Search</label>
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" id="filter-search" class="form-input pl-8 w-full" placeholder="Title, category, venue…">
                </div>
            </div>
            <div class="min-w-36">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5">Status</label>
                <select id="filter-status" class="form-select w-full">
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="flex gap-2 shrink-0">
                <button id="btn-search" class="btn bg-primary text-white gap-1.5 text-sm">
                    <i class="mgc_search_line"></i> Search
                </button>
                <button id="btn-reset" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white text-sm px-3">
                    <i class="mgc_close_line"></i>
                </button>
                <a href="{{ route('events.create') }}" class="btn bg-success text-white gap-1.5 text-sm">
                    <i class="mgc_add_line"></i> New Event
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Event cards grid --}}
<div id="event-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"></div>
<div id="event-pagination" class="hidden flex items-center justify-between mt-5 px-1">
    <p class="text-sm text-gray-400" id="pagination-info"></p>
    <div class="flex gap-1" id="pagination-btns"></div>
</div>

@endsection

@push('inline-scripts')
<script>
const ROUTES = {
    kpi:      '{{ route('events.kpi') }}',
    retrieve: '{{ route('events.retrieve') }}',
    showBase: '{{ url('events') }}',
};
const CSRF = '{{ csrf_token() }}';

let currentPage   = 1;
let currentSearch = '';
let currentStatus = '';

document.addEventListener('DOMContentLoaded', () => {
    loadKpi();
    loadEvents();

    document.getElementById('btn-search').addEventListener('click', () => {
        currentSearch = document.getElementById('filter-search').value;
        currentStatus = document.getElementById('filter-status').value;
        loadEvents(1);
    });

    document.getElementById('btn-reset').addEventListener('click', () => {
        document.getElementById('filter-search').value = '';
        document.getElementById('filter-status').value = '';
        currentSearch = '';
        currentStatus = '';
        loadEvents(1);
    });

    document.getElementById('filter-search').addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('btn-search').click();
    });
});

function loadKpi() {
    fetch(ROUTES.kpi).then(r => r.json()).then(res => {
        if (!res.success) return;
        document.getElementById('kpi-total').textContent      = res.total;
        document.getElementById('kpi-open').textContent       = res.open;
        document.getElementById('kpi-closed').textContent     = res.closed;
        document.getElementById('kpi-attendance').textContent = res.attendance;
    });
}

function loadEvents(page = 1) {
    currentPage = page;
    const grid  = document.getElementById('event-grid');
    grid.innerHTML = skeletonCards();

    const params = new URLSearchParams({ page, limit: 9, search: currentSearch, status: currentStatus });

    fetch(ROUTES.retrieve + '?' + params).then(r => r.json()).then(res => {
        if (!res.success || !res.data || res.data.length === 0) {
            grid.innerHTML = emptyState();
            document.getElementById('event-pagination').classList.add('hidden');
            return;
        }
        grid.innerHTML = res.data.map(renderCard).join('');
        renderPagination(res.total, res.page, res.limit);
    });
}

function renderCard(e) {
    const isOpen = e.status === 'open';
    const cover  = e.cover_photo_url
        ? `<img src="${e.cover_photo_url}" class="w-full h-36 object-cover">`
        : `<div class="w-full h-36 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
               <i class="mgc_calendar_line text-4xl text-primary/30"></i>
           </div>`;

    return `
    <div class="card overflow-hidden hover:shadow-lg transition-shadow">
        <a href="${ROUTES.showBase}/${e.id}" class="block">
            ${cover}
        </a>
        <div class="p-4">
            <div class="flex items-start justify-between gap-2 mb-2">
                <a href="${ROUTES.showBase}/${e.id}" class="font-bold text-gray-800 dark:text-gray-100 text-sm leading-snug hover:text-primary line-clamp-2 flex-1">${e.title}</a>
                <span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 shrink-0 ${isOpen ? 'bg-success/15 text-success' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'}">
                    <span class="w-1.5 h-1.5 rounded-full ${isOpen ? 'bg-success' : 'bg-gray-400'} inline-block"></span>
                    ${isOpen ? 'Open' : 'Closed'}
                </span>
            </div>

            ${e.category ? `<p class="text-xs text-primary font-medium mb-1">${e.category}</p>` : ''}

            <div class="space-y-1 mt-2">
                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <i class="mgc_calendar_line shrink-0"></i>
                    <span>${e.event_date}${e.event_start ? ' · ' + e.event_start : ''}</span>
                </div>
                ${e.venue ? `<div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <i class="mgc_location_line shrink-0"></i>
                    <span class="truncate">${e.venue}</span>
                </div>` : ''}
            </div>

            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-info bg-info/10 rounded-full px-2.5 py-1">
                    <i class="mgc_user_3_line"></i> ${e.attendance_count} attended
                </span>
                ${e.raffle_enabled ? `<span class="inline-flex items-center gap-1 text-xs font-semibold text-warning bg-warning/10 rounded-full px-2.5 py-1">
                    <i class="mgc_star_line"></i> Raffle
                </span>` : ''}
                <a href="${ROUTES.showBase}/${e.id}" class="text-xs text-primary hover:underline font-medium">View →</a>
            </div>
        </div>
    </div>`;
}

function renderPagination(total, page, limit) {
    const totalPages = Math.ceil(total / limit);
    const row = document.getElementById('event-pagination');
    if (totalPages <= 1) { row.classList.add('hidden'); return; }
    row.classList.remove('hidden');

    const from = (page - 1) * limit + 1;
    const to   = Math.min(page * limit, total);
    document.getElementById('pagination-info').textContent = `Showing ${from}–${to} of ${total}`;

    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="loadEvents(${i})"
            class="w-8 h-8 rounded-lg text-sm font-medium transition-colors ${i === page
                ? 'bg-primary text-white'
                : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50'
            }">${i}</button>`;
    }
    document.getElementById('pagination-btns').innerHTML = html;
}

function skeletonCards() {
    const c = `<div class="card overflow-hidden animate-pulse">
        <div class="w-full h-36 bg-gray-200 dark:bg-gray-700"></div>
        <div class="p-4 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 dark:bg-gray-600 rounded w-1/3"></div>
            <div class="h-3 bg-gray-100 dark:bg-gray-600 rounded w-1/2"></div>
        </div>
    </div>`;
    return Array(6).fill(c).join('');
}

function emptyState() {
    return `<div class="col-span-3 card py-16 text-center text-gray-400">
        <i class="mgc_calendar_line text-5xl mb-3 block opacity-30"></i>
        <p class="text-sm font-medium">No events found.</p>
        <a href="{{ route('events.create') }}" class="mt-3 inline-block text-xs text-primary hover:underline">+ Create your first event</a>
    </div>`;
}

window.loadEvents = loadEvents;
</script>
@endpush
