@extends('layouts.vertical', [
    'title'         => 'New Release',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => 'New Release',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ route('inventory.releases.store') }}" method="POST" id="release-form">
@csrf
<input type="hidden" name="citizen_id" id="citizen_id" value="{{ old('citizen_id', $selectedCitizen?->id) }}">

<div class="grid grid-cols-12 gap-6">

    {{-- Left --}}
    <div class="col-span-12 lg:col-span-7 flex flex-col gap-5">

        {{-- Citizen Lookup --}}
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                Find Citizen
            </h6>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="mgc_search_line text-gray-400"></i>
                </div>
                <input type="text" id="citizen-search" class="form-input pl-9"
                       placeholder="Search citizen name or scan ID…" autocomplete="off"
                       value="{{ $selectedCitizen ? $selectedCitizen->full_name : '' }}">
            </div>
            <div id="citizen-dropdown"
                 class="hidden mt-1 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-lg bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
            </div>
            <div id="citizen-card" class="{{ $selectedCitizen ? '' : 'hidden' }} mt-3 p-3 rounded-lg border border-success/40 bg-success/5 flex items-start gap-3">
                <div id="card-avatar" class="w-10 h-10 rounded-full bg-success/20 flex items-center justify-center shrink-0 overflow-hidden">
                    <i class="mgc_user_3_line text-success"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="card-name">{{ $selectedCitizen?->full_name }}</p>
                    <p class="text-xs text-gray-500" id="card-address">{{ $selectedCitizen?->complete_address }}</p>
                    <p class="text-xs text-gray-500" id="card-contact">{{ $selectedCitizen?->contact ? '📞 '.$selectedCitizen->contact : '' }}</p>
                </div>
                <button type="button" onclick="clearCitizen()" class="text-gray-400 hover:text-danger">
                    <i class="mgc_close_line"></i>
                </button>
            </div>
            @if($recentCitizens->isNotEmpty())
            <div id="recent-grid" class="mt-4 {{ $selectedCitizen ? 'hidden' : '' }}">
                <p class="text-xs text-gray-400 font-medium mb-2"><i class="mgc_history_line"></i> Recent</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($recentCitizens as $rc)
                    @php
                        $rcProfile = $rc->profile ? asset(str_replace('public/', 'storage/', $rc->profile)) : null;
                    @endphp
                    <button type="button"
                            onclick="selectCitizen({{ $rc->id }}, '{{ addslashes($rc->full_name) }}', '{{ addslashes($rc->complete_address ?? '') }}', '{{ addslashes($rc->contact ?? '') }}', '{{ $rcProfile }}')"
                            class="recent-citizen-btn flex items-center gap-2.5 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5 transition text-left w-full group">
                        <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">
                            @if($rcProfile)
                                <img src="{{ $rcProfile }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-sm font-bold text-primary">{{ strtoupper(substr($rc->fname ?? '?', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-primary">{{ $rc->full_name }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ $rc->complete_address ?? '—' }}</p>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Items selection --}}
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                Select Items
            </h6>

            <div class="mb-3 relative">
                <input type="text" id="item-search" placeholder="Search item…" class="form-input pl-9 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="mgc_search_line text-gray-400"></i>
                </div>
            </div>

            <div id="item-list" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                @foreach($items as $itm)
                <div class="item-row flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/40 hover:bg-primary/5 transition cursor-pointer"
                     data-name="{{ strtolower($itm->name) }}"
                     data-id="{{ $itm->id }}"
                     data-unit="{{ $itm->unit }}"
                     data-stock="{{ $itm->stock }}"
                     onclick="addItem(this)">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $itm->name }}</p>
                        <p class="text-xs text-gray-400">{{ $itm->category->name ?? '' }} · {{ number_format($itm->stock) }} {{ $itm->unit }} available</p>
                    </div>
                    @if($itm->stock == 0)
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800 shrink-0">
                            <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Out of stock
                        </span>
                    @elseif($itm->isLowStock())
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 shrink-0">
                            <span class="w-1.5 h-1.5 inline-block bg-yellow-400 rounded-full"></span>Low
                        </span>
                    @endif
                    <i class="mgc_add_circle_line text-primary text-xl shrink-0"></i>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Right --}}
    <div class="col-span-12 lg:col-span-5 flex flex-col gap-5">

        {{-- Selected items cart --}}
        <div class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-full bg-success text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
                Release List
            </h6>
            <div id="cart-empty" class="text-center py-8 text-gray-400 text-sm">
                <i class="mgc_shopping_bag_line text-3xl mb-2 block opacity-30"></i>
                Click items on the left to add them here.
            </div>
            <div id="cart-items" class="space-y-2"></div>
            <div id="cart-summary" class="hidden mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm">
                <span class="text-gray-500"><span id="cart-count" class="font-semibold text-gray-800 dark:text-gray-100"></span> item type(s)</span>
                <span class="text-gray-500">Total qty: <span id="cart-total-qty" class="font-semibold text-gray-800 dark:text-gray-100"></span></span>
            </div>
        </div>

        {{-- Purpose --}}
        <div class="card p-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Purpose / Remarks</label>
            <textarea name="purpose" rows="3" class="form-input w-full" placeholder="Optional: reason for this release">{{ old('purpose') }}</textarea>
        </div>

        <button type="submit" class="btn bg-primary text-white w-full gap-2">
            <i class="mgc_send_line"></i> Submit Release Request
        </button>
    </div>
