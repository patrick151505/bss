@extends('layouts.vertical', [
    'title'         => 'Barangay IDs',
    'sub_title'     => 'Barangay IDs',
    'sub_title_url' => route('citizens.ids.index'),
    'tagline'       => 'Issue and manage barangay identification cards for residents.',
])

@section('content')

{{-- Stats + Action --}}
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $ids->total() }} {{ Str::plural('record', $ids->total()) }} found
    </p>
    <div class="flex gap-2">
        <button type="button" id="create-id-btn"
                class="btn bg-primary text-white flex items-center gap-2">
            <i class="mgc_add_line"></i> Create New Barangay ID
        </button>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('citizens.ids.index') }}" class="grid grid-cols-12 gap-3 items-end">
        <div class="col-span-12 md:col-span-4">
            <label class="form-label">Search Name / QR Code</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-input" placeholder="Search…">
        </div>
        <div class="col-span-12 md:col-span-3">
            <label class="form-label">Address</label>
            <select name="address" class="form-select">
                <option value="">All Addresses</option>
                @foreach($addresses as $addr)
                <option value="{{ $addr->id }}" {{ request('address') == $addr->id ? 'selected' : '' }}>
                    {{ $addr->description }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-span-6 md:col-span-2">
            <label class="form-label">Date From</label>
            <input type="date" name="date_start" value="{{ request('date_start') }}" class="form-input">
        </div>
        <div class="col-span-6 md:col-span-2">
            <label class="form-label">Date To</label>
            <input type="date" name="date_end" value="{{ request('date_end') }}" class="form-input">
        </div>
        <div class="col-span-12 md:col-span-1 flex gap-2">
            <button type="submit" class="btn bg-dark text-white w-full">Search</button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Citizen</th>
                    <th>Address</th>
                    <th>Issued By</th>
                    <th>Date Issued</th>
                    <th>Valid Until</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ids as $record)
                @php $c = $record->citizen; @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">
                                @if($c?->profile)
                                <img src="{{ asset(str_replace('public/', 'storage/', $c->profile)) }}"
                                     class="w-full h-full object-cover" alt="">
                                @else
                                <i class="mgc_user_3_line text-primary text-sm"></i>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $c?->full_name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ \App\Models\Setting::instance()->formatCitizenId($c?->id) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $c?->addressZone?->description ?? $c?->complete_address ?? '—' }}
                    </td>
                    <td class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $record->generatedBy?->name ?? '—' }}
                    </td>
                    <td class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $record->created_at->format('M d, Y') }}
                    </td>
                    <td>
                        @php $expired = $record->valid_until->isPast(); @endphp
                        <span class="badge {{ $expired ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success' }}">
                            {{ $record->valid_until->format('M d, Y') }}
                            {{ $expired ? '(Expired)' : '' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('citizens.ids.print', $record->id) }}" target="_blank"
                           class="btn btn-sm bg-dark text-white" title="Print ID">
                            <i class="mgc_print_line"></i> Print
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-10 text-gray-400">
                        <i class="mgc_card_pay_line text-3xl block mb-2"></i>
                        No IDs issued yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ids->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
        {{ $ids->links() }}
    </div>
    @endif
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
                        {{ $i === 0 ? 'bg-gray-800 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}"
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
                    <button type="button" id="modal-search-btn" class="btn bg-dark text-white">Search</button>
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
const SEARCH_URL  = '{{ route('citizens.search') }}';
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
    document.getElementById('modal-citizen-results').innerHTML = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('modal-search').focus();
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
        t.classList.toggle('bg-gray-800', i === n);
        t.classList.toggle('text-white', i === n);
        t.classList.toggle('bg-gray-100', i !== n);
        t.classList.toggle('dark:bg-gray-700', i !== n);
        t.classList.toggle('text-gray-500', i !== n);
    });
    stepBodies.forEach(b => b.classList.toggle('hidden', parseInt(b.dataset.step) !== n));
}

document.querySelectorAll('.step-back').forEach(b => b.addEventListener('click', () => gotoStep(0)));

// ── Citizen search in modal ───────────────────────────────────────────
const resultsEl  = document.getElementById('modal-citizen-results');
const searchInput = document.getElementById('modal-search');

function doModalSearch() {
    const q = searchInput.value.trim();
    if (q.length < 2) return;
    resultsEl.innerHTML = skeletonCards(3);
    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(results => {
            if (!results.length) {
                resultsEl.innerHTML = '<p class="text-sm text-gray-400 py-3 text-center">No citizen found.</p>';
                return;
            }
            resultsEl.innerHTML = results.map(c => {
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
        });
}

document.getElementById('modal-search-btn').addEventListener('click', doModalSearch);
searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doModalSearch(); } });

// ── Pick citizen → load detail → step 1 ──────────────────────────────
function pickCitizen(id) {
    selectedCitizenId = id;
    // Show skeleton while loading
    document.getElementById('confirm-avatar').innerHTML = '<div class="w-full h-full bg-gray-200 dark:bg-gray-700 animate-pulse rounded-full"></div>';
    document.querySelectorAll('[id^="d-"], #confirm-name, #confirm-id, #confirm-address')
        .forEach(el => { el.textContent = '…'; });
    gotoStep(1);

    fetch(`${DETAIL_BASE}/${id}/detail`)
        .then(r => r.json())
        .then(c => fillConfirm(c));
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
}

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
