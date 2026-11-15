{{-- Check-up form behaviour: patient picker, live vital-sign flags, medicine rows. --}}
@if($canDispense && $medicineItems->isNotEmpty())
<template id="med-row-tpl">@include('health._med-row', ['i' => '__I__', 'row' => []])</template>
@endif

@push('inline-scripts')
@include('partials.scan-anywhere')
<script>
const PATIENTS_URL = @json(route('health.patients'));
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const FLAG_CLS = { danger: ['bg-red-100 text-red-800', 'bg-red-400'], warning: ['bg-yellow-100 text-yellow-800', 'bg-yellow-400'], success: ['bg-green-100 text-green-800', 'bg-green-400'] };
const pill = f => f ? `<span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium whitespace-nowrap ${FLAG_CLS[f[1]][0]}"><span class="w-1.5 h-1.5 inline-block rounded-full ${FLAG_CLS[f[1]][1]}"></span>${f[0]}</span>` : '';

// ── Patient ───────────────────────────────────────────────────────────
function showPatient(p) {
    document.getElementById('citizen_id').value = p.id;
    const chip = document.getElementById('patient-chip');
    chip.classList.remove('hidden'); chip.classList.add('flex');
    document.getElementById('patient-search-wrap').classList.add('hidden');
    document.getElementById('pc-name').textContent = p.name;
    document.getElementById('pc-meta').textContent = [p.age !== null ? p.age + ' yrs' : null, p.sex, p.address].filter(Boolean).join(' · ');
    document.getElementById('pc-last').innerHTML = p.last
        ? `Last check-up <a href="${esc(p.last.url)}" target="_blank" class="text-primary hover:underline">${esc(p.last.date)}</a>${p.last.bp ? ' · BP ' + esc(p.last.bp) : ''}${p.last.weight ? ' · ' + esc(p.last.weight) + ' kg' : ''}`
        : 'First check-up on record';
    const img = document.getElementById('pc-photo'), ini = document.getElementById('pc-initials');
    if (p.photo) { img.src = p.photo; img.classList.remove('hidden'); ini.classList.add('hidden'); }
    else { img.classList.add('hidden'); ini.classList.remove('hidden'); ini.textContent = p.name.split(' ').filter(Boolean).map(s => s[0]).slice(0, 2).join('').toUpperCase(); }
}
function clearPatient() {
    document.getElementById('citizen_id').value = '';
    const chip = document.getElementById('patient-chip');
    chip.classList.add('hidden'); chip.classList.remove('flex');
    document.getElementById('patient-search-wrap').classList.remove('hidden');
    document.getElementById('patient-results').innerHTML = '';
    const s = document.getElementById('patient-search'); s.value = ''; s.focus();
}
let searchSeq = 0, searchTimer = null, lastResults = [];
async function searchPatients(q) {
    const seq = ++searchSeq, box = document.getElementById('patient-results');
    if (q.trim().length < 2) { box.innerHTML = ''; return []; }
    const list = await (await fetch(PATIENTS_URL + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })).json();
    if (seq !== searchSeq) return list;
    lastResults = list;
    box.innerHTML = list.length ? list.map((p, i) => `
        <button type="button" data-i="${i}" class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/50 flex justify-between gap-3">
            <span><span class="font-medium text-gray-800 dark:text-gray-100">${esc(p.name)}</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">${esc([p.age !== null ? p.age + ' yrs' : null, p.sex, p.address].filter(Boolean).join(' · '))}</span></span>
            <span class="text-xs text-gray-400 whitespace-nowrap">${p.last ? 'Last: ' + esc(p.last.date) : ''}</span>
        </button>`).join('')
        : '<p class="text-sm text-gray-400 px-1">No citizen found. Tick “Walk-in” to record someone not in the records.</p>';
    return list;
}
document.getElementById('patient-search').addEventListener('input', e => {
    clearTimeout(searchTimer); searchTimer = setTimeout(() => searchPatients(e.target.value), 250);
});
document.getElementById('patient-results').addEventListener('click', e => {
    const b = e.target.closest('[data-i]'); if (b) showPatient(lastResults[b.dataset.i]);
});
listenForScans({
    skip: null,
    onScan: async code => {
        if (document.getElementById('walkin-toggle').checked) return;
        const list = await searchPatients(code);
        const p = list.find(x => x.qrcode === code);
        if (p) showPatient(p);
        else document.getElementById('patient-results').innerHTML = '<p class="text-sm text-danger px-1">No citizen found for this ID.</p>';
    },
});
document.getElementById('walkin-toggle').addEventListener('change', e => {
    const w = e.target.checked;
    document.getElementById('citizen-box').classList.toggle('hidden', w);
    document.getElementById('walkin-box').classList.toggle('hidden', !w);
    document.querySelectorAll('#walkin-box input, #walkin-box select').forEach(el => el.disabled = !w);
    if (w) { document.getElementById('citizen_id').value = ''; document.getElementById('patient_name').focus(); }
    else { const id = document.getElementById('citizen_id'); if (!id.value) clearPatient(); }
});
@if($patientInit)
showPatient(@json($patientInit));
@endif

