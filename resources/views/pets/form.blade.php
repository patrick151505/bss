@php
    $editing = $pet->exists;
    $catValue = old('category', $pet->category?->name);
    $ownerJs = $owner ? [
        'id' => $owner->id, 'name' => $owner->full_name, 'address' => $owner->full_address, 'profile' => $owner->profile_url,
    ] : null;
@endphp
@extends('layouts.vertical', [
    'title'       => $editing ? 'Edit ' . $pet->name : 'Add Pet',
    'breadcrumbs' => array_values(array_filter([
        ['label' => 'Pets', 'url' => route('pets.index')],
        $editing ? ['label' => $pet->name, 'url' => route('pets.show', $pet)] : null,
        ['label' => $editing ? 'Edit' : 'Add Pet', 'url' => ''],
    ])),
    'tagline'     => $editing ? 'Update the pet’s details or photo.' : 'Record a resident’s pet with its photo and details.',
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@section('content')
@include('partials.photo-cropper')

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <ul class="list-disc list-inside text-sm text-danger space-y-0.5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ $editing ? route('pets.update', $pet) : route('pets.store') }}" enctype="multipart/form-data" id="pet-form">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="grid grid-cols-12 gap-6">

        {{-- ── Left: photo ── --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="card p-6">
                <h4 class="card-title mb-4">Photo</h4>
                <div class="relative mx-auto w-full max-w-[16rem] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700" style="aspect-ratio:1/1">
                    <img id="pet-preview" src="{{ $pet->photo_url }}" alt="" class="absolute inset-0 w-full h-full object-cover {{ $pet->photo_url ? '' : 'hidden' }}">
                    <div id="pet-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 {{ $pet->photo_url ? 'hidden' : '' }}">
                        <i class="mgc_bone_line text-5xl opacity-40"></i>
                        <span class="text-xs mt-2">No photo yet</span>
                    </div>
                </div>
                <div class="flex flex-wrap justify-center gap-2 mt-4">
                    <label for="pet-photo" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white cursor-pointer">
                        <i class="mgc_upload_2_line"></i> Upload photo
                    </label>
                    <label for="pet-photo-camera" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white cursor-pointer sm:hidden">
                        <i class="mgc_camera_line"></i> Camera
                    </label>
                </div>
                <input type="file" id="pet-photo" name="photo" accept="image/jpeg,image/png" class="hidden">
                <input type="file" id="pet-photo-camera" accept="image/*" capture="environment" class="hidden">
                @if($editing && $pet->photo)
                <label class="flex items-center justify-center gap-2 mt-3 text-xs text-gray-500 dark:text-gray-400 cursor-pointer">
                    <input type="checkbox" name="remove_photo" value="1" class="form-checkbox rounded" id="remove-photo"> Remove photo
                </label>
                @endif
                <p class="text-xs text-gray-400 text-center mt-2">You can move and zoom the picture to fit the square before saving.</p>
            </div>
        </div>

        {{-- ── Right: details ── --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- Owner --}}
            <div class="card p-6">
                <h4 class="card-title mb-4">Owner <span class="text-danger">*</span></h4>
                <input type="hidden" name="citizen_id" id="owner-id" value="{{ old('citizen_id', $owner?->id) }}">

                <div id="owner-picked" class="{{ $owner ? '' : 'hidden' }} flex items-center gap-3 p-3 rounded-lg border border-primary/30 bg-primary/5">
                    <div id="owner-avatar" class="w-11 h-11 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0 flex items-center justify-center text-gray-500 font-semibold"></div>
                    <div class="flex-1 min-w-0">
                        <p id="owner-name" class="font-medium text-gray-800 dark:text-gray-100 truncate"></p>
                        <p id="owner-address" class="text-xs text-gray-500 dark:text-gray-400 truncate"></p>
                    </div>
                    <button type="button" onclick="ownerClear()" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Change</button>
                </div>

                <div id="owner-search-wrap" class="{{ $owner ? 'hidden' : '' }} relative">
                    <i class="mgc_search_line absolute left-3 top-3 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="owner-search" autocomplete="off" placeholder="Search the owner by name (at least 2 letters)…" class="form-input ps-9 w-full @error('citizen_id') border-danger @enderror">
                    <div id="owner-results" class="hidden absolute z-30 mt-1 w-full max-h-72 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"></div>
                </div>
            </div>

            {{-- Pet details --}}
            <div class="card p-6">
                <h4 class="card-title mb-4">Pet details</h4>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $pet->name) }}" maxlength="100" required class="form-input w-full @error('name') border-danger @enderror" placeholder="e.g. Bantay">
                    </div>

                    {{-- Kind of pet: pick a saved one or type a new one (kept for next time) --}}
                    <div>
                        <label for="cat-search" class="form-label">Kind of pet <span class="text-danger">*</span></label>
                        <input type="hidden" name="category" id="cat-input" value="{{ $catValue }}">
                        <div id="cat-dd" class="relative">
                            <div class="relative">
                                <input type="text" id="cat-search" autocomplete="off" maxlength="60" value="{{ $catValue }}"
                                       placeholder="Dog, Cat, Bird… or type another" class="form-input w-full pr-9 @error('category') border-danger @enderror">
                                <button type="button" tabindex="-1" id="cat-toggle" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <i id="cat-chevron" class="mgc_down_line"></i>
                                </button>
                            </div>
                            <div id="cat-list" class="hidden absolute z-30 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                @foreach($categories as $c)
                                <button type="button" class="cat-option w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-primary/10 flex items-center gap-2" data-name="{{ $c }}">
                                    <i class="{{ \App\Models\PetCategory::iconFor($c) }} text-gray-400"></i>{{ $c }}
                                </button>
                                @endforeach
                                <button type="button" id="cat-add" class="hidden w-full text-left px-3 py-2 text-sm text-primary hover:bg-primary/10 border-t border-gray-100 dark:border-gray-700">
                                    <i class="mgc_add_line"></i> Use "<span id="cat-add-label"></span>"
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">A new kind is saved for next time.</p>
                    </div>

                    <div>
                        <label class="form-label">Breed</label>
                        <input type="text" name="breed" value="{{ old('breed', $pet->breed) }}" maxlength="100" class="form-input w-full" placeholder="e.g. Aspin, Shih Tzu, Puspin">
                    </div>
                    <div>
                        <label class="form-label">Color / markings</label>
                        <input type="text" name="color" value="{{ old('color', $pet->color) }}" maxlength="100" class="form-input w-full" placeholder="e.g. Brown with white chest">
                    </div>

                    <div>
                        <label class="form-label">Sex</label>
                        <div class="flex gap-2">
                            @foreach(['' => 'Unknown', 'male' => 'Male', 'female' => 'Female'] as $v => $l)
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="sex" value="{{ $v }}" class="peer sr-only" @checked((string) old('sex', $pet->sex) === $v)>
                                <span class="block text-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40">{{ $l }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Birth date <span class="text-xs text-gray-400 font-normal">(or best guess)</span></label>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $pet->birth_date?->format('Y-m-d')) }}" max="{{ today()->toDateString() }}" class="form-input w-full">
                    </div>

                    <div>
                        <label class="form-label">Last anti-rabies shot</label>
                        <input type="date" name="last_vaccinated_on" value="{{ old('last_vaccinated_on', $pet->last_vaccinated_on?->format('Y-m-d')) }}" max="{{ today()->toDateString() }}" class="form-input w-full">
                        <p class="text-xs text-gray-400 mt-1">Shown as due one year after this date.</p>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select w-full">
                            @foreach(\App\Models\Pet::STATUSES as $key => [$label])
                            <option value="{{ $key }}" @selected(old('status', $pet->status ?: 'active') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                            <input type="hidden" name="is_neutered" value="0">
                            <input type="checkbox" name="is_neutered" value="1" class="form-checkbox rounded" @checked(old('is_neutered', $pet->is_neutered))>
                            Spayed / neutered (kapon)
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" maxlength="2000" class="form-input w-full" placeholder="Other vaccines, illnesses, behaviour, where it usually stays…">{{ old('notes', $pet->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <a href="{{ $editing ? route('pets.show', $pet) : ($owner ? route('citizens.show', $owner) : route('pets.index')) }}"
                   class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</a>
                @unless($editing)
                <button type="submit" name="after" value="another" class="btn bg-primary/10 text-primary hover:bg-primary hover:text-white">
                    <i class="mgc_add_line"></i> Save &amp; add another
                </button>
                @endunless
                <button type="submit" class="btn bg-primary text-white"><i class="mgc_check_line"></i> {{ $editing ? 'Save changes' : 'Save pet' }}</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('inline-scripts')
<script>
// ── Photo: position it in the square frame, then preview ──
function showPetPreview(url) {
    const img = document.getElementById('pet-preview');
    img.src = url; img.classList.remove('hidden');
    document.getElementById('pet-placeholder').classList.add('hidden');
    const rm = document.getElementById('remove-photo'); if (rm) rm.checked = false;
}
PhotoCrop.attachToInput(document.getElementById('pet-photo'), showPetPreview);
// Phone camera: crop, then hand the result to the real photo input.
document.getElementById('pet-photo-camera').addEventListener('change', e => {
    const f = e.target.files[0]; e.target.value = '';
    if (!f) return;
    PhotoCrop.open(f).then(blob => {
        if (!blob) return;
        const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
        const dt = new DataTransfer(); dt.items.add(file);
        const input = document.getElementById('pet-photo');
        input._pcSetting = true; input.files = dt.files; input._pcSetting = false;
        showPetPreview(URL.createObjectURL(file));
    });
});

// ── Owner picker (citizen search) ──
const OWNER_URL = @json(route('citizens.search'));
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
function ownerShow(o) {
    document.getElementById('owner-id').value = o.id;
    document.getElementById('owner-name').textContent = o.name;
    document.getElementById('owner-address').textContent = o.address || '';
    document.getElementById('owner-avatar').innerHTML = o.profile
        ? `<img src="${esc(o.profile)}" class="w-full h-full object-cover">` : esc((o.name || '?').trim()[0]);
    document.getElementById('owner-picked').classList.remove('hidden');
    document.getElementById('owner-search-wrap').classList.add('hidden');
    document.getElementById('owner-results').classList.add('hidden');
}
function ownerClear() {
    document.getElementById('owner-id').value = '';
    document.getElementById('owner-picked').classList.add('hidden');
    document.getElementById('owner-search-wrap').classList.remove('hidden');
    const s = document.getElementById('owner-search'); s.value = ''; s.focus();
}
@if($ownerJs) ownerShow(@json($ownerJs)); @endif

let ownerTimer = null, ownerSeq = 0, ownerData = [];
document.getElementById('owner-search').addEventListener('input', e => {
    clearTimeout(ownerTimer);
    const q = e.target.value.trim(), box = document.getElementById('owner-results');
    if (q.length < 2) { box.classList.add('hidden'); return; }
    ownerTimer = setTimeout(() => {
        const seq = ++ownerSeq;
        box.innerHTML = '<p class="px-3 py-3 text-xs text-gray-400">Searching…</p>'; box.classList.remove('hidden');
        fetch(OWNER_URL + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json()).then(list => {
                if (seq !== ownerSeq) return;
                ownerData = list;
                box.innerHTML = list.length ? list.map((c, i) => `
                    <button type="button" data-i="${i}" class="owner-opt w-full text-left px-3 py-2 flex items-center gap-3 hover:bg-primary/10">
                        <span class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0 flex items-center justify-center text-xs text-gray-500">${c.profile ? `<img src="${esc(c.profile)}" class="w-full h-full object-cover">` : esc(c.name[0])}</span>
                        <span class="min-w-0"><span class="block text-sm text-gray-800 dark:text-gray-100 truncate">${esc(c.name)}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">${esc(c.address)}</span></span>
                    </button>`).join('')
                    : '<p class="px-3 py-3 text-xs text-gray-400 text-center">No citizen found.</p>';
            }).catch(() => { box.innerHTML = '<p class="px-3 py-3 text-xs text-danger">Search failed. Try again.</p>'; });
    }, 250);
});
document.getElementById('owner-results').addEventListener('click', e => {
    const b = e.target.closest('.owner-opt'); if (b) ownerShow(ownerData[+b.dataset.i]);
});

// ── Kind of pet: searchable dropdown; a typed new kind is used as-is ──
(function () {
    const search = document.getElementById('cat-search'), hidden = document.getElementById('cat-input');
    const list = document.getElementById('cat-list'), chev = document.getElementById('cat-chevron');
    const add = document.getElementById('cat-add'), addLbl = document.getElementById('cat-add-label');
    const opts = () => [...document.querySelectorAll('.cat-option')];
    function filter() {
        const q = search.value.trim().toLowerCase();
        let exact = false;
        opts().forEach(o => {
            const n = o.dataset.name.toLowerCase();
            o.classList.toggle('hidden', q && !n.includes(q));
            if (n === q) exact = true;
        });
        add.classList.toggle('hidden', !q || exact);
        addLbl.textContent = search.value.trim();
    }
    const open = () => { list.classList.remove('hidden'); chev.classList.replace('mgc_down_line', 'mgc_up_line'); filter(); };
    const close = () => { list.classList.add('hidden'); chev.classList.replace('mgc_up_line', 'mgc_down_line'); };
    const pick = name => { search.value = name; hidden.value = name; search.classList.remove('border-danger'); close(); };
    search.addEventListener('focus', open);
    search.addEventListener('input', () => { hidden.value = search.value.trim(); open(); });
    search.addEventListener('keydown', e => {
        if (e.key === 'Escape') close();
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = search.value.trim();
        const exact = opts().find(o => o.dataset.name.toLowerCase() === q.toLowerCase());
        const first = opts().find(o => !o.classList.contains('hidden'));
        if (exact) pick(exact.dataset.name); else if (q) pick(q); else if (first) pick(first.dataset.name);
    });
    document.getElementById('cat-toggle').addEventListener('click', () => list.classList.contains('hidden') ? (search.focus(), open()) : close());
    list.addEventListener('click', e => {
        const o = e.target.closest('.cat-option'); if (o) pick(o.dataset.name);
        if (e.target.closest('#cat-add')) pick(search.value.trim());
    });
    document.addEventListener('click', e => { if (!document.getElementById('cat-dd').contains(e.target)) close(); });
})();

// Owner and kind are required (hidden fields, so check before sending).
document.getElementById('pet-form').addEventListener('submit', e => {
    if (!document.getElementById('owner-id').value) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Choose the owner', text: 'Search for the citizen who owns this pet.' });
        document.getElementById('owner-search')?.focus();
        return;
    }
    if (!document.getElementById('cat-input').value.trim()) {
        e.preventDefault();
        document.getElementById('cat-search').classList.add('border-danger');
        Swal.fire({ icon: 'warning', title: 'Choose the kind of pet', text: 'Pick Dog, Cat, … or type another kind.' });
    }
});
</script>
@endpush
