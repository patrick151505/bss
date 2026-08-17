@extends('layouts.vertical', [
    'title'         => 'Barangay IDs',
    'sub_title'     => 'Barangay IDs',
    'sub_title_url' => route('citizens.ids.index'),
    'tagline'       => 'Issue and manage barangay identification cards for residents.',
])

@section('content')

{{-- Stat Strip — IDs created per period --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
            <i class="mgc_calendar_day_line text-primary text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Today</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100" data-stat="today">{{ number_format($stats['today']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center shrink-0">
            <i class="mgc_calendar_week_line text-info text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">This Week</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100" data-stat="week">{{ number_format($stats['week']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
            <i class="mgc_calendar_month_line text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">This Month</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100" data-stat="month">{{ number_format($stats['month']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
            <i class="mgc_card_pay_line text-success text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Created</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100" data-stat="total">{{ number_format($stats['total']) }}</p>
        </div>
    </div>
</div>

@php $range = request('range', request('date_start') || request('date_end') ? 'custom' : ''); @endphp

{{-- Filter + Actions + Table --}}
<div class="card mb-5">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('citizens.ids.index') }}" id="ids-filter-form"
              class="flex flex-wrap items-end gap-3 flex-1">
            <div class="flex-1 min-w-[160px]">
                <label class="form-label text-xs mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name or QR code…" class="form-input text-sm py-1.5">
            </div>
            <div class="w-44">
                <label class="form-label text-xs mb-1">Address</label>
                <select name="address" class="form-select text-sm py-1.5">
                    <option value="">All Addresses</option>
                    @foreach($addresses as $addr)
                    <option value="{{ $addr->id }}" {{ request('address') == $addr->id ? 'selected' : '' }}>{{ $addr->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="form-label text-xs mb-1">Date Issued</label>
                <select name="range" id="range-select" class="form-select text-sm py-1.5" onchange="toggleCustomRange()">
                    <option value=""      {{ $range === ''      ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week"  {{ $range === 'week'  ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $range === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom"{{ $range === 'custom'? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            <div id="custom-range" class="flex items-end gap-2 {{ $range === 'custom' ? '' : 'hidden' }}">
                <div class="w-36">
                    <label class="form-label text-xs mb-1">From</label>
                    <input type="date" name="date_start" value="{{ request('date_start') }}" class="form-input text-sm py-1.5">
                </div>
                <div class="w-36">
                    <label class="form-label text-xs mb-1">To</label>
                    <input type="date" name="date_end" value="{{ request('date_end') }}" class="form-input text-sm py-1.5">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn bg-primary text-white text-sm py-1.5 px-4">
                    <i class="mgc_search_line me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search','address','range','date_start','date_end']))
                <a href="{{ route('citizens.ids.index') }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3">Clear</a>
                @endif
            </div>
        </form>
        <div class="flex items-center gap-2 shrink-0">
            {{-- View toggle (list / grid) --}}
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button type="button" id="view-list-btn" onclick="setIdsView('list')"
                        class="px-2.5 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800" title="List view">
                    <i class="mgc_list_check_line"></i>
                </button>
                <button type="button" id="view-grid-btn" onclick="setIdsView('grid')"
                        class="px-2.5 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 border-l border-gray-200 dark:border-gray-700" title="Grid view">
                    <i class="mgc_grid_line"></i>
                </button>
            </div>
            <button type="button" id="create-id-btn" class="btn bg-success text-white flex items-center gap-2">
                <i class="mgc_add_line"></i> New Barangay ID
            </button>
        </div>
    </div>

    {{-- ── Results (list + grid + footer) — swapped in via AJAX ── --}}
    <div id="ids-results" class="relative">
        {{-- Loading overlay shown during AJAX fetches --}}
        <div id="ids-loading" class="hidden absolute inset-0 z-10 bg-white/60 dark:bg-gray-900/60 flex items-start justify-center pt-16">
            <i class="mgc_loading_3_line animate-spin text-primary text-2xl"></i>
        </div>
        @include('citizens.ids._results')
    </div>
</div>

{{-- ── 3-Step Modal ── --}}
<div id="id-modal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 flex flex-col max-h-[90vh]">

        {{-- Close --}}
        <button type="button" id="close-modal-btn"
                class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-danger text-white flex items-center justify-center z-10 hover:bg-danger/80 transition">
            <i class="mgc_close_line text-sm"></i>
        </button>

        {{-- Step indicators --}}
        <div class="flex rounded-t-2xl overflow-hidden shrink-0">
            @foreach([['mgc_list_check_2_line','Choose Citizen','Pick an active citizen'],
                      ['mgc_user_3_line','Confirm Details','Review information'],
                      ['mgc_check_circle_line','Generate','Save and print ID']] as $i => [$icon, $title, $sub])
            <div class="step-tab flex-1 flex items-center justify-center gap-2 py-4 text-sm font-medium transition
                        {{ $i === 0 ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}"
                 data-step="{{ $i }}">
                <i class="{{ $icon }}"></i>
                <span class="hidden sm:block">{{ $title }}</span>
            </div>
            @endforeach
        </div>

        {{-- Step bodies --}}
        <div class="overflow-y-auto flex-1 p-5">

            {{-- Step 0: Pick citizen --}}
            <div class="step-body" data-step="0">
                <div class="flex gap-2 mb-3">
                    <input type="text" id="modal-search" class="form-input flex-1" placeholder="Search name or QR code…">
                    <button type="button" id="modal-search-btn" class="btn bg-primary text-white">Search</button>
                </div>
                <div id="modal-citizen-results" class="space-y-1 max-h-64 overflow-y-auto"></div>
            </div>

            {{-- Step 1: Confirm details --}}
            <div class="step-body hidden" data-step="1">
                <div class="flex items-center gap-4 mb-5">
                    <div id="confirm-avatar"
                         class="w-20 h-20 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center text-2xl text-primary font-bold border-4 border-primary/20">
                    </div>
                    <div>
                        <p id="confirm-name" class="text-lg font-bold text-gray-800 dark:text-gray-100"></p>
                        <p id="confirm-id" class="text-xs text-gray-400"></p>
                        <p id="confirm-address" class="text-sm text-gray-500 mt-0.5"></p>
                    </div>
                </div>
                <table class="w-full text-sm border border-gray-100 dark:border-gray-700 rounded-lg overflow-hidden">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr class="bg-primary text-white"><td colspan="2" class="px-4 py-2 font-semibold text-xs uppercase tracking-wide">Personal Information</td></tr>
                        <tr><th class="px-4 py-2 text-left w-2/5 text-gray-500 font-medium">Full Name</th><td id="d-fullname" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Date of Birth</th><td id="d-bday" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Gender</th><td id="d-gender" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Contact</th><td id="d-contact" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Address</th><td id="d-address" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Member Since</th><td id="d-since" class="px-4 py-2"></td></tr>
                        <tr class="bg-primary text-white"><td colspan="2" class="px-4 py-2 font-semibold text-xs uppercase tracking-wide">Emergency Contact</td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Name</th><td id="d-ic-name" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Contact / Relation</th><td id="d-ic-contact" class="px-4 py-2"></td></tr>
                        <tr><th class="px-4 py-2 text-left text-gray-500 font-medium">Address</th><td id="d-ic-address" class="px-4 py-2"></td></tr>
                    </tbody>
                </table>
                {{-- Validity period — set barangay-wide in Settings --}}
                @php $set = \App\Models\Setting::instance(); @endphp
                <div class="mt-5 flex items-center gap-3 p-3 rounded-lg bg-primary/5 border border-primary/20">
                    <i class="mgc_time_line text-primary text-xl shrink-0"></i>
                    <div class="text-sm">
                        <p class="text-gray-700 dark:text-gray-200">
                            Valid for <span class="font-semibold text-primary">{{ $set->idValidityLabel() }}</span>
                            — expires <span class="font-medium">{{ $set->idValidUntil()->format('M d, Y') }}</span>
                        </p>
                        <p class="text-xs text-gray-400">
                            @can('settings.edit')
                                Change the default in <a href="{{ route('settings.index') }}" class="text-primary hover:underline">Settings → Citizen ID Format</a>.
                            @else
                                Set by the barangay administrator.
                            @endcan
                        </p>
                    </div>
                </div>

                <div class="flex gap-3 mt-4">
                    <button type="button" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 step-back">Back</button>
                    <button type="button" id="generate-btn"
                            class="btn bg-primary text-white flex-1 flex items-center justify-center gap-2">
                        <i class="mgc_print_line"></i> Generate & Print ID
                    </button>
                </div>
            </div>

            {{-- Step 2: Done --}}
            <div class="step-body hidden" data-step="2">
                <div class="text-center py-8">
                    <div class="w-16 h-16 rounded-full bg-success/10 text-success flex items-center justify-center text-3xl mx-auto mb-4">
                        <i class="mgc_check_circle_line"></i>
                    </div>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">ID Generated!</p>
                    <p class="text-sm text-gray-500 mb-5">The print page will open in a new tab.</p>
                    <div class="flex gap-3 justify-center">
                        <a id="print-link" href="#" target="_blank"
                           class="btn bg-dark text-white flex items-center gap-2">
                            <i class="mgc_print_line"></i> Print Again
                        </a>
                        <button type="button" id="done-btn" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Show the custom date pickers only when "Custom Range" is selected.
function toggleCustomRange() {
    const isCustom = document.getElementById('range-select').value === 'custom';
    document.getElementById('custom-range').classList.toggle('hidden', !isCustom);
}

// ── AJAX filtering ────────────────────────────────────────────────────
// Live search + filter changes fetch just the results partial and swap it,
// keeping the URL in sync so the page stays shareable / back-button friendly.
// The plain form still works with JS disabled (it does a normal GET).
const filterForm = document.getElementById('ids-filter-form');
const idsResultsEl = document.getElementById('ids-results');
let   _ajaxCtl   = null;
let   _searchTimer = null;
function idsLoading() { return document.getElementById('ids-loading'); }

function currentView() { return localStorage.getItem('idsView') || 'list'; }

// Build the query string from the filter form + view + page size + page.
function buildQuery(extra = {}) {
    const p = new URLSearchParams(new FormData(filterForm));
    // Drop empty params so the URL stays clean.
    for (const [k, v] of [...p]) { if (v === '') p.delete(k); }
    p.set('view', currentView());
    const cur = new URL(window.location.href).searchParams;
    if (cur.get('show')) p.set('show', cur.get('show'));
    Object.entries(extra).forEach(([k, v]) => v == null ? p.delete(k) : p.set(k, v));
    return p;
}

function loadResults(extra = {}, pushUrl = true) {
    const params = buildQuery(extra);

    // Sync the browser URL (shareable + back button).
    if (pushUrl) {
        const url = new URL(window.location.href);
        url.search = params.toString();
        history.pushState({ ids: true }, '', url);
    }

    idsLoading()?.classList.remove('hidden');
    if (_ajaxCtl) _ajaxCtl.abort();
    _ajaxCtl = new AbortController();

    fetch('{{ route('citizens.ids.index') }}?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: _ajaxCtl.signal,
    })
    .then(r => r.json())
    .then(res => {
        idsResultsEl.innerHTML =
            '<div id="ids-loading" class="hidden absolute inset-0 z-10 bg-white/60 dark:bg-gray-900/60 flex items-start justify-center pt-16">'
            + '<i class="mgc_loading_3_line animate-spin text-primary text-2xl"></i></div>' + res.html;
        // Update stat cards.
        if (res.stats) {
            Object.entries(res.stats).forEach(([k, v]) => {
                const el = document.querySelector('[data-stat="' + k + '"]');
                if (el) el.textContent = Number(v).toLocaleString();
            });
        }
        applyViewToggle(currentView());
    })
    .catch(err => { if (err.name !== 'AbortError') idsLoading()?.classList.add('hidden'); });
}

// Change page size via AJAX (resets to page 1).
function changePerPage(n) {
    const url = new URL(window.location.href);
    url.searchParams.set('show', n);
    url.searchParams.delete('page');
    history.replaceState({ ids: true }, '', url);   // remember the size
    loadResults({ page: null });
}

// List / grid view toggle. Just show/hide — both are always in the partial.
function applyViewToggle(view) {
    const list = document.getElementById('ids-list-view');
    const grid = document.getElementById('ids-grid-view');
    const lb   = document.getElementById('view-list-btn');
    const gb   = document.getElementById('view-grid-btn');
    const isGrid = view === 'grid';
    if (list) list.classList.toggle('hidden', isGrid);
    if (grid) grid.classList.toggle('hidden', !isGrid);
    [[lb, !isGrid], [gb, isGrid]].forEach(([btn, active]) => {
        if (!btn) return;
        btn.classList.toggle('bg-primary', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-gray-500', !active);
    });
}
function setIdsView(view) {
    localStorage.setItem('idsView', view);
    applyViewToggle(view);
}

// Intercept pagination links inside the swapped results (event delegation).
idsResultsEl.addEventListener('click', (e) => {
    const link = e.target.closest('#ids-results .pagination a, #ids-results nav a');
    if (!link || !link.href) return;
    e.preventDefault();
    const page = new URL(link.href).searchParams.get('page');
    loadResults({ page });
    idsResultsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});

// Filter form: live search (debounced) + instant change for selects/dates.
filterForm.addEventListener('input', (e) => {
    if (e.target.name === 'search') {
        clearTimeout(_searchTimer);
        _searchTimer = setTimeout(() => loadResults({ page: null }), 300);
    }
});
filterForm.addEventListener('change', (e) => {
    if (['address', 'range', 'date_start', 'date_end'].includes(e.target.name)) {
        loadResults({ page: null });
    }
});
// Prevent the Enter/Filter button from doing a full reload — filter via AJAX.
filterForm.addEventListener('submit', (e) => { e.preventDefault(); loadResults({ page: null }); });

// Back/forward button → reload results for that URL state.
window.addEventListener('popstate', () => loadResults({}, false));

// Restore saved view on first load (no fetch needed — partial already rendered).
applyViewToggle(currentView());

const SEARCH_URL  = '{{ route('citizens.search') }}';
const RECENT_URL  = '{{ route('citizens.recent') }}';
const DETAIL_BASE = '{{ url('citizens') }}';
const STORE_URL   = '{{ route('citizens.ids.store') }}';
const PRINT_BASE  = '{{ url('citizens/ids') }}';
const CSRF        = '{{ csrf_token() }}';

let selectedCitizenId = null;

// ── Modal open/close ──────────────────────────────────────────────────
const modal     = document.getElementById('id-modal');
const openBtn   = document.getElementById('create-id-btn');
const closeBtn  = document.getElementById('close-modal-btn');
const doneBtn   = document.getElementById('done-btn');

function openModal() {
    selectedCitizenId = null;
    gotoStep(0);
    document.getElementById('modal-search').value = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('modal-search').focus();
    loadRecentCitizens();   // pre-pick: show 5 most recently updated
}
function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

openBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);
doneBtn.addEventListener('click', () => { closeModal(); location.reload(); });
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

// ── Steps ─────────────────────────────────────────────────────────────
const stepTabs   = document.querySelectorAll('.step-tab');
const stepBodies = document.querySelectorAll('.step-body');

function gotoStep(n) {
    stepTabs.forEach((t, i) => {
        const done = i < n;   // completed steps get a softer primary tint
        t.classList.toggle('bg-primary', i === n);
        t.classList.toggle('text-white', i === n);
        t.classList.toggle('bg-primary/20', done);
        t.classList.toggle('text-primary', done);
        t.classList.toggle('bg-gray-100', i > n);
        t.classList.toggle('dark:bg-gray-700', i > n);
        t.classList.toggle('text-gray-500', i > n);
    });
    stepBodies.forEach(b => b.classList.toggle('hidden', parseInt(b.dataset.step) !== n));
}

document.querySelectorAll('.step-back').forEach(b => b.addEventListener('click', () => gotoStep(0)));

// ── Citizen search in modal ───────────────────────────────────────────
const resultsEl  = document.getElementById('modal-citizen-results');
const searchInput = document.getElementById('modal-search');

// Render a list of citizen result cards. `heading` is an optional label row.
function renderCitizenResults(results, heading) {
    if (!results.length) {
        resultsEl.innerHTML = '<p class="text-sm text-gray-400 py-3 text-center">No citizen found.</p>';
        return;
    }
    const cards = results.map(c => {
        const initial = (c.name.trim()[0] ?? '?').toUpperCase();
        const avatar  = c.profile
            ? `<img src="${esc(c.profile)}" class="w-full h-full object-cover">`
            : `<span class="text-sm font-bold text-primary">${initial}</span>`;
        return `
        <button type="button" onclick="pickCitizen(${c.id})"
                class="flex items-center gap-3 w-full p-3 rounded-lg border border-gray-200 dark:border-gray-700
                       hover:border-primary/50 hover:bg-primary/5 transition text-left group">
            <div class="w-10 h-10 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">${avatar}</div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-primary">${esc(c.name)}</p>
                <p class="text-xs text-gray-400 truncate">${esc(c.address) || '—'}</p>
            </div>
            <i class="mgc_arrow_right_line text-gray-300 shrink-0"></i>
        </button>`;
    }).join('');
    const label = heading
        ? `<p class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold px-1 pb-1">${heading}</p>`
        : '';
    resultsEl.innerHTML = label + cards;
}

// Pre-load the 5 most recently updated citizens when the picker opens.
function loadRecentCitizens() {
    resultsEl.innerHTML = skeletonCards(5);
    fetch(RECENT_URL + '?limit=5')
        .then(r => r.json())
        .then(results => renderCitizenResults(results, 'Recently updated'))
        .catch(() => { resultsEl.innerHTML = ''; });
}

function doModalSearch() {
    const q = searchInput.value.trim();
    if (q.length < 2) { loadRecentCitizens(); return; }   // empty/short search → recents
    resultsEl.innerHTML = skeletonCards(3);
    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(results => renderCitizenResults(results));
}

document.getElementById('modal-search-btn').addEventListener('click', doModalSearch);
searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doModalSearch(); } });

// ── Pick citizen → load detail → step 1 ──────────────────────────────
// A single shimmer bar; `w` is a Tailwind width class.
function skelBar(w = 'w-24') {
    return `<span class="inline-block h-3 ${w} rounded bg-gray-200 dark:bg-gray-700 animate-pulse align-middle"></span>`;
}

function showConfirmSkeleton() {
    // Avatar shimmer
    document.getElementById('confirm-avatar').innerHTML =
        '<div class="w-full h-full bg-gray-200 dark:bg-gray-700 animate-pulse rounded-full"></div>';
    // Header shimmer bars
    document.getElementById('confirm-name').innerHTML    = skelBar('w-40');
    document.getElementById('confirm-id').innerHTML      = skelBar('w-24');
    document.getElementById('confirm-address').innerHTML = skelBar('w-56');
    // Table cell shimmer bars — varied widths so it reads as content
    const widths = {
        'd-fullname':'w-40','d-bday':'w-24','d-gender':'w-16','d-contact':'w-28',
        'd-address':'w-56','d-since':'w-20','d-ic-name':'w-36','d-ic-contact':'w-44','d-ic-address':'w-52',
    };
    Object.entries(widths).forEach(([id, w]) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = skelBar(w);
    });
    // Disable Generate while loading
    const gen = document.getElementById('generate-btn');
    gen.disabled = true; gen.classList.add('opacity-60', 'pointer-events-none');
}

function pickCitizen(id) {
    selectedCitizenId = id;
    showConfirmSkeleton();
    gotoStep(1);

    fetch(`${DETAIL_BASE}/${id}/detail`)
        .then(r => r.json())
        .then(c => fillConfirm(c))
        .catch(() => {
            document.getElementById('confirm-name').textContent = 'Failed to load citizen. Go back and try again.';
        });
}

function fillConfirm(c) {
    const avatarEl = document.getElementById('confirm-avatar');
    if (c.profile) {
        avatarEl.innerHTML = `<img src="${esc(c.profile)}" class="w-full h-full object-cover">`;
    } else {
        avatarEl.textContent = (c.full_name?.trim()[0] ?? '?').toUpperCase();
    }
    document.getElementById('confirm-name').textContent    = c.full_name;
    document.getElementById('confirm-id').textContent      = 'QR: ' + (c.qrcode || '—');
    document.getElementById('confirm-address').textContent = c.address || '—';
    document.getElementById('d-fullname').textContent      = c.full_name;
    document.getElementById('d-bday').textContent          = c.bday || '—';
    document.getElementById('d-gender').textContent        = c.gender || '—';
    document.getElementById('d-contact').textContent       = c.contact || '—';
    document.getElementById('d-address').textContent       = c.address || '—';
    document.getElementById('d-since').textContent         = c.year_stay || '—';
    document.getElementById('d-ic-name').textContent       = c.ic_fullname || '—';
    document.getElementById('d-ic-contact').textContent    = (c.ic_contact || '—') + ' / ' + (c.ic_relationship || '—');
    document.getElementById('d-ic-address').textContent    = c.ic_address || '—';

    // Re-enable Generate now that data is loaded
    const gen = document.getElementById('generate-btn');
    gen.disabled = false; gen.classList.remove('opacity-60', 'pointer-events-none');
}

// Validity is set barangay-wide in Settings; the server applies it on generate.

// ── Generate ID ───────────────────────────────────────────────────────
document.getElementById('generate-btn').addEventListener('click', () => {
    if (!selectedCitizenId) return;
    const btn = document.getElementById('generate-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Generating…';

    fetch(STORE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ citizen_id: selectedCitizenId }),
    })
    .then(r => r.json())
    .then(data => {
        const url = PRINT_BASE + '/' + data.id + '/print';
        document.getElementById('print-link').href = url;
        window.open(url, '_blank');
        gotoStep(2);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_print_line"></i> Generate & Print ID';
    });
});

// ── Helpers ───────────────────────────────────────────────────────────
function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function skeletonCards(n) {
    const card = `<div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 animate-pulse">
        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0"></div>
        <div class="flex-1 space-y-2"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div><div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div>
    </div>`;
    return card.repeat(n);
}
</script>
@endsection