// ── Live vital-sign flags (same thresholds as App\Models\HealthCheckup) ─
const val = n => { const v = parseFloat(document.querySelector(`[name="${n}"]`).value); return isNaN(v) ? null : v; };
const FLAGS = {
    bp: () => { const s = val('bp_systolic'), d = val('bp_diastolic'); if (!s || !d) return null;
        return s >= 180 || d >= 120 ? ['Hypertensive crisis', 'danger'] : s >= 140 || d >= 90 ? ['High (stage 2)', 'danger']
             : s >= 130 || d >= 80 ? ['High (stage 1)', 'warning'] : s >= 120 ? ['Elevated', 'warning']
             : s < 90 || d < 60 ? ['Low', 'warning'] : ['Normal', 'success']; },
    temp: () => { const t = val('temperature'); if (!t) return null;
        return t >= 38 ? ['Fever', 'danger'] : t >= 37.5 ? ['Slight fever', 'warning'] : t < 35 ? ['Low', 'danger'] : ['Normal', 'success']; },
    pulse: () => { const p = val('pulse'); if (!p) return null; return p < 60 ? ['Low', 'warning'] : p > 100 ? ['High', 'warning'] : ['Normal', 'success']; },
    o2: () => { const o = val('o2_sat'); if (!o) return null; return o < 90 ? ['Low', 'danger'] : o < 95 ? ['Slightly low', 'warning'] : ['Normal', 'success']; },
    sugar: () => { const g = val('blood_sugar'); if (!g) return null; if (g < 70) return ['Low', 'danger'];
        return document.getElementById('sugar_fasting').checked
            ? (g >= 126 ? ['High', 'danger'] : g >= 100 ? ['Above normal', 'warning'] : ['Normal', 'success'])
            : (g >= 200 ? ['High', 'danger'] : g >= 140 ? ['Above normal', 'warning'] : ['Normal', 'success']); },
    bmi: () => { const w = val('weight_kg'), h = val('height_cm'); const out = document.getElementById('bmi-out');
        if (!w || !h) { out.textContent = '—'; return null; }
        const b = Math.round(w / ((h / 100) ** 2) * 10) / 10; out.textContent = b;
        return b < 18.5 ? ['Underweight', 'warning'] : b < 25 ? ['Normal', 'success'] : b < 30 ? ['Overweight', 'warning'] : ['Obese', 'danger']; },
};
function refreshFlag(k) { document.querySelector(`[data-flag="${k}"]`).innerHTML = pill(FLAGS[k]()); }
document.querySelectorAll('.vital').forEach(el => el.addEventListener('input', () => refreshFlag(el.dataset.v)));
document.getElementById('sugar_fasting').addEventListener('change', () => refreshFlag('sugar'));
Object.keys(FLAGS).forEach(refreshFlag);

// ── Medicines ─────────────────────────────────────────────────────────
let medSeq = {{ count($medRows) }};
function addMedRow() {
    const tpl = document.getElementById('med-row-tpl');
    if (!tpl) return;
    const wrap = document.createElement('div');
    wrap.innerHTML = tpl.innerHTML.replaceAll('__I__', medSeq++);
    const row = wrap.firstElementChild;
    document.getElementById('med-rows').appendChild(row);
    row.querySelector('select').focus();
}
document.addEventListener('change', e => {
    if (!e.target.matches('.med-item')) return;
    const opt = e.target.selectedOptions[0], row = e.target.closest('.med-row');
    row.querySelector('.med-unit').textContent = opt?.dataset.unit || '';
    const qty = row.querySelector('.med-qty');
    qty.max = opt?.dataset.stock || '';
    if (opt?.value && !qty.value) qty.value = 1;
});
</script>
@endpush
