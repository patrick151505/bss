{{--
    Marks the fields Settings → Citizen Fields makes required (red *) and
    un-marks the ones it makes optional. Place it INSIDE the citizen form.
    The server checks the same settings, so this is only the visual cue —
    it never sets the browser's own "required", which would silently block
    saving when the field sits in a collapsed section.

    @param string $form  'citizen' (Add / Edit) or 'minor' (Register Minor)
--}}
@php
    $fieldRulesForm = $form ?? 'citizen';
    $fieldRulesAll  = \App\Support\CitizenFieldRules::fieldsFor($fieldRulesForm);
    $fieldRulesReq  = \App\Support\CitizenFieldRules::requiredOnForm($fieldRulesForm);
@endphp
<span id="citizen-field-rules" class="hidden" data-all='@json($fieldRulesAll)' data-required='@json($fieldRulesReq)'></span>
<script>
// Runs once the whole form is on the page (this sits above its fields).
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('citizen-field-rules');
    const form = el.closest('form');
    if (!form) return;
    const all = JSON.parse(el.dataset.all), req = JSON.parse(el.dataset.required);

    all.forEach(name => {
        const input = form.querySelector('[name="' + name + '"]');
        if (!input) return;
        const on = req.includes(name);
        input.removeAttribute('required');

        if (name === 'profile') {
            const btn = input.id ? form.querySelector('label[for="' + input.id + '"]') : null;
            if (on && btn && !btn.parentElement.querySelector('.field-rule-note')) {
                const p = document.createElement('p');
                p.className = 'field-rule-note text-xs text-danger mt-1 text-center';
                p.textContent = 'A photo is required.';
                btn.insertAdjacentElement('afterend', p);
            }
            return;
        }

        const label = input.parentElement.querySelector(':scope > label.form-label')
                   || input.parentElement.parentElement?.querySelector(':scope > label.form-label');
        if (!label) return;
        const star = [...label.querySelectorAll('span.text-danger')].find(s => s.textContent.trim() === '*');
        if (on && !star) {
            const s = document.createElement('span');
            s.className = 'text-danger';
            s.textContent = '*';
            label.append(' ', s);
        } else if (!on && star) {
            star.remove();
        }
    });
});
</script>
