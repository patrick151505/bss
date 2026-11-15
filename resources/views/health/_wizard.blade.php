{{--
    Step-by-step layout of the check-up form (create page). Inherits the
    variables set up in health._form. Every step is part of the one <form>,
    so nothing is lost when moving back and forth; the server still validates
    everything on save, and if it rejects something the wizard opens the step
    that holds the first error.
--}}
@php
    $hasMeds = $canDispense && $medicineItems->isNotEmpty();
    $steps = array_values(array_filter([
        ['key' => 'patient', 'label' => 'Patient',      'sub' => 'Who and when',       'icon' => 'mgc_user_3_line'],
        ['key' => 'vitals',  'label' => 'Vital signs',  'sub' => 'Measurements',       'icon' => 'mgc_heartbeat_line'],
        ['key' => 'consult', 'label' => 'Consultation', 'sub' => 'Complaint & advice', 'icon' => 'mgc_clipboard_line'],
        $hasMeds ? ['key' => 'meds', 'label' => 'Medicines', 'sub' => 'From Inventory', 'icon' => 'mgc_box_3_line'] : null,
        ['key' => 'review',  'label' => 'Review',       'sub' => 'Check and save',     'icon' => 'mgc_file_check_line'],
    ]));
    // Which step each server-side error belongs to.
    $errorStep = function (string $field) {
        return match (true) {
            str_starts_with($field, 'medicines')                                              => 'meds',
            in_array($field, ['citizen_id', 'patient_name', 'patient_age', 'patient_sex', 'checked_at', 'attended_by']) => 'patient',
            in_array($field, ['complaint', 'findings', 'advice', 'referred_to', 'follow_up_on']) => 'consult',
            default                                                                            => 'vitals',
        };
    };
    $firstErrorStep = $errors->any() ? $errorStep($errors->keys()[0]) : null;
@endphp

{{-- ── Stepper ── --}}
<div class="card mb-6">
    <ol class="flex items-center gap-1.5 md:gap-2 p-3 md:p-4" id="wizard-steps">
        @foreach($steps as $i => $s)
        <li class="flex items-center gap-1.5 md:gap-2 {{ $loop->last ? '' : 'flex-1' }} min-w-0">
            <button type="button" data-goto="{{ $s['key'] }}" class="wizard-tab group flex items-center gap-3 text-left rounded-lg px-1.5 py-1 disabled:cursor-not-allowed" disabled>
                <span class="wizard-dot w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center shrink-0 text-base font-semibold border-2 transition">
                    <span class="wizard-num">{{ $i + 1 }}</span>
                    <i class="wizard-check mgc_check_line hidden"></i>
                </span>
                <span class="hidden lg:block whitespace-nowrap">
                    <span class="wizard-label block text-sm font-semibold">{{ $s['label'] }}</span>
                    <span class="block text-xs text-gray-400">{{ $s['sub'] }}</span>
                </span>
            </button>
            @unless($loop->last)
            <span class="wizard-line flex-1 h-0.5 min-w-[8px] rounded-full bg-gray-200 dark:bg-gray-700 transition"></span>
            @endunless
        </li>
        @endforeach
    </ol>
    {{-- Who this check-up is for, once chosen — keeps the context on later steps --}}
    <div id="wizard-context" class="hidden px-5 py-2.5 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 items-center gap-2">
        <i class="mgc_user_3_line text-primary"></i><span id="wizard-context-text"></span>
    </div>
</div>

{{-- Problems found before moving on --}}
<div id="wizard-error" class="hidden mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3" role="alert">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <ul id="wizard-error-list" class="text-sm text-danger font-medium space-y-0.5"></ul>
</div>

{{-- ── Step 1: patient + visit ── --}}
<div class="wizard-step hidden" data-step="patient">
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">@include('health._sections.patient')</div>
        <div>@include('health._sections.visit')</div>
    </div>
</div>

{{-- ── Step 2: vital signs ── --}}
<div class="wizard-step hidden" data-step="vitals">
    @include('health._sections.vitals')
</div>