</div>
</form>

@endsection

@push('inline-scripts')
<script>
const SEARCH_URL = '{{ route('citizens.search') }}';
let searchTimer = null;

const searchInput = document.getElementById('citizen-search');
const dropdown    = document.getElementById('citizen-dropdown');

searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) { dropdown.classList.add('hidden'); return; }
    searchTimer = setTimeout(() => {
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(results => {
                if (!results.length) {
                    dropdown.innerHTML = '<p class="px-4 py-3 text-sm text-gray-400">No citizen found.</p>';
                } else {
                    dropdown.innerHTML = results.map(c => `
                        <div class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                             onclick="selectCitizen(${c.id}, '${escStr(c.name)}', '${escStr(c.address)}', '${escStr(c.contact)}', '${escStr(c.profile ?? '')}')">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0 overflow-hidden">
                                ${c.profile ? `<img src="${c.profile}" class="w-full h-full object-cover">` : '<i class="mgc_user_3_line text-primary text-sm"></i>'}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${c.name}</p>
                                <p class="text-xs text-gray-400 truncate">${c.address}</p>
                            </div>
                        </div>
                    `).join('');
                }
                dropdown.classList.remove('hidden');
            });
    }, 200);
});

searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); dropdown.querySelector('[onclick]')?.click(); }
});

document.addEventListener('click', function (e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('hidden');
});

function escStr(s) { return (s || '').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

function selectCitizen(id, name, address, contact, profileUrl) {
    document.getElementById('citizen_id').value = id;
    searchInput.value = name;
    dropdown.classList.add('hidden');
    document.getElementById('card-name').textContent    = name;
    document.getElementById('card-address').textContent = address || '—';
    document.getElementById('card-contact').textContent = contact ? ('📞 ' + contact) : '';
    const avatar = document.getElementById('card-avatar');
    avatar.innerHTML = profileUrl
        ? `<img src="${profileUrl}" class="w-full h-full object-cover">`
        : '<i class="mgc_user_3_line text-success"></i>';
    document.getElementById('citizen-card').classList.remove('hidden');
    const grid = document.getElementById('recent-grid');
    if (grid) grid.classList.add('hidden');
}

function clearCitizen() {
    document.getElementById('citizen_id').value = '';
    searchInput.value = '';
    document.getElementById('citizen-card').classList.add('hidden');
    const grid = document.getElementById('recent-grid');
    if (grid) grid.classList.remove('hidden');
}

// ── Item search filter ────────────────────────────────────────────────
document.getElementById('item-search').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.item-row').forEach(row => {
        row.classList.toggle('hidden', q.length > 0 && !row.dataset.name.includes(q));
    });
});

// ── Cart ──────────────────────────────────────────────────────────────
const cart = {};

function addItem(el) {
    const id    = el.dataset.id;
    const name  = el.querySelector('p').textContent.trim();
    const unit  = el.dataset.unit;
    const stock = parseInt(el.dataset.stock, 10);
    if (stock === 0) {
        Swal.fire({ title: 'Out of Stock', text: 'This item has no available stock.', icon: 'error', confirmButtonColor: '#4361ee' });
        return;
    }

    if (cart[id]) {
        // just focus qty
        document.getElementById('cart-qty-' + id).focus();
        return;
    }
    cart[id] = { name, unit, stock };
    renderCart();
}

function removeItem(id) {
    delete cart[id];
    renderCart();
}

function renderCart() {
    const keys = Object.keys(cart);
    document.getElementById('cart-empty').classList.toggle('hidden', keys.length > 0);
    const summary = document.getElementById('cart-summary');
    summary.classList.toggle('hidden', keys.length === 0);

    const container = document.getElementById('cart-items');
    container.innerHTML = keys.map(id => `
        <div class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${cart[id].name}</p>
                <p class="text-xs text-gray-400">max ${cart[id].stock} ${cart[id].unit}</p>
            </div>
            <input type="number" name="items[${id}][qty]" id="cart-qty-${id}"
                   value="1" min="1" max="${cart[id].stock}"
                   class="form-input w-20 text-center text-sm"
                   oninput="updateCartTotals()">
            <input type="hidden" name="items[${id}][id]" value="${id}">
            <span class="text-xs text-gray-400">${cart[id].unit}</span>
            <button type="button" onclick="removeItem('${id}')" class="text-gray-300 hover:text-danger">
                <i class="mgc_close_line"></i>
            </button>
        </div>
    `).join('');

    updateCartTotals();
}

function updateCartTotals() {
    const keys = Object.keys(cart);
    let totalQty = 0;
    keys.forEach(id => {
        const input = document.getElementById('cart-qty-' + id);
        totalQty += input ? parseInt(input.value, 10) || 0 : 1;
    });
    document.getElementById('cart-count').textContent = keys.length;
    document.getElementById('cart-total-qty').textContent = totalQty;
}
</script>
@endpush
