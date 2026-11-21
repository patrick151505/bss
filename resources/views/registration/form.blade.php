@extends('registration._shell', ['pageTitle' => 'Citizen Registration'])

{{--
    Public self-registration as a step-by-step wizard with large text and fields,
    so senior citizens and first-time phone users can fill it in comfortably.
    Each step is checked before moving on; the last step shows everything for review.
--}}

@php
    $err = fn($f) => $errors->has($f) ? ' is-invalid' : '';
    $steps = [
        1 => ['icon' => 'mgc_user_3_line',  'title' => 'About you'],
        2 => ['icon' => 'mgc_home_3_line',  'title' => 'Address'],
        3 => ['icon' => 'mgc_phone_line',   'title' => 'Contact'],
        4 => ['icon' => 'mgc_heart_line',   'title' => 'Groups & proof'],
        5 => ['icon' => 'mgc_pic_line',     'title' => 'Photo & emergency'],
        6 => ['icon' => 'mgc_check_line',   'title' => 'Review'],
    ];
    $groups = [
        'is_senior'      => ['key' => 'senior', 'title' => 'Senior citizen (60 and above)', 'proof' => 'Senior Citizen ID'],
        'is_pwd'         => ['key' => 'pwd',    'title' => 'Person with disability (PWD)',  'proof' => 'PWD ID'],
        'is_soloparents' => ['key' => 'solo',   'title' => 'Solo parent',                   'proof' => 'Solo Parent ID'],
    ];
@endphp