{{-- ── Step 3: consultation ── --}}
<div class="wizard-step hidden" data-step="consult">
    @include('health._sections.consult')
</div>

{{-- ── Step 4: medicines (only for roles that may dispense) ── --}}
@if($hasMeds)
<div class="wizard-step hidden" data-step="meds">
    <div class="max-w-3xl">@include('health._sections.medicines')</div>
</div>
@endif

{{-- ── Step 5: review ── --}}
<div class="wizard-step hidden" data-step="review">
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h4 class="card-title">Patient</h4>
                    <button type="button" data-goto="patient" class="text-sm text-primary hover:underline"><i class="mgc_edit_line me-1"></i>Edit</button>
                </div>
                <div class="p-6" id="rv-patient"></div>
            </div>
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h4 class="card-title">Vital signs</h4>
                    <button type="button" data-goto="vitals" class="text-sm text-primary hover:underline"><i class="mgc_edit_line me-1"></i>Edit</button>
                </div>
                <div class="p-6" id="rv-vitals"></div>
            </div>
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h4 class="card-title">Consultation</h4>
                    <button type="button" data-goto="consult" class="text-sm text-primary hover:underline"><i class="mgc_edit_line me-1"></i>Edit</button>
                </div>
                <div class="p-6" id="rv-consult"></div>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h4 class="card-title">Medicines given</h4>
                    @if($hasMeds)
                    <button type="button" data-goto="meds" class="text-sm text-primary hover:underline"><i class="mgc_edit_line me-1"></i>Edit</button>
                    @endif
                </div>
                <div class="p-6" id="rv-meds">
                    @unless($hasMeds)
                    <p class="text-sm text-gray-400">
                        @if($canDispense)
                        No medicines in stock. Only items in a category marked “Use for medicine” are listed. @can('inventory_categories.view')<a href="{{ route('inventory.categories.index') }}" class="text-primary hover:underline">Set it in Inventory → Categories</a>@endcan
                        @else
                        You can’t give medicines from stock. Ask someone with the “Dispense” permission, or write them in Advice.
                        @endif
                    </p>
                    @endunless
                </div>
            </div>
            <div id="rv-alerts" class="card p-6 hidden">
                <h4 class="card-title mb-3">Needs attention</h4>
                <div id="rv-alerts-list" class="flex flex-wrap gap-1.5"></div>
                <p class="text-xs text-gray-400 mt-3">Screening guides for adults, not a diagnosis.</p>
            </div>
        </div>
    </div>
</div>

{{-- ── Navigation ── --}}
<div class="card p-4 mt-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        <button type="button" id="wizard-back" onclick="wizardGo(-1)" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white hidden">
            <i class="mgc_arrow_left_line me-1"></i>Back
        </button>
        <a href="{{ route('health.index') }}" class="btn text-gray-500 dark:text-gray-400 hover:text-danger">Cancel</a>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span id="wizard-count" class="text-xs text-gray-400 me-1"></span>
        <button type="button" id="wizard-next" onclick="wizardGo(1)" class="btn bg-primary text-white">
            Next<i class="mgc_arrow_right_line ms-1"></i>
        </button>
        <button type="submit" name="after" value="another" id="wizard-save-next" class="btn bg-success/25 text-success hover:bg-success hover:text-white hidden">
            <i class="mgc_user_add_line me-1"></i>Save &amp; next patient
        </button>
        <button type="submit" id="wizard-save" class="btn bg-primary text-white hidden"><i class="mgc_check_line me-1"></i>Save check-up</button>
    </div>
</div>

