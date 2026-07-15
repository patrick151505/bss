<script>
const CITIZEN_SEARCH_URL = '{{ route('citizens.search') }}';

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Re-index all name attributes in a role container so they are sequential
function reindexRows(role) {
    document.querySelectorAll('#' + role + '-rows .party-row').forEach((row, i) => {
        row.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
        });
        // first row: hide remove btn; others: show it
        const btn = row.querySelector('.remove-party-btn');
        if (btn) btn.classList.toggle('hidden', i === 0);
    });
}

// Returns a Set of all citizen IDs currently selected across both sides,
// excluding the one already held by `excludeField` (the row being edited).
function getSelectedCitizenIds(excludeField) {
    const ids = new Set();
    document.querySelectorAll('.citizen-id-input').forEach(f => {
        if (f !== excludeField && f.value) ids.add(String(f.value));
    });
    return ids;
}

function setupRowLookup(row, role) {
    const input    = row.querySelector('.citizen-search');
    const dd       = row.querySelector('.citizen-dd');
    const chip     = row.querySelector('.citizen-chip');
    const chipName = row.querySelector('.citizen-chip-name');
    const clearBtn = row.querySelector('.citizen-clear');
    const idField  = row.querySelector('.citizen-id-input');
    const nameF    = row.querySelector('.party-name');
    const addrF    = row.querySelector('.party-address');
    const conF     = row.querySelector('.party-contact');
    let timer;

    function lock(on) {
        [nameF, addrF, conF].forEach(f => {
            if (!f) return;
            f.readOnly = on;
            f.classList.toggle('bg-gray-50', on);
            f.classList.toggle('text-gray-500', on);
        });
    }

    function select(c) {
        const taken = getSelectedCitizenIds(idField);
        if (taken.has(String(c.id))) {
            Swal.fire({
                icon: 'warning',
                title: 'Already Added',
                text: `${c.name} is already selected as a party in this blotter.`,
                confirmButtonColor: '#4361ee',
            });
            input.value = '';
            dd.classList.add('hidden');
            return;
        }
        idField.value        = c.id;
        if (nameF) nameF.value = c.name;
        if (addrF) addrF.value = c.address || '';
        if (conF)  conF.value  = c.contact  || '';
        lock(true);
        chipName.textContent = c.name;
        chip.classList.remove('hidden');
        input.classList.add('hidden');
        dd.classList.add('hidden');
        input.value = '';
    }

    function clear() {
        idField.value = '';
        lock(false);
        chip.classList.add('hidden');
        input.classList.remove('hidden');
        input.focus();
    }

    function showDd(items) {
        if (!items.length) {
            dd.innerHTML = '<div class="px-3 py-2.5 text-sm text-gray-400 italic">No citizens found</div>';
        } else {
            const taken = getSelectedCitizenIds(idField);
            dd.innerHTML = items.map(c => {
                const isDupe = taken.has(String(c.id));
                return `<div class="citizen-opt px-3 py-2.5 border-b border-gray-50 dark:border-gray-800 last:border-0
                              ${isDupe ? 'opacity-40 cursor-not-allowed' : 'hover:bg-primary/5 cursor-pointer'}"
                              data-id="${esc(c.id)}" data-name="${esc(c.name)}"
                              data-address="${esc(c.address)}" data-contact="${esc(c.contact)}"
                              data-dupe="${isDupe}">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${esc(c.name)}</p>
                    <p class="text-xs ${isDupe ? 'text-danger' : 'text-gray-400'} truncate">
                        ${isDupe ? 'Already added as a party' : esc(c.address || '—')}
                    </p>
                </div>`;
            }).join('');
            dd.querySelectorAll('.citizen-opt').forEach(opt => {
                opt.addEventListener('mousedown', e => {
                    e.preventDefault();
                    if (opt.dataset.dupe === 'true') return; // silently ignore clicks on dupes
                    select({ id: opt.dataset.id, name: opt.dataset.name, address: opt.dataset.address, contact: opt.dataset.contact });
                });
            });
        }
        dd.classList.remove('hidden');
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { dd.classList.add('hidden'); return; }
        timer = setTimeout(() => {
            fetch(CITIZEN_SEARCH_URL + '?q=' + encodeURIComponent(q))
                .then(r => r.json()).then(showDd)
                .catch(() => dd.classList.add('hidden'));
        }, 300);
    });
    input.addEventListener('blur', () => setTimeout(() => dd.classList.add('hidden'), 200));
    clearBtn.addEventListener('click', clear);

    // If already has citizen_id on load, lock fields
    if (idField.value) lock(true);
}