@section('body')
<style>
    /* Big, easy-to-read form */
    .reg { font-size: 1.125rem; }
    .reg .r-label { display:block; font-size:1.05rem; font-weight:600; margin-bottom:.4rem; }
    .reg .r-hint { font-size:.95rem; }
    .reg .r-input { width:100%; font-size:1.15rem; padding:.85rem 1rem; min-height:3.4rem; border-radius:.6rem; }
    .reg textarea.r-input { min-height:5rem; }
    .reg .is-invalid { border-color:#fa5c7c !important; box-shadow:0 0 0 2px rgba(250,92,124,.2); }
    .reg .r-btn { font-size:1.15rem; padding:.9rem 1.6rem; border-radius:.7rem; min-height:3.4rem; }
    .reg .r-choice { display:flex; align-items:center; gap:.9rem; padding:1rem 1.1rem; border:2px solid rgba(148,163,184,.4); border-radius:.8rem; cursor:pointer; }
    .reg .r-choice:has(input:checked) { border-color:#3e60d5; background:rgba(62,96,213,.08); }
    .reg .r-choice input[type=checkbox], .reg .r-choice input[type=radio] { width:1.6rem; height:1.6rem; flex-shrink:0; }
    .reg .r-drop { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.4rem; min-height:10rem;
                   border:2px dashed rgba(148,163,184,.6); border-radius:.8rem; cursor:pointer; text-align:center; padding:.8rem; overflow:hidden; position:relative; }
    .reg .r-drop img { position:absolute; inset:0; width:100%; height:100%; object-fit:contain; background:#fff; }
    .reg .r-drop.has-file { border-style:solid; border-color:#0acf97; }
    .reg .r-step { display:none; }
    .reg .r-step.is-active { display:block; }
</style>

<div class="reg">
    <div class="mb-5">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Citizen Registration</h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
            Register as a resident in a few easy steps. Barangay staff check every registration before approving it.
            Items marked <span class="text-danger font-bold">*</span> are required.
        </p>
    </div>

    {{-- Progress --}}
    <div class="card p-4 mb-5">
        <div class="flex items-center justify-between mb-3">
            <p class="font-semibold text-gray-800 dark:text-gray-100">
                Step <span id="step-no">1</span> of {{ count($steps) }} — <span id="step-title">{{ $steps[1]['title'] }}</span>
            </p>
        </div>
        <div class="w-full h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <div id="step-bar" class="h-full bg-primary rounded-full transition-all" style="width:{{ 100 / count($steps) }}%"></div>
        </div>
        <div class="hidden sm:grid grid-cols-6 gap-1 mt-3 text-center">
            @foreach($steps as $n => $st)
            <div class="step-dot text-xs text-gray-400" data-dot="{{ $n }}">
                <i class="{{ $st['icon'] }} text-xl block"></i>{{ $st['title'] }}
            </div>
            @endforeach
        </div>
    </div>

    @if($errors->any())
    <div class="mb-5 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
        <i class="mgc_alert_line text-danger text-2xl shrink-0"></i>
        <div>
            <p class="font-semibold text-danger mb-1">Please check the following:</p>
            <ul class="list-disc list-inside text-danger/90 space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">Pictures you chose before need to be added again.</p>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('register.citizen.store', $link->token) }}" enctype="multipart/form-data" id="reg-form" novalidate>
        @csrf
        <div style="position:absolute;left:-9999px" aria-hidden="true">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        {{-- ── 1. About you ── --}}
        <div class="r-step card p-6 space-y-5" data-step="1">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">About you</h2>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="r-label">First name <span class="text-danger">*</span></label>
                    <input type="text" name="fname" value="{{ old('fname') }}" maxlength="250" required autocomplete="given-name" class="form-input r-input{{ $err('fname') }}">
                </div>
                <div>
                    <label class="r-label">Middle name</label>
                    <input type="text" name="mname" value="{{ old('mname') }}" maxlength="250" autocomplete="additional-name" class="form-input r-input">
                </div>
                <div>
                    <label class="r-label">Last name <span class="text-danger">*</span></label>
                    <input type="text" name="lname" value="{{ old('lname') }}" maxlength="250" required autocomplete="family-name" class="form-input r-input{{ $err('lname') }}">
                </div>
                <div>
                    <label class="r-label">Suffix <span class="r-hint text-gray-400 font-normal">(Jr., Sr., III)</span></label>
                    <input type="text" name="suffix" value="{{ old('suffix') }}" maxlength="50" class="form-input r-input">
                </div>
            </div>

            <div>
                <p class="r-label">Sex <span class="text-danger">*</span></p>
                <div class="grid grid-cols-2 gap-3 max-w-md">
                    <label class="r-choice"><input type="radio" name="gender" value="1" required @checked(old('gender') === '1')> Male</label>
                    <label class="r-choice"><input type="radio" name="gender" value="0" required @checked(old('gender') === '0')> Female</label>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="r-label">Birthday <span class="text-danger">*</span></label>
                    <input type="date" name="bday" id="bday" value="{{ old('bday') }}" min="1900-01-01" max="{{ now()->subDay()->format('Y-m-d') }}" required class="form-input r-input{{ $err('bday') }}">
                    <p id="age-note" class="r-hint text-gray-500 mt-1"></p>
                </div>
                <div>
                    <label class="r-label">Civil status <span class="text-danger">*</span></label>
                    <select name="civil_status" required class="form-select r-input{{ $err('civil_status') }}">
                        <option value="">— Choose —</option>
                        @foreach($civilStatuses as $cs)
                        <option value="{{ $cs->id }}" @selected(old('civil_status') == $cs->id)>{{ $cs->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="r-label">Birthplace <span class="text-danger">*</span></label>
                    <input type="text" name="bplace" value="{{ old('bplace') }}" maxlength="250" required placeholder="Town / City" class="form-input r-input{{ $err('bplace') }}">
                </div>
                <div>
                    <label class="r-label">Citizenship <span class="text-danger">*</span></label>
                    <input type="text" name="citizenship" value="{{ old('citizenship', 'Filipino') }}" maxlength="250" required class="form-input r-input{{ $err('citizenship') }}">
                </div>
                <div class="md:col-span-2">
                    <label class="r-label">Occupation / work</label>
                    <input type="text" name="occupation" value="{{ old('occupation') }}" maxlength="250" placeholder="e.g. Vendor, Retired, Student" class="form-input r-input">
                </div>
            </div>
        </div>

        {{-- ── 2. Address ── --}}
        <div class="r-step card p-6 space-y-5" data-step="2">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Where do you live?</h2>
            <div>
                <label class="r-label">Zone / Area <span class="text-danger">*</span></label>
                <select name="address" id="address-select" required class="form-select r-input{{ $err('address') }}">
                    <option value="">— Choose your zone —</option>
                    @foreach($addresses as $a)
                    <option value="{{ $a->id }}" data-subd="{{ $a->is_subd }}" data-rules="{{ $a->rules_required }}" @selected(old('address') == $a->id)>{{ $a->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid md:grid-cols-3 gap-5">
                <div id="f-blk" class="hidden"><label class="r-label">Block No.</label><input type="number" name="blk" value="{{ old('blk') }}" min="1" inputmode="numeric" class="form-input r-input{{ $err('blk') }}"></div>
                <div id="f-lot" class="hidden"><label class="r-label">Lot No.</label><input type="number" name="lot" value="{{ old('lot') }}" min="1" inputmode="numeric" class="form-input r-input{{ $err('lot') }}"></div>
                <div id="f-phase" class="hidden"><label class="r-label">Phase</label><input type="number" name="phase" value="{{ old('phase') }}" min="1" inputmode="numeric" class="form-input r-input{{ $err('phase') }}"></div>
                <div id="f-no" class="hidden"><label class="r-label">House No.</label><input type="text" name="no" value="{{ old('no') }}" maxlength="50" class="form-input r-input{{ $err('no') }}"></div>
                <div id="f-street" class="hidden md:col-span-2"><label class="r-label">Street</label><input type="text" name="street" value="{{ old('street') }}" maxlength="250" class="form-input r-input{{ $err('street') }}"></div>
                <div id="f-complete_address" class="hidden md:col-span-3"><label class="r-label">Complete address</label><textarea name="complete_address" rows="2" maxlength="500" class="form-input r-input">{{ old('complete_address') }}</textarea></div>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="r-label">Living here since <span class="text-danger">*</span></label>
                    <input type="month" name="year_stay" value="{{ old('year_stay') }}" max="{{ now()->format('Y-m') }}" required class="form-input r-input{{ $err('year_stay') }}">
                    <p class="r-hint text-gray-500 mt-1">Month and year you started living in the barangay.</p>
                </div>
            </div>
            <div>
                <p class="r-label">Do you own the house? <span class="text-danger">*</span></p>
                <div class="grid sm:grid-cols-2 gap-3 max-w-xl">
                    <label class="r-choice"><input type="radio" name="owner_status" value="1" required @checked(old('owner_status') === '1')> Yes, owner</label>
                    <label class="r-choice"><input type="radio" name="owner_status" value="0" required @checked(old('owner_status') === '0')> No, renting / other</label>
                </div>
            </div>
        </div>

        {{-- ── 3. Contact ── --}}
        <div class="r-step card p-6 space-y-5" data-step="3">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">How can we reach you?</h2>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="r-label">Mobile number <span class="text-danger">*</span></label>
                    <input type="tel" name="contact" value="{{ old('contact') }}" maxlength="11" inputmode="numeric" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX" required class="form-input r-input{{ $err('contact') }}">
                    <p class="r-hint text-gray-500 mt-1">11 digits, starting with 09.</p>
                </div>
                <div>
                    <label class="r-label">Email <span class="r-hint text-gray-400 font-normal">(if any)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="250" class="form-input r-input{{ $err('email') }}">
                </div>
            </div>
            <div>
                <label class="r-choice max-w-xl">
                    <input type="hidden" name="voters" value="0">
                    <input type="checkbox" id="cb-voters" name="voters" value="1" @checked(old('voters') == '1')>
                    I am a registered voter in this barangay
                </label>
                <div id="f-precinct" class="mt-4 max-w-sm {{ old('voters') == '1' ? '' : 'hidden' }}">
                    <label class="r-label">Precinct No. <span class="text-danger">*</span></label>
                    <input type="text" name="pricinct_no" value="{{ old('pricinct_no') }}" minlength="5" maxlength="6" placeholder="e.g. 0055F" class="form-input r-input{{ $err('pricinct_no') }}">
                </div>
            </div>
        </div>

        {{-- ── 4. Groups & proof ── --}}
        <div class="r-step card p-6 space-y-5" data-step="4">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Are you part of any of these groups?</h2>
            <p class="text-gray-600 dark:text-gray-300">Tick all that apply. For each one, add a clear picture of the <strong>front and back</strong> of your ID. Skip this step if none apply.</p>

            @foreach($groups as $flag => $g)
            <div class="group-box rounded-xl border border-gray-200 dark:border-gray-700 p-4 {{ $flag === 'is_senior' ? 'hidden' : '' }}" id="grp-{{ $g['key'] }}">
                <label class="r-choice">
                    <input type="hidden" name="{{ $flag }}" value="0">
                    <input type="checkbox" class="grp-cb" data-key="{{ $g['key'] }}" name="{{ $flag }}" value="1" @checked(old($flag) == '1')>
                    <span class="font-semibold">{{ $g['title'] }}</span>
                </label>
                <div class="proof hidden mt-4" data-proof="{{ $g['key'] }}">
                    <p class="r-hint text-gray-600 dark:text-gray-300 mb-3">Upload your <strong>{{ $g['proof'] }}</strong> — both sides:</p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach(['front' => 'Front', 'back' => 'Back'] as $side => $sideLabel)
                        @php $field = "proof_{$g['key']}_{$side}"; @endphp
                        <label class="r-drop{{ $err($field) }}">
                            <input type="file" name="{{ $field }}" accept="image/*" class="hidden img-input" data-label="{{ $sideLabel }} of {{ $g['proof'] }}">
                            <i class="mgc_camera_line text-4xl text-primary"></i>
                            <span class="font-semibold">{{ $sideLabel }} side <span class="text-danger">*</span></span>
                            <span class="r-hint text-gray-500">Tap to take a photo or choose a picture</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── 5. Photo & emergency contact ── --}}
        <div class="r-step card p-6 space-y-6" data-step="5">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Your photo <span class="text-base font-normal text-gray-400">(optional)</span></h2>
                <p class="text-gray-600 dark:text-gray-300 mb-3">A clear photo of your face, looking at the camera. Used for your barangay records and ID.</p>
                <label class="r-drop max-w-xs" style="min-height:14rem">
                    <input type="file" name="photo" accept="image/*" class="hidden img-input" data-label="Your photo">
                    <i class="mgc_user_3_line text-5xl text-primary"></i>
                    <span class="font-semibold">Add your photo</span>
                    <span class="r-hint text-gray-500">Tap to take or choose a picture</span>
                </label>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Emergency contact <span class="text-base font-normal text-gray-400">(optional)</span></h2>
                <p class="text-gray-600 dark:text-gray-300 mb-3">Someone we can call if something happens to you.</p>
                <div class="grid md:grid-cols-2 gap-5">
                    <div><label class="r-label">Full name</label><input type="text" name="ic_fullname" value="{{ old('ic_fullname') }}" maxlength="250" class="form-input r-input"></div>
                    <div><label class="r-label">Relationship</label><input type="text" name="ic_relationship" value="{{ old('ic_relationship') }}" maxlength="250" placeholder="e.g. Daughter, Spouse" class="form-input r-input"></div>
                    <div><label class="r-label">Mobile number</label><input type="tel" name="ic_contact" value="{{ old('ic_contact') }}" maxlength="11" inputmode="numeric" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX" class="form-input r-input{{ $err('ic_contact') }}"></div>
                    <div><label class="r-label">Email</label><input type="email" name="ic_email" value="{{ old('ic_email') }}" maxlength="250" class="form-input r-input{{ $err('ic_email') }}"></div>
                    <div class="md:col-span-2"><label class="r-label">Address</label><input type="text" name="ic_address" value="{{ old('ic_address') }}" maxlength="500" class="form-input r-input"></div>
                </div>
            </div>
        </div>

        {{-- ── 6. Review ── --}}
        <div class="r-step card p-6 space-y-5" data-step="6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Please check your details</h2>
            <p class="text-gray-600 dark:text-gray-300">If something is wrong, press <strong>Back</strong> to fix it.</p>
            <div id="review" class="space-y-4"></div>
            <label class="r-choice">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                <span>I confirm these details are true, and I agree that the barangay keeps them for its records and services (Data Privacy Act of 2012, RA 10173).</span>
            </label>
        </div>

        {{-- Navigation --}}
        <div class="flex justify-between gap-3 mt-5">
            <button type="button" id="btn-back" class="btn r-btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white invisible">
                <i class="mgc_arrow_left_line"></i> Back
            </button>
            <button type="button" id="btn-next" class="btn r-btn bg-primary text-white">
                Next <i class="mgc_arrow_right_line"></i>
            </button>
            <button type="submit" id="btn-submit" class="btn r-btn bg-success text-white hidden">
                <i class="mgc_send_line"></i> Submit registration
            </button>
        </div>
    </form>
</div>
@endsection

@push('page-scripts')
<script>
(function () {
    const form   = document.getElementById('reg-form');
    const steps  = [...document.querySelectorAll('.r-step')];
    const titles = @json(collect($steps)->pluck('title')->values());
    let current  = 1;

    function show(n) {
        current = n;
        steps.forEach(s => s.classList.toggle('is-active', +s.dataset.step === n));
        document.getElementById('step-no').textContent = n;
        document.getElementById('step-title').textContent = titles[n - 1];
        document.getElementById('step-bar').style.width = (n / steps.length * 100) + '%';
        document.querySelectorAll('.step-dot').forEach(d => {
            const k = +d.dataset.dot;
            d.className = 'step-dot text-xs ' + (k === n ? 'text-primary font-semibold' : k < n ? 'text-success' : 'text-gray-400');
        });
        document.getElementById('btn-back').classList.toggle('invisible', n === 1);
        document.getElementById('btn-next').classList.toggle('hidden', n === steps.length);
        document.getElementById('btn-submit').classList.toggle('hidden', n !== steps.length);
        if (n === steps.length) buildReview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Check only the visible, enabled fields of a step; show the browser's message on the first problem.
    function stepValid(n) {
        const box = steps[n - 1];
        for (const f of box.querySelectorAll('input, select, textarea')) {
            if (f.type === 'hidden' || f.disabled) continue;
            if (f.type === 'file') {
                // File inputs are visually hidden inside their drop box; required only while their group is ticked.
                if (f.required && !f.files.length) {
                    alert('Please add the ' + f.dataset.label + '.');
                    f.closest('.r-drop').classList.add('is-invalid');
                    f.closest('.r-drop').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                continue;
            }
            if (f.offsetParent === null) continue;            // field hidden for this zone / answer
            if (!f.checkValidity()) {
                f.classList.add('is-invalid');
                f.reportValidity();
                return false;
            }
            f.classList.remove('is-invalid');
        }
        return true;
    }

    document.getElementById('btn-next').addEventListener('click', () => { if (stepValid(current)) show(current + 1); });
    document.getElementById('btn-back').addEventListener('click', () => show(current - 1));
    form.addEventListener('keydown', e => {
        // Enter moves to the next step instead of submitting early.
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && current < steps.length) {
            e.preventDefault();
            document.getElementById('btn-next').click();
        }
    });
    form.addEventListener('submit', e => {
        if (!stepValid(current)) { e.preventDefault(); return; }
        const b = document.getElementById('btn-submit');
        b.disabled = true;
        b.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Sending…';
    });

    // ── Address parts follow the zone ──
    const sel = document.getElementById('address-select');
    const parts = ['blk', 'lot', 'phase', 'no', 'street', 'complete_address'];
    function toggleAddress() {
        const opt = sel.options[sel.selectedIndex];
        const subd = parseInt(opt?.dataset.subd || 0, 10);
        const rules = (opt?.dataset.rules || '').split(',').map(r => r.trim()).filter(Boolean);
        parts.forEach(f => {
            const box = document.getElementById('f-' + f);
            box.classList.add('hidden');
            box.querySelector('input, textarea').required = false;
            const label = box.querySelector('label');
            label.innerHTML = label.textContent.replace('*', '').trim();
        });
        const show = subd === 1 ? ['blk', 'lot', 'phase'] : subd === 2 ? ['complete_address'] : (sel.value ? ['no', 'street'] : []);
        show.forEach(f => {
            const box = document.getElementById('f-' + f);
            box.classList.remove('hidden');
            if (rules.includes(f)) {
                box.querySelector('input, textarea').required = true;
                const label = box.querySelector('label');
                label.innerHTML = label.textContent.trim() + ' <span class="text-danger">*</span>';
            }
        });
    }
    sel.addEventListener('change', toggleAddress);
    toggleAddress();

    // ── Voter → precinct ──
    const voter = document.getElementById('cb-voters');
    const precinct = document.getElementById('f-precinct');
    const syncVoter = () => { precinct.classList.toggle('hidden', !voter.checked); precinct.querySelector('input').required = voter.checked; };
    voter.addEventListener('change', syncVoter);
    syncVoter();

    // ── Age → senior citizen option ──
    const bday = document.getElementById('bday');
    function ageOf(v) {
        if (!v) return null;
        const b = new Date(v + 'T00:00:00'), t = new Date();
        let a = t.getFullYear() - b.getFullYear();
        if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) a--;
        return a;
    }
    function syncAge() {
        const a = ageOf(bday.value);
        document.getElementById('age-note').textContent = a !== null && a >= 0 ? `Age: ${a}` : '';
        const senior = a !== null && a >= 60;
        document.getElementById('grp-senior').classList.toggle('hidden', !senior);
        if (!senior) {
            const cb = document.querySelector('.grp-cb[data-key="senior"]');
            cb.checked = false;
            syncProof(cb);
        }
    }
    bday.addEventListener('change', syncAge);
    syncAge();

    // ── Group ticked → front/back proof required ──
    function syncProof(cb) {
        const box = document.querySelector(`.proof[data-proof="${cb.dataset.key}"]`);
        box.classList.toggle('hidden', !cb.checked);
        box.querySelectorAll('input[type=file]').forEach(i => i.required = cb.checked);
    }
    document.querySelectorAll('.grp-cb').forEach(cb => { cb.addEventListener('change', () => syncProof(cb)); syncProof(cb); });

    // ── Pictures: preview, and shrink big phone photos before upload (max 1600px JPEG) ──
    function shrink(file) {
        return new Promise(resolve => {
            if (!/^image\//.test(file.type) || file.size < 900 * 1024) return resolve(file);
            const img = new Image();
            img.onload = () => {
                const scale = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight));
                const c = document.createElement('canvas');
                c.width = Math.round(img.naturalWidth * scale); c.height = Math.round(img.naturalHeight * scale);
                const ctx = c.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
                ctx.drawImage(img, 0, 0, c.width, c.height);
                c.toBlob(b => resolve(b ? new File([b], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file), 'image/jpeg', 0.85);
                URL.revokeObjectURL(img.src);
            };
            img.onerror = () => resolve(file);
            img.src = URL.createObjectURL(file);
        });
    }
    document.querySelectorAll('.img-input').forEach(input => {
        input.addEventListener('change', async () => {
            const drop = input.closest('.r-drop');
            drop.querySelector('img')?.remove();
            drop.classList.remove('has-file', 'is-invalid');
            const f = input.files[0];
            if (!f) return;
            const small = await shrink(f);
            if (small !== f && window.DataTransfer) {
                try { const dt = new DataTransfer(); dt.items.add(small); input.files = dt.files; } catch (e) {}
            }
            const img = document.createElement('img');
            img.src = URL.createObjectURL(input.files[0]);
            drop.appendChild(img);
            drop.classList.add('has-file');
        });
    });

    // ── Review ──
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const val = n => form.elements[n]?.value?.trim() || '';
    const radio = n => form.querySelector(`input[name="${n}"]:checked`)?.parentElement.textContent.trim() || '';
    const selText = n => { const s = form.elements[n]; return s && s.value ? s.options[s.selectedIndex].text.trim() : ''; };
    const checked = n => form.querySelector(`input[type=checkbox][name="${n}"]`)?.checked;
    function buildReview() {
        const addr = [val('no') && 'No. ' + val('no'), val('street'), val('blk') && 'Blk ' + val('blk'), val('lot') && 'Lot ' + val('lot'),
                      val('phase') && 'Phase ' + val('phase'), val('complete_address'), selText('address')].filter(Boolean).join(', ');
        const groups = [checked('is_senior') && 'Senior citizen', checked('is_pwd') && 'PWD', checked('is_soloparents') && 'Solo parent'].filter(Boolean);
        const sections = {
            'About you': {
                'Name': [val('fname'), val('mname'), val('lname'), val('suffix')].filter(Boolean).join(' '),
                'Sex': radio('gender'), 'Birthday': val('bday') ? new Date(val('bday') + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ` (age ${ageOf(val('bday'))})` : '',
                'Civil status': selText('civil_status'), 'Birthplace': val('bplace'), 'Citizenship': val('citizenship'), 'Occupation': val('occupation'),
            },
            'Address': { 'Address': addr, 'Living here since': val('year_stay'), 'House': radio('owner_status') },
            'Contact': { 'Mobile': val('contact'), 'Email': val('email'), 'Voter': checked('voters') ? 'Yes' + (val('pricinct_no') ? ' — precinct ' + val('pricinct_no') : '') : 'No' },
            'Groups': { 'Groups': groups.length ? groups.join(', ') + ' (ID front & back attached)' : 'None' },
            'Emergency contact': { 'Name': val('ic_fullname'), 'Relationship': val('ic_relationship'), 'Mobile': val('ic_contact') },
        };
        document.getElementById('review').innerHTML = Object.entries(sections).map(([title, rows]) => `
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <p class="px-4 py-2 bg-gray-50 dark:bg-gray-700 font-semibold text-gray-800 dark:text-gray-100">${esc(title)}</p>
                <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                    ${Object.entries(rows).map(([k, v]) => `<div class="grid grid-cols-5 gap-2 px-4 py-2.5">
                        <dt class="col-span-2 text-gray-500 dark:text-gray-400">${esc(k)}</dt>
                        <dd class="col-span-3 text-gray-800 dark:text-gray-100 break-words">${esc(v) || '—'}</dd></div>`).join('')}
                </dl>
            </div>`).join('');
    }

    // After a server error, open the first step that has a problem.
    const errorFields = @json($errors->keys());
    const firstBad = errorFields.map(k => form.querySelector(`[name="${k}"]`)).find(Boolean);
    firstBad?.closest('.r-drop')?.classList.add('is-invalid');
    show(firstBad ? +firstBad.closest('.r-step').dataset.step : 1);
})();
</script>
@endpush
