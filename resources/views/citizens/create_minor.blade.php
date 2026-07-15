@extends('layouts.vertical', [
    'title'         => 'Register Minor',
    'sub_title'     => 'Citizens',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Register a child or minor citizen linked to an existing parent.',
])

@section('content')

{{-- ── Step indicator ─────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-2 mb-6">
    @foreach(['Find Parent','Personal Info','Review & Save'] as $i => $label)
    <div class="flex items-center gap-2 {{ $i > 0 ? 'flex-1' : '' }}">
        @if($i > 0)
        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 step-line" data-after="{{ $i }}"></div>
        @endif
        <div class="flex items-center gap-2 step-block" data-step="{{ $i + 1 }}">
            <span class="step-circle w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all
                         {{ $i === 0 ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                {{ $i + 1 }}
            </span>
            <span class="text-sm font-medium hidden sm:block
                         {{ $i === 0 ? 'text-primary' : 'text-gray-400' }}">{{ $label }}</span>
        </div>
    </div>
    @endforeach
</div>

<form action="{{ route('citizens.store-minor') }}" method="POST" enctype="multipart/form-data" id="minor-form">
@csrf

{{-- Hidden: parent data passed to server --}}
<input type="hidden" name="parent_id"   id="f-parent-id">
<input type="hidden" name="address"     id="f-address-id">

<div class="grid grid-cols-12 gap-6">

    {{-- ── Left / Main column ──────────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">

        {{-- ══ STEP 1: Find Parent ══════════════════════════════════════════ --}}
        <div id="step-1" class="step-panel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-primary/15 text-primary text-xs font-bold flex items-center justify-center">1</span>
                        Find Parent / Guardian
                    </h5>
                </div>
                <div class="p-6">

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Search for the parent who is already registered as a citizen. The minor will be assigned to the parent's household and family automatically.
                    </p>

                    <div class="relative mb-4">
                        <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        <input type="text" id="parent-search-input"
                               placeholder="Type parent's name…"
                               autocomplete="off"
                               class="form-input pl-9 w-full">
                    </div>

                    {{-- Search results --}}
                    <div id="parent-results" class="space-y-2 min-h-[60px]">
                        <p class="text-xs text-gray-400 text-center py-4">Start typing to search citizens…</p>
                    </div>

                    {{-- Selected parent card --}}
                    <div id="parent-selected" class="hidden mt-4 rounded-xl border-2 border-primary/30 bg-primary/5 p-4">
                        <div class="flex items-center gap-4">
                            <img id="ps-photo" src="" alt=""
                                 class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-gray-700 shadow shrink-0">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary mb-0.5">Selected Parent</p>
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm" id="ps-name">—</p>
                                <p class="text-xs text-gray-500" id="ps-meta">—</p>
                            </div>
                            <div id="ps-hh" class="text-right shrink-0">
                                <p class="text-xs font-semibold text-success" id="ps-hh-no">—</p>
                                <p class="text-xs text-gray-400" id="ps-address">—</p>
                            </div>
                            <button type="button" onclick="clearParent()"
                                    class="text-gray-400 hover:text-danger transition-colors shrink-0" title="Clear">
                                <i class="mgc_close_line text-lg"></i>
                            </button>
                        </div>
                        {{-- No household warning --}}
                        <div id="ps-no-hh-warn" class="hidden mt-3 rounded-lg bg-danger/10 border border-danger/30 px-3 py-2 text-xs text-danger flex items-center gap-2">
                            <i class="mgc_alert_line shrink-0"></i>
                            <span>This parent has no household or family assignment. Please assign the parent to a household first before registering a minor under them.</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ══ STEP 2: Personal Info ═════════════════════════════════════════ --}}
        <div id="step-2" class="step-panel hidden">
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center justify-between w-full">
                        <h5 class="card-title flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-primary/15 text-primary text-xs font-bold flex items-center justify-center">2</span>
                            Minor's Personal Information
                        </h5>
                        <span class="badge bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 text-xs font-semibold px-2 py-1 rounded-full flex items-center gap-1">
                            <i class="mgc_user_2_line text-xs"></i> Minor Registration
                        </span>
                    </div>
                </div>
                <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="fname" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="mname" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lname" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" class="form-input" placeholder="Jr., III">
                    </div>

                    <div>
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">— Select —</option>
                            <option value="1">Male</option>
                            <option value="0">Female</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Birthday <span class="text-danger">*</span></label>
                        <input type="date" name="bday" id="minor-bday"
                               class="form-input" max="{{ date('Y-m-d') }}" required>
                        <p id="age-display" class="text-xs text-gray-400 mt-1 hidden">Age: <span id="age-value" class="font-semibold text-primary"></span></p>
                        <p id="age-warn" class="text-xs text-danger mt-1 hidden">This person is 18 or older. Use the <a href="{{ route('citizens.create') }}" class="underline">regular registration</a> instead.</p>
                    </div>

                    <div>
                        <label class="form-label">Place of Birth <span class="text-danger">*</span></label>
                        <input type="text" name="bplace" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label">Citizenship <span class="text-danger">*</span></label>
                        <input type="text" name="citizenship" value="Filipino" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label">Relation to Parent <span class="text-danger">*</span></label>
                        <select name="relation_id" class="form-select" required>
                            <option value="">— Select —</option>
                            @foreach($relations as $rel)
                                <option value="{{ $rel->id }}" {{ old('relation_id') == $rel->id ? 'selected' : '' }}>
                                    {{ $rel->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('relation_id') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">PWD</label>
                        <select name="is_pwd" class="form-select">
                            <option value="0" {{ old('is_pwd', 0) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('is_pwd', 0) == 1 ? 'selected' : '' }}>Yes — PWD</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Approval Status</label>
                        <select name="approval_status" class="form-select">
                            @foreach($approvalStatuses as $as)
                                <option value="{{ $as->id }}" {{ old('approval_status', 2) == $as->id ? 'selected' : '' }}>
                                    {{ $as->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="form-label">Notes</label>
                        <textarea name="note" rows="2"
                                  class="form-input w-full">{{ old('note') }}</textarea>
                    </div>

                </div>
            </div>

            {{-- Auto-filled address (read-only display) --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title flex items-center gap-2">
                        <i class="mgc_home_3_line text-primary"></i>
                        Address
                        <span class="text-xs font-normal text-gray-400 ml-1">— inherited from parent's household</span>
                    </h5>
                </div>
                <div class="p-6">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center gap-3">
                        <i class="mgc_home_3_line text-success text-xl shrink-0"></i>
                        <div>
                            <p class="text-xs text-gray-400">Household Address</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="review-address-display">—</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="mgc_information_line"></i>
                        The minor's address is automatically set to the parent's household address and cannot be changed here.
                    </p>
                </div>
            </div>
        </div>

        {{-- ══ STEP 3: Review ═══════════════════════════════════════════════ --}}
        <div id="step-3" class="step-panel hidden">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-primary/15 text-primary text-xs font-bold flex items-center justify-center">3</span>
                        Review Before Saving
                    </h5>
                </div>
                <div class="p-6 space-y-4">

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 flex items-center gap-1">
                                <i class="mgc_user_3_line"></i> Parent
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-100 text-sm" id="r-parent-name">—</p>
                            <p class="text-xs text-gray-500 mt-0.5" id="r-parent-hh">—</p>
                            <p class="text-xs text-gray-500" id="r-parent-address">—</p>
                        </div>
                        <div class="rounded-xl bg-primary/5 border border-primary/20 p-4">
                            <p class="text-xs font-semibold text-primary uppercase tracking-wide mb-2 flex items-center gap-1">
                                <i class="mgc_user_2_line"></i> Minor Being Registered
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-100 text-sm" id="r-minor-name">—</p>
                            <p class="text-xs text-gray-500 mt-0.5" id="r-minor-meta">—</p>
                        </div>
                    </div>

                    {{-- Auto-applied flags --}}
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 p-4">
                        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide mb-2">Applied automatically for minors</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <i class="mgc_close_line text-danger text-xs"></i> Voter = No
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <i class="mgc_close_line text-danger text-xs"></i> Solo Parent = No
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <i class="mgc_close_line text-danger text-xs"></i> Owner = Renter
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <i class="mgc_check_line text-success text-xs"></i> Civil Status = Single
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <i class="mgc_check_line text-success text-xs"></i> Joined parent's family
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- ── Right column ──────────────────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

        {{-- Profile Photo --}}
        <div id="photo-panel" class="card hidden">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_pic_line me-2 text-primary"></i>Profile Photo</h5>
            </div>
            <div class="p-6 flex flex-col items-center gap-4">
                <div id="profile-placeholder"
                     class="w-28 h-28 rounded-full bg-primary/20 flex items-center justify-center text-primary shadow">
                    <i class="mgc_user_3_line text-4xl"></i>
                </div>
                <img id="profile-preview" src="" class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow hidden" alt="Profile">
                <label for="profile-input" class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer w-full text-center">
                    <i class="mgc_upload_2_line me-1"></i> Upload Photo
                </label>
                <input type="file" id="profile-input" name="profile"
                       class="hidden" accept="image/jpeg,image/png,image/jpg">
                <p class="text-xs text-gray-400 text-center">JPG or PNG. Max 2MB.</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card">
            <div class="p-6 flex flex-col gap-3">

                <button type="button" id="btn-next" onclick="stepNext()"
                        class="btn bg-primary text-white w-full flex items-center justify-center gap-2">
                    Next <i class="mgc_right_line"></i>
                </button>

                <button type="button" id="btn-submit" onclick="submitMinor()"
                        class="hidden btn bg-success text-white w-full flex items-center justify-center gap-2">
                    <i class="mgc_user_add_line"></i> Register Minor
                </button>

                <button type="button" id="btn-back" onclick="stepBack()"
                        class="hidden btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 w-full text-center">
                    <i class="mgc_left_line me-1"></i> Back
                </button>

                <a href="{{ route('citizens.index') }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 w-full text-center">
                    <i class="mgc_arrow_left_line me-1"></i> Cancel
                </a>
            </div>
        </div>

        {{-- Info box --}}
        <div class="card bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700">
            <div class="p-4 space-y-2">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 flex items-center gap-1">
                    <i class="mgc_information_line"></i> Minor Registration Rules
                </p>
                <ul class="text-xs text-blue-600 dark:text-blue-400 space-y-1 list-disc list-inside">
                    <li>Must be under 18 years old</li>
                    <li>Parent must already be registered</li>
                    <li>Parent must be assigned to a household & family</li>
                    <li>Address is inherited from parent's household</li>
                    <li>Voter, Solo Parent, Owner flags are locked</li>
                </ul>
            </div>
        </div>

    </div>

</div>
</form>

@endsection

@section('script')
<script>
const PARENT_SEARCH_URL = '{{ route('citizens.parent-search') }}';
const STORE_URL         = '{{ route('citizens.store-minor') }}';
const CSRF              = '{{ csrf_token() }}';

let currentStep    = 1;
let selectedParent = null;

// ── Step navigation ──────────────────────────────────────────────────────────

function updateStepUI() {
    // Show/hide panels
    document.querySelectorAll('.step-panel').forEach((el, i) => {
        el.classList.toggle('hidden', i + 1 !== currentStep);
    });

    // Update step indicator circles
    document.querySelectorAll('.step-circle').forEach((el, i) => {
        const active = i + 1 === currentStep;
        const done   = i + 1 < currentStep;
        el.className = el.className.replace(/bg-\S+|text-\S+/g, '').trim();
        if (done) {
            el.classList.add('bg-success', 'text-white');
            el.innerHTML = '<i class="mgc_check_line text-xs"></i>';
        } else if (active) {
            el.classList.add('bg-primary', 'text-white');
            el.textContent = i + 1;
        } else {
            el.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-400');
            el.textContent = i + 1;
        }
    });

    // Step label colors
    document.querySelectorAll('.step-block span:last-child').forEach((el, i) => {
        el.className = el.className.replace(/text-\S+/g, '').trim();
        el.classList.add(i + 1 === currentStep ? 'text-primary' : 'text-gray-400');
    });

    // Photo panel
    document.getElementById('photo-panel').classList.toggle('hidden', currentStep < 2);

    // Buttons
    document.getElementById('btn-next').classList.toggle('hidden', currentStep === 3);
    document.getElementById('btn-submit').classList.toggle('hidden', currentStep !== 3);
    document.getElementById('btn-back').classList.toggle('hidden', currentStep === 1);
}

function stepNext() {
    if (currentStep === 1) {
        if (!selectedParent) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please select a parent first', showConfirmButton: false, timer: 2000 });
            return;
        }
        if (!selectedParent.has_family) {
            Swal.fire({
                icon: 'error',
                title: 'Parent Has No Household',
                html: 'The selected parent <strong>' + selectedParent.name + '</strong> is not assigned to any household or family.<br><br>Please assign the parent to a household first, then return to register the minor.',
                confirmButtonText: 'OK, I understand',
            });
            return;
        }
        currentStep = 2;
        updateStepUI();
        return;
    }

    if (currentStep === 2) {
        // Basic validation before moving to review
        const fname = document.querySelector('[name="fname"]').value.trim();
        const lname = document.querySelector('[name="lname"]').value.trim();
        const gender = document.querySelector('[name="gender"]').value;
        const bday   = document.querySelector('[name="bday"]').value;
        const bplace = document.querySelector('[name="bplace"]').value.trim();
        const relation = document.querySelector('[name="relation_id"]').value;

        if (!fname || !lname || !gender || !bday || !bplace || !relation) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please fill all required fields', showConfirmButton: false, timer: 2000 });
            return;
        }

        if (document.getElementById('age-warn').classList.contains('hidden') === false) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Age must be under 18', showConfirmButton: false, timer: 2500 });
            return;
        }

        // Populate review step
        const relEl   = document.querySelector('[name="relation_id"]');
        const relText = relEl.options[relEl.selectedIndex]?.text ?? '';
        const age     = document.getElementById('age-value').textContent;

        document.getElementById('r-parent-name').textContent    = selectedParent.name;
        document.getElementById('r-parent-hh').textContent      = selectedParent.hh_no ?? 'No household number';
        document.getElementById('r-parent-address').textContent = selectedParent.address ?? '—';
        document.getElementById('r-minor-name').textContent     = lname + ', ' + fname;
        document.getElementById('r-minor-meta').textContent     = (gender === '1' ? 'Male' : 'Female') + ' · Age ' + age + ' · ' + relText + ' of parent';

        currentStep = 3;
        updateStepUI();
        return;
    }
}

function stepBack() {
    if (currentStep > 1) {
        currentStep--;
        updateStepUI();
    }
}

// ── Parent search ────────────────────────────────────────────────────────────

let searchTimer;
document.getElementById('parent-search-input').addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    const box = document.getElementById('parent-results');

    if (q.length < 2) {
        box.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Start typing to search citizens…</p>';
        return;
    }

    box.innerHTML = '<p class="text-xs text-gray-400 text-center py-4 animate-pulse">Searching…</p>';

    searchTimer = setTimeout(() => {
        fetch(PARENT_SEARCH_URL + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    box.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">No citizens found.</p>';
                    return;
                }
                box.innerHTML = '';
                data.forEach(p => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full text-left rounded-xl border-2 transition-all flex items-center gap-3 p-3 ' +
                        (p.has_family
                            ? 'border-gray-200 dark:border-gray-700 hover:border-primary/50 hover:bg-primary/5'
                            : 'border-gray-200 dark:border-gray-700 opacity-60');

                    const photoSrc = p.profile
                        ? `<img src="${p.profile}" class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0">`
                        : `<span class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-sm font-bold">${p.name.charAt(0)}</span>`;

                    const hhBadge = p.has_family
                        ? `<span class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-success/10 text-success font-semibold"><i class="mgc_home_3_line"></i> ${p.hh_no}</span>`
                        : `<span class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-danger/10 text-danger font-semibold"><i class="mgc_alert_line"></i> No household</span>`;

                    btn.innerHTML = `
                        ${photoSrc}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">${p.name}</p>
                            <p class="text-xs text-gray-400">${p.zone} · Age ${p.age ?? '?'}</p>
                        </div>
                        ${hhBadge}
                    `;

                    btn.addEventListener('click', () => selectParent(p));
                    box.appendChild(btn);
                });
            });
    }, 350);
});

function selectParent(p) {
    selectedParent = p;

    // Fill hidden fields
    document.getElementById('f-parent-id').value  = p.id;
    document.getElementById('f-address-id').value = p.address_id ?? '';

    // Show selected card
    const photoEl = document.getElementById('ps-photo');
    photoEl.src   = p.profile ?? '{{ asset("images/default-avatar.png") }}';

    document.getElementById('ps-name').textContent    = p.name;
    document.getElementById('ps-meta').textContent    = (p.zone ?? '') + ' · Age ' + (p.age ?? '?');
    document.getElementById('ps-hh-no').textContent   = p.hh_no ?? 'No household';
    document.getElementById('ps-address').textContent = p.address ?? '—';

    document.getElementById('review-address-display').textContent = p.address ?? '—';

    const warnEl = document.getElementById('ps-no-hh-warn');
    warnEl.classList.toggle('hidden', p.has_family);

    document.getElementById('parent-results').innerHTML = '';
    document.getElementById('parent-search-input').value = '';
    document.getElementById('parent-selected').classList.remove('hidden');
}

function clearParent() {
    selectedParent = null;
    document.getElementById('f-parent-id').value  = '';
    document.getElementById('f-address-id').value = '';
    document.getElementById('parent-selected').classList.add('hidden');
    document.getElementById('parent-results').innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Start typing to search citizens…</p>';
}

// ── Age check on birthday input ───────────────────────────────────────────────

document.getElementById('minor-bday').addEventListener('change', function () {
    const val = this.value;
    if (!val) return;

    const today = new Date();
    const bday  = new Date(val);
    let age = today.getFullYear() - bday.getFullYear();
    const m = today.getMonth() - bday.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) age--;

    const display = document.getElementById('age-display');
    const warn    = document.getElementById('age-warn');

    document.getElementById('age-value').textContent = age;
    display.classList.remove('hidden');

    if (age >= 18) {
        warn.classList.remove('hidden');
        display.classList.add('hidden');
    } else {
        warn.classList.add('hidden');
    }
});

// ── Profile photo preview ─────────────────────────────────────────────────────

document.getElementById('profile-input').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('profile-preview').src = e.target.result;
        document.getElementById('profile-preview').classList.remove('hidden');
        document.getElementById('profile-placeholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);
});

