{{-- Check-up form section. Inherits the variables set up in health._form. --}}
@php $typeValue = old('checkup_type', $C->checkup_type); @endphp
<div class="card p-6 space-y-4">
    {{-- Type of check-up: pick a saved type or type a new one (kept for next time), like document purposes --}}
    <div>
        <label for="ct-search" class="{{ $lbl }}">Type of check-up <span class="text-danger">*</span></label>
        <input type="hidden" name="checkup_type" id="ct-input" value="{{ $typeValue }}" required>
        <div id="ct-dd" class="relative">
            <div class="relative">
                <input type="text" id="ct-search" autocomplete="off" maxlength="100"
                       class="form-input w-full pr-9 @error('checkup_type') border-danger @enderror"
                       value="{{ $typeValue }}" placeholder="Search or type a check-up type…"
                       onfocus="ctOpen()" oninput="ctOnInput()" onkeydown="ctOnKey(event)">
                <button type="button" tabindex="-1" onclick="ctToggle()"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i id="ct-chevron" class="mgc_down_line"></i>
                </button>
            </div>
            <div id="ct-list" class="hidden absolute z-30 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                @foreach($checkupTypes ?? [] as $t)
                <button type="button" class="ct-option w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-primary/10"
                        data-name="{{ $t }}" onclick="ctPick(this.dataset.name)">{{ $t }}</button>
                @endforeach
                <button type="button" id="ct-add" onclick="ctPick(document.getElementById('ct-search').value.trim())"
                        class="hidden w-full text-left px-3 py-2 text-sm text-primary hover:bg-primary/10 border-t border-gray-100 dark:border-gray-700">
                    <i class="mgc_add_line"></i> Use "<span id="ct-add-label"></span>"
                </button>
                <div id="ct-empty" class="hidden px-3 py-3 text-xs text-gray-400 text-center">No matches.</div>
            </div>
        </div>
        @error('checkup_type')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
        <p class="text-xs text-gray-400 mt-1">Click to pick, or type to search. A new type is saved for next time.</p>
    </div>
    <div>
        <label class="{{ $lbl }}">Date &amp; time <span class="text-danger">*</span></label>
        <input type="datetime-local" name="checked_at" required value="{{ old('checked_at', $C->checked_at?->format('Y-m-d\TH:i')) }}" class="form-input w-full">
    </div>
    <div>
        <label class="{{ $lbl }}">Attended by</label>
        <input type="text" name="attended_by" value="{{ old('attended_by', $C->attended_by) }}" maxlength="255" list="attended-list" class="form-input w-full" placeholder="Doctor, nurse or BHW">
        <datalist id="attended-list">
            @foreach(\App\Models\HealthCheckup::whereNotNull('attended_by')->select('attended_by')->distinct()->orderBy('attended_by')->limit(30)->pluck('attended_by') as $a)
            <option value="{{ $a }}">
            @endforeach
        </datalist>
    </div>
</div>

<script>
// ── Type of check-up: searchable dropdown; a typed new type is used as-is ──
function ctEls() {
    return {
        search: document.getElementById('ct-search'), hidden: document.getElementById('ct-input'),
        list: document.getElementById('ct-list'), chevron: document.getElementById('ct-chevron'),
        add: document.getElementById('ct-add'), addLbl: document.getElementById('ct-add-label'),
        empty: document.getElementById('ct-empty'),
    };
}
function ctOpen() {
    const e = ctEls();
    e.list.classList.remove('hidden');
    e.chevron.classList.replace('mgc_down_line', 'mgc_up_line');
    ctFilter();
}
function ctClose() {
    const e = ctEls();
    e.list.classList.add('hidden');
    e.chevron.classList.replace('mgc_up_line', 'mgc_down_line');
}
function ctToggle() {
    const e = ctEls();
    if (e.list.classList.contains('hidden')) { e.search.focus(); ctOpen(); } else ctClose();
}
function ctFilter() {
    const e = ctEls();
    const q = e.search.value.trim().toLowerCase();
    let shown = 0, exact = false;
    document.querySelectorAll('.ct-option').forEach(o => {
        const n = o.dataset.name.toLowerCase();
        const ok = !q || n.includes(q);
        o.classList.toggle('hidden', !ok);
        if (ok) shown++;
        if (n === q) exact = true;
    });
    e.add.classList.toggle('hidden', !q || exact);
    e.addLbl.textContent = e.search.value.trim();
    e.empty.classList.toggle('hidden', shown > 0 || (q && !exact));
}
function ctOnInput() {
    const e = ctEls();
    e.search.classList.remove('border-danger');
    e.hidden.value = e.search.value.trim();     // a brand-new type counts even without clicking "Use"
    ctOpen();
}
function ctPick(name) {
    const e = ctEls();
    e.search.value = name; e.hidden.value = name;
    e.search.classList.remove('border-danger');
    ctClose();
}
function ctOnKey(ev) {
    const e = ctEls();
    if (ev.key === 'Enter') {
        ev.preventDefault();
        // Exact match → that type; other text → the typed type; empty → first in the list.
        const q = e.search.value.trim();
        const exact = [...document.querySelectorAll('.ct-option')].find(o => o.dataset.name.toLowerCase() === q.toLowerCase());
        const first = document.querySelector('.ct-option:not(.hidden)');
        if (exact) ctPick(exact.dataset.name);
        else if (q) ctPick(q);
        else if (first) ctPick(first.dataset.name);
    } else if (ev.key === 'Escape') {
        ctClose();
    }
}
document.addEventListener('click', ev => {
    const wrap = document.getElementById('ct-dd');
    if (wrap && !wrap.contains(ev.target)) ctClose();
});
</script>