@push('inline-scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const STEPS = @json(array_column($steps, 'key'));
    const LABELS = @json(array_column($steps, 'label'));
    const form  = document.querySelector('[data-step]').closest('form');
    const q     = s => document.querySelector(s);
    const field = n => form.querySelector(`[name="${n}"]`);
    const valOf = n => (field(n)?.value ?? '').trim();
    let current = 0, reached = 0;

    // ── Checks per step (the server checks everything again on save) ──
    const CHECKS = {
        patient() {
            const errs = [];
            if (q('#walkin-toggle').checked) { if (!valOf('patient_name')) errs.push(['patient_name', 'Enter the walk-in patient’s name.']); }
            else if (!valOf('citizen_id')) errs.push(['patient-search', 'Choose the patient — search by name or scan their Barangay ID — or tick “Walk-in”.']);
            const at = valOf('checked_at');
            if (!at) errs.push(['checked_at', 'Enter the date and time of the check-up.']);
            else if (new Date(at) > new Date(Date.now() + 5 * 60000)) errs.push(['checked_at', 'The check-up date can’t be in the future.']);
            return errs;
        },
        vitals() {
            const s = valOf('bp_systolic'), d = valOf('bp_diastolic');
            return (s && !d) || (!s && d) ? [[s ? 'bp_diastolic' : 'bp_systolic', 'Enter both numbers of the blood pressure (e.g. 120 / 80).']] : [];
        },
        consult: () => [],
        meds() {
            const errs = [];
            form.querySelectorAll('.med-row').forEach(row => {
                const item = row.querySelector('.med-item'), qty = row.querySelector('.med-qty');
                const name = item.selectedOptions[0]?.text.replace(/\s*\(.*left\)$/, '') || '';
                if (item.value && !qty.value) errs.push([qty, `Enter how many ${name} were given.`]);
                else if (!item.value && qty.value) errs.push([item, 'Pick the medicine for each quantity entered.']);
                else if (item.value && qty.max && +qty.value > +qty.max) errs.push([qty, `Only ${qty.max} ${name} left in stock.`]);
            });
            return errs;
        },
        review: () => [],
    };

    function validate(key) {
        const step = q(`[data-step="${key}"]`);
        const errs = (CHECKS[key] || (() => []))();
        // Browser checks too (min/max on the number fields).
        step.querySelectorAll('input, select, textarea').forEach(el => {
            if (!el.disabled && !el.checkValidity() && !errs.some(e => e[0] === el || e[0] === el.name)) {
                let p = el.parentElement;
                while (p && p !== step && !p.querySelector(':scope > label')) p = p.parentElement;
                const label = p?.querySelector(':scope > label')?.childNodes[0]?.textContent.trim() || 'A field';   // label text without the unit
                errs.push([el, `${label}: ${el.validationMessage}`]);
            }
        });
        const box = q('#wizard-error');
        box.classList.toggle('hidden', !errs.length);
        q('#wizard-error-list').innerHTML = errs.map(e => `<li>${esc(e[1])}</li>`).join('');
        if (errs.length) {
            const first = typeof errs[0][0] === 'string' ? (field(errs[0][0]) || q('#' + errs[0][0])) : errs[0][0];
            first?.focus();
        }
        return !errs.length;
    }

    // ── Showing a step ──
    function show(i) {
        current = i; reached = Math.max(reached, i);
        const key = STEPS[i];
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.toggle('hidden', s.dataset.step !== key));
        document.querySelectorAll('#wizard-steps .wizard-tab').forEach((b, n) => {
            const done = n < i, on = n === i;
            b.disabled = n > reached;
            const dot = b.querySelector('.wizard-dot');
            dot.className = 'wizard-dot w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center shrink-0 text-base font-semibold border-2 transition '
                + (on ? 'border-primary bg-primary text-white' : done ? 'border-success bg-success/10 text-success' : 'border-gray-200 dark:border-gray-600 text-gray-400');
            b.querySelector('.wizard-num').classList.toggle('hidden', done);
            b.querySelector('.wizard-check').classList.toggle('hidden', !done);
            b.querySelector('.wizard-label')?.classList.toggle('text-gray-800', on || done);
            b.querySelector('.wizard-label')?.classList.toggle('dark:text-gray-100', on || done);
            b.querySelector('.wizard-label')?.classList.toggle('text-gray-400', !on && !done);
            b.setAttribute('aria-current', on ? 'step' : 'false');
        });
        document.querySelectorAll('#wizard-steps .wizard-line').forEach((l, n) => {
            l.classList.toggle('bg-success', n < i);
            l.classList.toggle('bg-gray-200', n >= i);
            l.classList.toggle('dark:bg-gray-700', n >= i);
        });
        const last = i === STEPS.length - 1;
        q('#wizard-back').classList.toggle('hidden', i === 0);
        q('#wizard-next').classList.toggle('hidden', last);
        q('#wizard-save').classList.toggle('hidden', !last);
        q('#wizard-save-next').classList.toggle('hidden', !last);
        q('#wizard-count').textContent = `Step ${i + 1} of ${STEPS.length} · ${LABELS[i]}`;
        updateContext();
        if (key === 'review') renderReview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        // Focus the first empty field so typing can start right away.
        setTimeout(() => {
            const step = q(`[data-step="${key}"]`);
            const target = key === 'patient' && !q('#walkin-toggle').checked && !valOf('citizen_id') ? q('#patient-search')
                : [...step.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), textarea, select')].find(el => !el.disabled && el.offsetParent && !el.value);
            target?.focus({ preventScroll: true });
        }, 50);
    }

    window.wizardGo = dir => {
        if (dir > 0 && !validate(STEPS[current])) return;
        if (dir < 0) q('#wizard-error').classList.add('hidden');
        show(Math.min(STEPS.length - 1, Math.max(0, current + dir)));
    };

    // Jump via the stepper or the review "Edit" links — forward jumps check each step on the way.
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-goto]');
        if (!b || b.disabled) return;
        const to = STEPS.indexOf(b.dataset.goto);
        if (to < 0) return;
        for (let n = current; n < to; n++) { if (!validate(STEPS[n])) { show(n); validate(STEPS[n]); return; } }
        q('#wizard-error').classList.add('hidden');
        show(to);
    });

    // Enter moves to the next step instead of saving half a form. The ID scanner
    // cancels the Enter that ends a scan, so those are left alone; Enter in the
    // patient search and in text areas keeps its normal meaning.
    document.addEventListener('keydown', e => {
        if (e.key !== 'Enter' || e.defaultPrevented || !form.contains(e.target)) return;
        if (e.target.matches('textarea, button, [type=submit]')) return;
        e.preventDefault();
        if (e.target.id === 'patient-search') return;
        if (current < STEPS.length - 1) wizardGo(1);
    });

    // Saving: re-check every step and open the first one with a problem.
    form.addEventListener('submit', e => {
        for (let n = 0; n < STEPS.length; n++) {
            if (!validate(STEPS[n])) { e.preventDefault(); show(n); validate(STEPS[n]); return; }
        }
    });

    // ── Patient line under the stepper ──
    function patientText() {
        if (q('#walkin-toggle').checked) {
            const name = valOf('patient_name'); if (!name) return null;
            const sex = { M: 'Male', F: 'Female' }[valOf('patient_sex')];
            return [name, valOf('patient_age') ? valOf('patient_age') + ' yrs' : null, sex, 'Walk-in'].filter(Boolean).join(' · ');
        }
        if (!valOf('citizen_id')) return null;
        return [q('#pc-name').textContent, q('#pc-meta').textContent].filter(Boolean).join(' · ');
    }
    function updateContext() {
        const t = current > 0 ? patientText() : null, box = q('#wizard-context');
        box.classList.toggle('hidden', !t); box.classList.toggle('flex', !!t);
        q('#wizard-context-text').textContent = t || '';
    }

    // ── Review ──
    const fmtWhen = v => {
        if (!v) return '—';
        const d = new Date(v);
        const date = d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        return `${date} ${time}`;   // M d, Y g:i A
    };
    const row = (label, value, extra = '') => `
        <div class="flex justify-between gap-4 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <span class="text-sm text-gray-500 dark:text-gray-400">${label}</span>
            <span class="text-sm text-right text-gray-800 dark:text-gray-100">${value}${extra ? ' ' + extra : ''}</span>
        </div>`;
    const text = v => v ? esc(v) : '<span class="text-gray-400">—</span>';

    function renderReview() {
        q('#rv-patient').innerHTML = row('Patient', text(patientText()))
            + (!q('#walkin-toggle').checked && q('#pc-last').textContent ? row('History', esc(q('#pc-last').textContent)) : '')
            + row('Date &amp; time', esc(fmtWhen(valOf('checked_at'))))
            + row('Attended by', text(valOf('attended_by')));

        const bp = valOf('bp_systolic') && valOf('bp_diastolic') ? `${valOf('bp_systolic')}/${valOf('bp_diastolic')} mmHg` : '';
        const bmi = q('#bmi-out').textContent.trim();
        const sugar = valOf('blood_sugar') ? `${valOf('blood_sugar')} mg/dL · ${q('#sugar_fasting').checked ? 'fasting' : 'random'}` : '';
        const vitals = [
            ['Blood pressure', bp, 'bp'], ['Temperature', valOf('temperature') && valOf('temperature') + ' °C', 'temp'],
            ['Pulse', valOf('pulse') && valOf('pulse') + ' bpm', 'pulse'], ['Respiratory rate', valOf('resp_rate') && valOf('resp_rate') + ' /min', null],
            ['Oxygen saturation', valOf('o2_sat') && valOf('o2_sat') + ' %', 'o2'], ['Blood sugar', sugar, 'sugar'],
            ['Weight', valOf('weight_kg') && valOf('weight_kg') + ' kg', null], ['Height', valOf('height_cm') && valOf('height_cm') + ' cm', null],
            ['BMI', bmi !== '—' ? bmi : '', 'bmi'],
        ].filter(v => v[1]);
        const flags = vitals.map(v => v[2] && FLAGS[v[2]]()).filter(f => f && f[1] !== 'success');
        q('#rv-vitals').innerHTML = vitals.length
            ? vitals.map(([l, v, k]) => row(l, esc(v), k ? pill(FLAGS[k]()) : '')).join('')
            : '<p class="text-sm text-gray-400">No vital signs entered.</p>';
        q('#rv-alerts').classList.toggle('hidden', !flags.length);
        q('#rv-alerts-list').innerHTML = flags.map(pill).join('');

        q('#rv-consult').innerHTML = [['Complaint', 'complaint'], ['Findings / assessment', 'findings'], ['Advice / treatment', 'advice']]
            .map(([l, n]) => `<div class="mb-3"><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-0.5">${l}</p>
                              <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">${text(valOf(n))}</p></div>`).join('')
            + row('Referred to', text(valOf('referred_to')))
            + row('Follow-up on', valOf('follow_up_on') ? esc(new Date(valOf('follow_up_on') + 'T00:00').toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' })) : text(''));

        const medBox = q('#rv-meds');
        if (form.querySelector('.med-row') !== null || STEPS.includes('meds')) {
            const meds = [...form.querySelectorAll('.med-row')].map(r => {
                const it = r.querySelector('.med-item');
                return it.value ? { name: it.selectedOptions[0].text.replace(/\s*\(.*left\)$/, ''), qty: r.querySelector('.med-qty').value,
                                    unit: r.querySelector('.med-unit').textContent, how: r.querySelector('[name$="[instructions]"]').value } : null;
            }).filter(Boolean);
            medBox.innerHTML = meds.length
                ? meds.map(m => `<div class="py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex justify-between gap-2 text-sm"><span class="font-medium text-gray-800 dark:text-gray-100">${esc(m.name)}</span>
                        <span class="text-gray-600 dark:text-gray-300 whitespace-nowrap">${esc(m.qty)} ${esc(m.unit)}</span></div>
                        ${m.how ? `<p class="text-xs text-gray-500 dark:text-gray-400">${esc(m.how)}</p>` : ''}</div>`).join('')
                  + '<p class="text-xs text-gray-400 mt-3">Taken out of Inventory stock when you save.</p>'
                : '<p class="text-sm text-gray-400">None — nothing will be taken from stock.</p>';
        }
    }

    // Start on the step with the server's first error, otherwise on step 1.
    const startKey = @json($firstErrorStep);
    const start = Math.max(0, STEPS.indexOf(startKey));
    reached = startKey ? STEPS.length - 1 : 0;   // after a failed save every step was already filled in
    show(start);
});
</script>
@endpush