// ── Field error helpers ───────────────────────────────────────────────────────

function clearAllErrors() {
    document.querySelectorAll('.field-error').forEach(el => el.remove());
    document.querySelectorAll('.border-danger').forEach(el => el.classList.remove('border-danger'));
}

function showFieldError(name, message) {
    const input = document.querySelector(`[name="${name}"]`);
    if (!input) return;
    input.classList.add('border-danger');
    const existing = input.parentElement.querySelector('.field-error');
    if (existing) existing.remove();
    const p = document.createElement('p');
    p.className = 'field-error text-danger text-xs mt-1';
    p.textContent = message;
    input.parentElement.appendChild(p);
}

// ── AJAX submit ───────────────────────────────────────────────────────────────

function submitMinor() {
    clearAllErrors();

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Saving…';

    const fd = new FormData(document.getElementById('minor-form'));

    fetch(STORE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: fd,
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Registered!',
                text: data.message,
                timer: 1800,
                showConfirmButton: false,
                timerProgressBar: true,
            }).then(() => { window.location.href = data.redirect; });
            return;
        }

        // Server returned errors
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_user_add_line"></i> Register Minor';

        // General message (no fields — e.g. parent has no household)
        if (data.message && !data.errors) {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Register',
                text: data.message,
                confirmButtonText: 'OK',
            });
            return;
        }

        // Field-level errors — go back to step 2 to show them
        if (data.errors) {
            const fieldNames = Object.keys(data.errors);

            // If any error is on a step-2 field, navigate back to step 2
            const step2Fields = ['fname','lname','mname','suffix','gender','bday','bplace','citizenship','relation_id','is_pwd','note'];
            const hasStep2Error = fieldNames.some(f => step2Fields.includes(f));
            if (hasStep2Error && currentStep === 3) {
                currentStep = 2;
                updateStepUI();
            }

            fieldNames.forEach(field => {
                const messages = data.errors[field];
                showFieldError(field, messages[0]);
            });

            // Also show a Swal toast summary
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Please fix the highlighted fields',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="mgc_user_add_line"></i> Register Minor';
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Network error — please try again', showConfirmButton: false, timer: 3000 });
    });
}

// ── Init
updateStepUI();
</script>
@endsection
