@extends('layouts.vertical', [
    'title'         => 'Schedule',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.items.index'),
    'tagline'       => 'Reserve items such as the ambulance or the hall by date and time',
])

@section('content')

<style>
    /* FullCalendar inside the Konrix theme */
    #calendar { --fc-border-color: #e5e7eb; --fc-today-bg-color: rgba(48, 115, 241, .08); --fc-page-bg-color: transparent;
                --fc-button-bg-color: #3073F1; --fc-button-border-color: #3073F1; --fc-button-hover-bg-color: #255fd0;
                --fc-button-hover-border-color: #255fd0; --fc-button-active-bg-color: #1f4fb0; --fc-button-active-border-color: #1f4fb0; }
    [data-mode="dark"] #calendar { --fc-border-color: #374151; --fc-neutral-bg-color: rgba(255,255,255,.04);
                --fc-list-event-hover-bg-color: rgba(255,255,255,.06); --fc-today-bg-color: rgba(48, 115, 241, .16); color: #e5e7eb; }
    /* app.css resets every button background to transparent — put the calendar's back */
    #calendar .fc-button-primary { background-color: var(--fc-button-bg-color) !important; color: #fff; }
    #calendar .fc-button-primary:hover { background-color: var(--fc-button-hover-bg-color) !important; }
    #calendar .fc-button-primary:not(:disabled).fc-button-active,
    #calendar .fc-button-primary:not(:disabled):active { background-color: var(--fc-button-active-bg-color) !important; }
    #calendar .fc-toolbar-title { font-size: 1.15rem; font-weight: 600; }
    #calendar .fc-day-past { opacity: .55; }
    #calendar .fc-daygrid-day.fc-day-future, #calendar .fc-daygrid-day.fc-day-today { cursor: {{ auth()->user()->can('inventory_schedule.create') ? 'pointer' : 'default' }}; }
    #calendar .fc-event { cursor: pointer; }
    /* List view rows carry .fc-event, which forces white text — invisible on the light list background */
    #calendar .fc-list-event, #calendar .fc-list-event a { color: #1f2937; }
    #calendar .fc-list-event-time { color: #6b7280; }
    #calendar .fc-list-event td { text-align: left; }
    #calendar .fc-list-event-title { width: 100%; font-weight: 500; }
    #calendar .fc-list-event:hover td { background-color: rgba(48, 115, 241, .06); }
    [data-mode="dark"] #calendar .fc-list-event, [data-mode="dark"] #calendar .fc-list-event a { color: #e5e7eb; }
    [data-mode="dark"] #calendar .fc-list-event-time { color: #9ca3af; }
    [data-mode="dark"] #calendar .fc-list-day-cushion { background-color: #1f2937; }
    #calendar .fc-daygrid-block-event .fc-event-main { padding: 1px 4px; font-size: .75rem; }
    #calendar .fc-daygrid-block-event .fc-event-time { font-weight: 600; flex-shrink: 0; overflow: visible; }
    #calendar .fc-daygrid-block-event .fc-event-main-frame { gap: 4px; }
    #calendar .sched-completed { opacity: .55; }
    #calendar .sched-cancelled { opacity: .45; text-decoration: line-through; }
    #calendar .fc-bg-event.sched-buffer { opacity: .13; background-image: repeating-linear-gradient(45deg, rgba(255,255,255,.6) 0 6px, transparent 6px 12px); }
    #calendar .fc-bg-event.sched-buffer .fc-event-title { display: none; }
    .sc-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .75rem; }
    .sc-grid > * { grid-column: span 12 / span 12; }
    @media (min-width: 640px) { .sc-grid > .sc-6 { grid-column: span 6 / span 6; } }
</style>

