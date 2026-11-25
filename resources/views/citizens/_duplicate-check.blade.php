{{--
    Live duplicate warning for the citizen forms. Place it INSIDE the form.
    Watches the form's fname / lname / bday fields and asks the server for
    matches across all records (archived, walk-ins, other zones).

    @param int|null $except  citizen being edited (never matches itself)
    @param string   $class   spacing around the panel (default mb-4)
    @param bool     $gate    true = possible matches need the "different person"
                             tick before saving (Add / Register Minor)
--}}
@php $gate = $gate ?? true; @endphp
<div id="dup-check" class="hidden {{ $class ?? 'mb-4' }}" data-url="{{ route('citizens.duplicate-check') }}" data-except="{{ $except ?? '' }}" data-gate="{{ $gate ? 1 : 0 }}">

    {{-- Exact match: cannot be saved --}}
    <div id="dup-block" class="hidden p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
        <i class="mgc_close_circle_line text-danger text-xl mt-0.5 shrink-0"></i>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-danger">Already recorded — this cannot be saved</p>
            <p id="dup-block-text" class="text-sm text-gray-700 dark:text-gray-200 mt-1"></p>
            <a id="dup-block-link" href="#" target="_blank" class="hidden text-sm font-medium text-primary hover:underline mt-1 inline-block"></a>
        </div>
    </div>

    {{-- Possible matches: check, then confirm --}}
    <div id="dup-warn" class="hidden p-4 rounded-lg bg-warning/10 border border-warning/40 flex gap-3">
        <i class="mgc_alert_line text-warning text-xl mt-0.5 shrink-0"></i>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Possible duplicate — please check before saving</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">These records look like the same person. Open them to compare.</p>
            <ul id="dup-list" class="mt-2 space-y-1.5"></ul>
            @if($gate)
            <label class="mt-3 inline-flex items-start gap-2 cursor-pointer text-sm font-medium text-gray-800 dark:text-gray-100">
                <input type="checkbox" name="confirm_duplicate" id="dup-confirm" value="1" class="form-checkbox rounded mt-0.5" @checked(old('confirm_duplicate'))>
                <span>This is a different person — save anyway</span>
            </label>
            @endif
        </div>
    </div>
</div>

<script>
// Runs once the whole form is on the page (this sits above its fields).
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('dup-check');
    const form = box.closest('form');
    if (!form) return;
    const f = n => form.querySelector('[name="' + n + '"]');
    const fname = f('fname'), lname = f('lname'), bday = f('bday'), mname = f('mname');
    const gate = box.dataset.gate === '1';
    const block = document.getElementById('dup-block');
    const warn  = document.getElementById('dup-warn');
    const list  = document.getElementById('dup-list');
    let lastKey = '', timer = null, ctrl = null;

    const STATUS_CLASS = {
        active:     'bg-primary/10 text-primary',
        archived:   'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        walkin:     'bg-warning/20 text-warning',
        other_zone: 'bg-info/10 text-info',
    };

    function render(res) {
        list.replaceChildren();
        block.classList.toggle('hidden', !res.block);
        if (res.block) {
            document.getElementById('dup-block-text').textContent = res.block;
            const a = document.getElementById('dup-block-link');
            a.classList.toggle('hidden', !res.exact?.url);
            if (res.exact?.url) {
                a.href = res.exact.url;
                a.textContent = (res.exact.status === 'archived' ? 'Open the Archive' : 'Open their record') + ' →';
            }
        }
        const show = !res.block && res.similar.length > 0;
        warn.classList.toggle('hidden', !show);
        if (show) {
            res.similar.forEach(m => {
                const li = document.createElement('li');
                li.className = 'text-sm text-gray-700 dark:text-gray-200 flex flex-wrap items-center gap-x-2 gap-y-1';
                const name = document.createElement(m.url ? 'a' : 'span');
                name.className = 'font-semibold' + (m.url ? ' text-primary hover:underline' : '');
                if (m.url) { name.href = m.url; name.target = '_blank'; }
                name.textContent = m.name;
                const meta = document.createElement('span');
                meta.className = 'text-xs text-gray-500 dark:text-gray-400';
                meta.textContent = m.code + ' · born ' + m.bday + ' · ' + m.reason;
                const badge = document.createElement('span');
                badge.className = 'text-xs font-medium px-2 py-0.5 rounded-full ' + (STATUS_CLASS[m.status] || '');
                badge.textContent = m.label;
                li.append(name, meta, badge);
                list.append(li);
            });
        }
        box.classList.toggle('hidden', !res.block && !show);
    }

    function check() {
        const v = [fname?.value.trim() || '', lname?.value.trim() || '', bday?.value || '', mname?.value.trim() || ''];
        if (!v[0] || !v[1]) { render({ block: null, similar: [] }); lastKey = ''; return; }
        const key = v.join('|');
        if (key === lastKey) return;
        lastKey = key;
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const q = new URLSearchParams({ fname: v[0], lname: v[1], bday: v[2], mname: v[3], except: box.dataset.except });
        fetch(box.dataset.url + '?' + q, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, signal: ctrl.signal })
            .then(r => r.ok ? r.json() : null)
            .then(res => { if (res) render(res); })
            .catch(() => {});
    }

    [fname, lname, bday, mname].forEach(el => {
        if (!el) return;
        el.addEventListener('change', check);
        el.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(check, 600); });
    });
    check();   // e.g. coming back after a failed save, or opening Edit

    // Stop the save early (before a photo would have to be re-attached).
    form.addEventListener('submit', e => {
        const blocked = !block.classList.contains('hidden');
        const needTick = gate && !warn.classList.contains('hidden') && !document.getElementById('dup-confirm')?.checked;
        if (blocked || needTick) {
            e.preventDefault();
            e.stopImmediatePropagation();
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'warning', showConfirmButton: false, timer: 3500,
                title: blocked ? 'This person is already recorded' : 'Possible duplicate — tick "This is a different person" to save' });
        }
    }, true);

    // Register Minor saves with fetch, not a form submit — expose the same guard.
    window.dupCheckBlocksSave = () => {
        const blocked = !block.classList.contains('hidden');
        const needTick = gate && !warn.classList.contains('hidden') && !document.getElementById('dup-confirm')?.checked;
        if (blocked || needTick) {
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return blocked ? 'This person is already recorded' : 'Possible duplicate — tick "This is a different person" to continue';
        }
        return null;
    };
});
</script>