function addPartyRow(role) {
    const container = document.getElementById(role + '-rows');
    const existing  = container.querySelectorAll('.party-row');
    const idx       = existing.length;
    const color     = role === 'complainant' ? 'success' : 'danger';

    const div = document.createElement('div');
    div.className = 'party-row border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative';
    div.innerHTML = `
        <button type="button" class="remove-party-btn absolute top-3 right-3 text-gray-300 hover:text-danger" onclick="removePartyRow(this)">
            <i class="mgc_close_line text-sm"></i>
        </button>
        <input type="hidden" name="${role}s[${idx}][citizen_id]" class="citizen-id-input" value="">
        <div class="mb-3">
            <label class="form-label text-xs text-gray-400">Search registered citizen <span class="text-gray-300">(optional)</span></label>
            <div class="relative">
                <input type="text" class="form-input text-sm w-full citizen-search" placeholder="Type name to search…" autocomplete="off">
                <div class="citizen-dd hidden absolute top-full left-0 right-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-y-auto text-sm" style="z-index:9999;max-height:200px"></div>
            </div>
            <div class="citizen-chip hidden mt-2 flex items-center gap-2 bg-${color}/10 border border-${color}/30 rounded-lg px-3 py-2">
                <i class="mgc_check_circle_line text-${color} text-sm shrink-0"></i>
                <span class="citizen-chip-name text-sm text-${color} font-medium flex-1 truncate"></span>
                <button type="button" class="citizen-clear text-xs text-gray-400 hover:text-danger shrink-0">
                    <i class="mgc_close_line"></i> Clear
                </button>
            </div>
        </div>
        <div class="border-t border-gray-100 dark:border-gray-800 pt-3 grid grid-cols-1 gap-3">
            <div>
                <label class="form-label text-xs">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="${role}s[${idx}][name]" placeholder="Full name" class="form-input text-sm party-name" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label text-xs">Address</label>
                    <input type="text" name="${role}s[${idx}][address]" placeholder="Home address" class="form-input text-sm party-address">
                </div>
                <div>
                    <label class="form-label text-xs">Contact No.</label>
                    <input type="text" name="${role}s[${idx}][contact]" placeholder="09XXXXXXXXX" class="form-input text-sm party-contact">
                </div>
            </div>
        </div>`;

    container.appendChild(div);
    setupRowLookup(div, role);
    reindexRows(role);
}

function removePartyRow(btn) {
    const row  = btn.closest('.party-row');
    const role = row.closest('[id$="-rows"]').id.replace('-rows', '');
    row.remove();
    reindexRows(role);
}

// Boot: attach lookup to all existing rows on page load
document.addEventListener('DOMContentLoaded', function () {
    ['complainant', 'respondent'].forEach(role => {
        document.querySelectorAll('#' + role + '-rows .party-row').forEach(row => {
            setupRowLookup(row, role);
        });
        reindexRows(role);
    });
});

function toggleOtherType(val) {
    const wrap  = document.getElementById('type-other-wrap');
    const input = document.getElementById('type-other-input');
    const show  = val === 'other';
    wrap.classList.toggle('hidden', !show);
    input.required = show;
    if (show) input.focus();
    else input.value = '';
}
</script>