@if($items->isEmpty())
    <div class="card p-6 text-center">
        <i class="mgc_calendar_line text-5xl text-gray-300 dark:text-gray-600 block mb-2"></i>
        <p class="font-semibold text-gray-800 dark:text-gray-100">No items can be scheduled yet</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Edit an item such as the ambulance or the hall and tick <strong>Can be scheduled</strong>. It then appears here and can be reserved by date.</p>
        @can('inventory_items.edit')
            <a href="{{ route('inventory.items.index') }}" class="btn bg-primary text-white mt-4"><i class="mgc_box_3_line"></i> Go to Items</a>
        @endcan
    </div>
@else

{{-- Item filter + legend --}}
<div class="card p-4 mb-4">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-gray-500 dark:text-gray-400 me-1">Show:</span>
        <button type="button" class="sc-filter btn btn-sm bg-primary text-white" data-item="">All items</button>
        @foreach($items as $i)
            <button type="button" class="sc-filter btn btn-sm bg-dark/10 text-slate-700 dark:text-slate-200 hover:bg-dark/20 gap-1.5" data-item="{{ $i['id'] }}">
                <span class="w-2.5 h-2.5 rounded-full inline-block" style="background: {{ $i['color'] }}"></span>{{ $i['name'] }}
            </button>
        @endforeach
        <label class="ms-auto flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
            <input type="checkbox" id="sc-show-cancelled" class="form-checkbox"> Show cancelled
        </label>
    </div>
    @can('inventory_schedule.create')
        <p class="text-xs text-gray-400 mt-2"><i class="mgc_information_line"></i> Click a date to reserve — or drag across several days. In Week view, drag over the hours. Striped days are the buffer kept free after a reservation.</p>
    @endcan
</div>

<div class="card p-4">
    <div id="calendar"></div>
</div>

{{-- New / edit reservation --}}
<div id="sc-form-modal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="sc-form-title">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-2xl flex flex-col" style="max-height: 92vh;">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 id="sc-form-title" class="font-bold text-gray-800 dark:text-white">New reservation</h3>
            <button type="button" onclick="closeModal('sc-form-modal')" aria-label="Close"><i class="mgc_close_line text-xl text-gray-500 dark:text-gray-400"></i></button>
        </div>
        <div class="p-4 overflow-y-auto">
            <div class="sc-grid">
                <div class="sc-6" id="sc-item-wrap">
                    <label class="form-label" for="sc-item">Item <span class="text-danger">*</span></label>
                    <select id="sc-item" class="form-select w-full">
                        @foreach($items as $i)
                            <option value="{{ $i['id'] }}" data-stock="{{ $i['stock'] }}" data-unit="{{ $i['unit'] }}" data-buffer="{{ $i['buffer'] }}">{{ $i['name'] }} ({{ $i['stock'] }} {{ $i['unit'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sc-6" id="sc-qty-wrap">
                    <label class="form-label" for="sc-qty">Quantity <span class="text-danger">*</span></label>
                    <input type="number" id="sc-qty" class="form-input w-full" min="1" value="1">
                </div>
                <div class="sc-6">
                    <label class="form-label" for="sc-start">Start <span class="text-danger">*</span></label>
                    <input type="datetime-local" id="sc-start" class="form-input w-full">
                </div>
                <div class="sc-6">
                    <label class="form-label" for="sc-end">End <span class="text-danger">*</span></label>
                    <input type="datetime-local" id="sc-end" class="form-input w-full">
                </div>
                <div id="sc-availability" class="hidden p-3 rounded-lg text-sm"></div>

                {{-- Who it's for --}}
                <div>
                    <label class="form-label" for="sc-citizen-search">For (citizen)</label>
                    <div id="sc-citizen-chip" class="hidden items-center gap-2 p-2 rounded-lg border border-success/40 bg-success/5">
                        <i class="mgc_user_3_line text-success"></i>
                        <span id="sc-citizen-name" class="text-sm font-medium text-gray-800 dark:text-gray-100 flex-1"></span>
                        <button type="button" onclick="clearScheduleCitizen()" class="text-gray-400 hover:text-danger" aria-label="Remove"><i class="mgc_close_line"></i></button>
                    </div>
                    <div id="sc-citizen-pick">
                        <input type="text" id="sc-citizen-search" class="form-input w-full" placeholder="Search a citizen, or scan their ID…" autocomplete="off">
                        <div id="sc-citizen-results" class="mt-1 space-y-1"></div>
                    </div>
                </div>
                <div class="sc-6" id="sc-name-wrap">
                    <label class="form-label" for="sc-name">Or name (not registered)</label>
                    <input type="text" id="sc-name" class="form-input w-full" maxlength="150" placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="sc-6">
                    <label class="form-label" for="sc-contact">Contact number</label>
                    <input type="text" id="sc-contact" class="form-input w-full" maxlength="30">
                </div>
                <div>
                    <label class="form-label" for="sc-purpose">Purpose <span class="text-danger">*</span></label>
                    <input type="text" id="sc-purpose" class="form-input w-full" maxlength="255" placeholder="e.g. Patient transport to provincial hospital">
                </div>
                <div>
                    <label class="form-label" for="sc-location">Destination / venue</label>
                    <input type="text" id="sc-location" class="form-input w-full" maxlength="255">
                </div>
                <div>
                    <label class="form-label" for="sc-notes">Notes</label>
                    <textarea id="sc-notes" rows="2" class="form-input w-full" maxlength="2000"></textarea>
                </div>
            </div>
            <div id="sc-errors" class="hidden mt-3 p-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger"></div>
        </div>
        <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
            <button type="button" onclick="closeModal('sc-form-modal')" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
            <button type="button" id="sc-save" onclick="saveSchedule()" class="btn bg-primary text-white"><i class="mgc_check_line"></i> Reserve</button>
        </div>
    </div>
</div>

{{-- Reservation details --}}
<div id="sc-detail-modal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="sc-d-title">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-lg">
        <div class="flex justify-between items-start gap-3 py-3 px-4 border-b dark:border-gray-700">
            <div>
                <h3 id="sc-d-title" class="font-bold text-gray-800 dark:text-white"></h3>
                <p id="sc-d-when" class="text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button" onclick="closeModal('sc-detail-modal')" aria-label="Close"><i class="mgc_close_line text-xl text-gray-500 dark:text-gray-400"></i></button>
        </div>
        <div id="sc-d-body" class="p-4 space-y-2 text-sm"></div>
        <div id="sc-d-actions" class="flex flex-wrap justify-end gap-2 py-3 px-4 border-t dark:border-gray-700"></div>
    </div>
</div>
@endif

@endsection

@if($items->isNotEmpty())
@push('inline-scripts')
<script src="{{ asset('js/fullcalendar.min.js') }}"></script>
@include('partials.scan-anywhere')
<script>
const SC = {
    events:       '{{ route('inventory.schedule.events') }}',
    availability: '{{ route('inventory.schedule.availability') }}',
    store:        '{{ route('inventory.schedule.store') }}',
    base:         '{{ url('inventory/schedule') }}',
    citizens:     '{{ route('citizens.search') }}',
    profile:      '{{ url('citizens') }}',
};
const CAN_CREATE = @json(auth()->user()->can('inventory_schedule.create'));
const CAN_EDIT   = @json(auth()->user()->can('inventory_schedule.edit'));
const CSRF = '{{ csrf_token() }}';
let scItem = '', scEditId = null, scCitizen = null;

const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const pad = n => String(n).padStart(2, '0');
const toLocal = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
const atTime = (d, h, m = 0) => { const x = new Date(d); x.setHours(h, m, 0, 0); return x; };
const startOfToday = () => atTime(new Date(), 0);

function openModal(id)  { const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModal(id) { const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }

// ── Calendar ──────────────────────────────────────────────────────────
const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
    buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'List' },
    height: 'auto',
    dayMaxEvents: 3,
    eventDisplay: 'block',      // solid colored bars instead of dots
    displayEventEnd: false,
    scrollTime: '06:00:00',     // Week view opens at 6 AM, not midnight
    navLinks: true,
    nowIndicator: true,
    eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
    // Click a day (or drag across days / hours) to reserve. Past days can't be picked.
    selectable: CAN_CREATE,
    selectMirror: true,
    selectAllow: info => info.start >= startOfToday(),
    select: info => {
        calendar.unselect();
        if (info.allDay) {
            const lastDay = new Date(info.end.getTime() - 86400000);   // FullCalendar's end is exclusive
            openNewReservation(atTime(info.start, 8), atTime(lastDay, 17));
        } else {
            openNewReservation(info.start, info.end);
        }
    },
    events: (info, success, failure) => {
        const p = new URLSearchParams({ start: info.startStr, end: info.endStr });
        if (scItem) p.set('item_id', scItem);
        if (document.getElementById('sc-show-cancelled').checked) p.set('show_cancelled', 1);
        fetch(SC.events + '?' + p, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(success).catch(failure);
    },
    eventClick: info => { if (!info.event.extendedProps.buffer) openDetails(info.event); },
    eventDidMount: info => {
        info.el.title = info.event.extendedProps.buffer
            ? info.event.title + ''
            : info.event.title + '\n' + info.event.extendedProps.when;
    },
});
calendar.render();

document.querySelectorAll('.sc-filter').forEach(btn => btn.addEventListener('click', () => {
    scItem = btn.dataset.item;
    document.querySelectorAll('.sc-filter').forEach(b => {
        const on = b === btn;
        b.classList.toggle('bg-primary', on); b.classList.toggle('text-white', on);
        b.classList.toggle('bg-dark/10', !on); b.classList.toggle('text-slate-700', !on); b.classList.toggle('dark:text-slate-200', !on);
    });
    calendar.refetchEvents();
}));
document.getElementById('sc-show-cancelled').addEventListener('change', () => calendar.refetchEvents());

// ── New / edit form ───────────────────────────────────────────────────
function resetForm() {
    ['sc-name', 'sc-contact', 'sc-purpose', 'sc-location', 'sc-notes', 'sc-citizen-search'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('sc-qty').value = 1;
    document.getElementById('sc-citizen-results').innerHTML = '';
    document.getElementById('sc-errors').classList.add('hidden');
    clearScheduleCitizen();
}

function openNewReservation(start, end) {
    if (!CAN_CREATE) return;
    scEditId = null;
    resetForm();
    if (scItem) document.getElementById('sc-item').value = scItem;
    document.getElementById('sc-start').value = toLocal(start);
    document.getElementById('sc-end').value   = toLocal(end);
    document.getElementById('sc-form-title').textContent = 'New reservation';
    document.getElementById('sc-save').innerHTML = '<i class="mgc_check_line"></i> Reserve';
    onItemChange();
    openModal('sc-form-modal');
    setTimeout(() => document.getElementById('sc-purpose').focus(), 50);
}

function openEdit(ev) {
    const p = ev.extendedProps;
    closeModal('sc-detail-modal');
    scEditId = ev.id;
    resetForm();
    document.getElementById('sc-item').value  = p.item_id;
    document.getElementById('sc-qty').value   = p.quantity;
    document.getElementById('sc-start').value = p.start_local;
    document.getElementById('sc-end').value   = p.end_local;
    document.getElementById('sc-contact').value  = p.contact || '';
    document.getElementById('sc-purpose').value  = p.purpose || '';
    document.getElementById('sc-location').value = p.location || '';
    document.getElementById('sc-notes').value    = p.notes || '';
    if (p.citizen_id) selectScheduleCitizen({ id: p.citizen_id, name: p.for, contact: p.contact });
    else document.getElementById('sc-name').value = p.requester_name || '';
    document.getElementById('sc-form-title').textContent = 'Edit reservation';
    document.getElementById('sc-save').innerHTML = '<i class="mgc_check_line"></i> Save changes';
    onItemChange();
    openModal('sc-form-modal');
}

function onItemChange() {
    const opt = document.getElementById('sc-item').selectedOptions[0];
    const stock = parseInt(opt?.dataset.stock || '0', 10);
    document.getElementById('sc-qty-wrap').classList.toggle('hidden', stock <= 1);   // one ambulance → no quantity to pick
    document.getElementById('sc-item-wrap').classList.toggle('sc-6', stock > 1);     // …and the item takes the full row
    if (stock <= 1) document.getElementById('sc-qty').value = 1;
    checkAvailability();
}
document.getElementById('sc-item').addEventListener('change', onItemChange);
['sc-qty', 'sc-start', 'sc-end'].forEach(id => document.getElementById(id).addEventListener('change', checkAvailability));

// Live availability for the chosen item and time.
let availSeq = 0;
async function checkAvailability() {
    const box = document.getElementById('sc-availability');
    const start = document.getElementById('sc-start').value, end = document.getElementById('sc-end').value;
    if (!start || !end) { box.classList.add('hidden'); return; }
    if (end <= start) {
        box.className = 'p-3 rounded-lg text-sm bg-danger/10 border border-danger/30 text-danger';
        box.textContent = 'The end must be after the start.';
        return;
    }
    const mine = ++availSeq;
    const p = new URLSearchParams({ item_id: document.getElementById('sc-item').value, start_at: start, end_at: end });
    if (scEditId) p.set('except_id', scEditId);
    const res = await fetch(SC.availability + '?' + p, { headers: { Accept: 'application/json' } }).then(r => r.json()).catch(() => null);
    if (!res || mine !== availSeq) return;
    const qty = parseInt(document.getElementById('sc-qty').value || '1', 10);
    const unit = document.getElementById('sc-item').selectedOptions[0].dataset.unit || '';
    const list = res.conflicts.map(c => `<li>${esc(c.when)} — ${esc(c.for)}${c.quantity > 1 ? ' (' + c.quantity + ' ' + esc(unit) + ')' : ''}</li>`).join('');
    if (res.stock < 1) {
        box.className = 'p-3 rounded-lg text-sm bg-danger/10 border border-danger/30 text-danger';
        box.innerHTML = 'This item has no units in stock. Add stock to the item first.';
    } else if (qty <= res.available) {
        box.className = 'p-3 rounded-lg text-sm bg-success/10 border border-success/30 text-success';
        box.innerHTML = `<i class="mgc_check_circle_line"></i> Available` + (res.stock > 1 ? ` — ${res.available} of ${res.stock} ${esc(unit)} free at that time` : '')
            + (res.buffer > 0 ? `<span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Includes a ${res.buffer}-day buffer before and after other reservations.</span>` : '')
            + (list ? `<ul class="mt-1 list-disc ps-5 text-gray-600 dark:text-gray-300">${list}</ul>` : '');
    } else {
        box.className = 'p-3 rounded-lg text-sm bg-danger/10 border border-danger/30 text-danger';
        box.innerHTML = (res.available > 0 ? `Only ${res.available} ${esc(unit)} free at that time.` : 'Already reserved at that time:')
            + `<ul class="mt-1 list-disc ps-5">${list}</ul>`;
    }
}

// Who it's for: a registered citizen (search or scan) or a typed name.
let citizenTimer = null;
document.getElementById('sc-citizen-search').addEventListener('input', function () {
    clearTimeout(citizenTimer);
    const q = this.value.trim();
    const box = document.getElementById('sc-citizen-results');
    if (q.length < 2) { box.innerHTML = ''; return; }
    citizenTimer = setTimeout(() => fetch(SC.citizens + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
        .then(r => r.json()).then(list => {
            box.innerHTML = list.length ? list.slice(0, 6).map((c, i) => `<button type="button" data-i="${i}"
                class="sc-pick w-full text-left px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5">
                <span class="block text-sm text-gray-800 dark:text-gray-100">${esc(c.name)}</span>
                <span class="block text-xs text-gray-400">${esc(c.address || '')}</span></button>`).join('')
                : '<p class="text-xs text-gray-400">No citizen found — type the name in the field below instead.</p>';
            box.querySelectorAll('.sc-pick').forEach(b => b.addEventListener('click', () => selectScheduleCitizen(list[b.dataset.i])));
        }), 250);
});

function selectScheduleCitizen(c) {
    scCitizen = c;
    document.getElementById('sc-citizen-name').textContent = c.name;
    document.getElementById('sc-citizen-chip').classList.remove('hidden');
    document.getElementById('sc-citizen-chip').classList.add('flex');
    document.getElementById('sc-citizen-pick').classList.add('hidden');
    document.getElementById('sc-name-wrap').classList.add('hidden');
    document.getElementById('sc-citizen-results').innerHTML = '';
    const contact = document.getElementById('sc-contact');
    if (!contact.value && c.contact) contact.value = c.contact;
}

function clearScheduleCitizen() {
    scCitizen = null;
    document.getElementById('sc-citizen-chip').classList.add('hidden');
    document.getElementById('sc-citizen-chip').classList.remove('flex');
    document.getElementById('sc-citizen-pick').classList.remove('hidden');
    document.getElementById('sc-name-wrap').classList.remove('hidden');
}

// Scanning a Barangay ID while the form is open picks that citizen.
listenForScans({
    onScan: code => {
        if (document.getElementById('sc-form-modal').classList.contains('hidden')) return;
        fetch(SC.citizens + '?q=' + encodeURIComponent(code), { headers: { Accept: 'application/json' } })
            .then(r => r.json()).then(list => {
                const c = list.find(x => x.qrcode === code);
                if (c) selectScheduleCitizen(c);
                else document.getElementById('sc-citizen-results').innerHTML = '<p class="text-xs text-danger">No citizen found for this code.</p>';
            });
    },
});

async function saveSchedule() {
    const btn = document.getElementById('sc-save');
    const errBox = document.getElementById('sc-errors');
    errBox.classList.add('hidden');
    btn.disabled = true;
    const body = {
        item_id:        document.getElementById('sc-item').value,
        quantity:       document.getElementById('sc-qty').value || 1,
        start_at:       document.getElementById('sc-start').value,
        end_at:         document.getElementById('sc-end').value,
        citizen_id:     scCitizen ? scCitizen.id : null,
        requester_name: scCitizen ? null : document.getElementById('sc-name').value.trim(),
        contact:        document.getElementById('sc-contact').value.trim(),
        purpose:        document.getElementById('sc-purpose').value.trim(),
        location:       document.getElementById('sc-location').value.trim(),
        notes:          document.getElementById('sc-notes').value.trim(),
    };
    const res = await fetch(scEditId ? `${SC.base}/${scEditId}` : SC.store, {
        method: scEditId ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify(body),
    }).then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
      .catch(() => ({ ok: false, data: { message: 'Could not reach the server. Try again.' } }));
    btn.disabled = false;

    if (!res.ok || !res.data.success) {
        const msgs = Object.values(res.data.errors || {}).flat();
        errBox.innerHTML = (msgs.length ? msgs : [res.data.message || 'Could not save.']).map(esc).join('<br>');
        errBox.classList.remove('hidden');
        checkAvailability();
        return;
    }
    closeModal('sc-form-modal');
    calendar.refetchEvents();
    Swal.fire({ icon: 'success', title: scEditId ? 'Reservation updated' : 'Reserved', timer: 1500, showConfirmButton: false });
}

// ── Details ───────────────────────────────────────────────────────────
const STATUS_PILL = {
    reserved:  ['bg-primary/25 text-sky-800', 'bg-primary'],
    completed: ['bg-green-100 text-green-800', 'bg-green-400'],
    cancelled: ['bg-gray-100 text-gray-700', 'bg-gray-400'],
};
let detailEvent = null;

function openDetails(ev) {
    detailEvent = ev;
    const p = ev.extendedProps;
    const [cls, dot] = STATUS_PILL[p.status] || STATUS_PILL.reserved;
    document.getElementById('sc-d-title').textContent = (p.quantity > 1 ? p.quantity + '× ' : '') + p.item;
    document.getElementById('sc-d-when').textContent  = p.when;
    const row = (label, value) => value ? `<div class="flex gap-3"><span class="w-28 shrink-0 text-gray-500 dark:text-gray-400">${label}</span><span class="text-gray-800 dark:text-gray-100 min-w-0">${value}</span></div>` : '';
    document.getElementById('sc-d-body').innerHTML =
        row('Status', `<span class="inline-flex items-center gap-1.5 py-0.5 px-2.5 rounded-full text-xs font-medium ${cls}"><span class="w-1.5 h-1.5 inline-block ${dot} rounded-full"></span>${esc(p.status_label)}</span>`)
        + row('For', p.citizen_id ? `<a href="${SC.profile}/${p.citizen_id}" class="text-primary hover:underline">${esc(p.for)}</a>` : esc(p.for))
        + row('Contact', esc(p.contact))
        + row('Purpose', esc(p.purpose))
        + row('Destination', esc(p.location))
        + (p.status === 'reserved' && p.free_from ? row('Free again', esc(p.free_from) + ` <span class="text-xs text-gray-400">(${p.buffer_days}-day buffer)</span>`) : '')
        + row('Notes', esc(p.notes))
        + row('Cancel reason', esc(p.cancel_reason))
        + row('Booked by', esc([p.created_by, p.created_at].filter(Boolean).join(' · ')));

    const actions = document.getElementById('sc-d-actions');
    actions.innerHTML = '';
    if (CAN_EDIT && p.status === 'reserved') {
        actions.innerHTML = `
            <button type="button" id="sc-d-cancel" class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white">Cancel reservation</button>
            <button type="button" id="sc-d-edit" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_edit_line"></i> Edit</button>
            <button type="button" id="sc-d-done" class="btn bg-success/25 text-success hover:bg-success hover:text-white"><i class="mgc_check_line"></i> Mark completed</button>`;
        document.getElementById('sc-d-edit').onclick   = () => openEdit(detailEvent);
        document.getElementById('sc-d-done').onclick   = () => completeSchedule(detailEvent.id);
        document.getElementById('sc-d-cancel').onclick = () => cancelSchedule(detailEvent.id);
    } else {
        actions.innerHTML = `<button type="button" onclick="closeModal('sc-detail-modal')" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Close</button>`;
    }
    openModal('sc-detail-modal');
}

async function patchSchedule(id, action, body = {}) {
    return fetch(`${SC.base}/${id}/${action}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify(body),
    }).then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }));
}

async function completeSchedule(id) {
    const res = await patchSchedule(id, 'complete');
    if (!res.ok || !res.data.success) return Swal.fire({ icon: 'error', title: res.data.message || 'Could not update.' });
    closeModal('sc-detail-modal');
    calendar.refetchEvents();
    Swal.fire({ icon: 'success', title: 'Marked completed', timer: 1400, showConfirmButton: false });
}

async function cancelSchedule(id) {
    closeModal('sc-detail-modal');
    const r = await Swal.fire({
        title: 'Cancel this reservation?',
        input: 'text',
        inputLabel: 'Reason',
        inputPlaceholder: 'e.g. Requester no longer needs it',
        inputValidator: v => !v.trim() ? 'Please give the reason for cancelling.' : undefined,
        showCancelButton: true,
        confirmButtonText: 'Cancel reservation',
        cancelButtonText: 'Keep it',
        reverseButtons: true,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important'),
    });
    if (!r.isConfirmed) return;
    const res = await patchSchedule(id, 'cancel', { reason: r.value.trim() });
    if (!res.ok || !res.data.success) return Swal.fire({ icon: 'error', title: res.data.message || Object.values(res.data.errors || {}).flat()[0] || 'Could not cancel.' });
    calendar.refetchEvents();
    Swal.fire({ icon: 'success', title: 'Reservation cancelled', timer: 1400, showConfirmButton: false });
}

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    ['sc-form-modal', 'sc-detail-modal'].forEach(id => { if (!document.getElementById(id).classList.contains('hidden')) closeModal(id); });
});
</script>
@endpush
@endif
