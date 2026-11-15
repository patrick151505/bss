{{--
    "Who can attend" card for the event create/edit forms.
    Expects: $addresses (active eb_address rows), $selected (array of allowed address ids; empty = everyone)
--}}
@php
    $picked   = collect(old('allowed_addresses', $selected ?? []))->map(fn($v) => (int) $v)->all();
    $restrict = old('area_mode', count($picked) ? 'selected' : 'all') === 'selected';

    // Extra rules (age, gender, groups, tags) — see Event::eligibilityRules().
    $rules      = isset($event) && $event->exists ? $event->eligibilityRules() : [];
    $minAge     = (string) old('min_age', $rules['min_age'] ?? '');
    $maxAge     = (string) old('max_age', $rules['max_age'] ?? '');
    $gender     = (string) old('eligible_gender', isset($rules['gender']) ? (string) $rules['gender'] : '');
    $groups     = old('eligible_groups', $rules['groups'] ?? []);
    $groupsAll  = old('groups_match', $rules['groups_match'] ?? 'any') === 'all';
    $pickedTags = collect(old('eligible_tags', $rules['tags'] ?? []))->map(fn($v) => (int) $v)->all();
    $hasRules   = $minAge !== '' || $maxAge !== '' || $gender !== '' || $groups || $pickedTags;
@endphp

<style>
    .area-mode, .area-tile { border: 1px solid #e5e7eb; transition: border-color .15s, background-color .15s; }
    [data-mode="dark"] .area-mode, [data-mode="dark"] .area-tile { border-color: #374151; }
    .area-mode:has(input:checked), .area-tile:has(input:checked) { border-color: #3073F1; background-color: rgba(48, 115, 241, .07); }
    .rule-grid { display: grid; grid-template-columns: 1fr; gap: .75rem; }
    @media (min-width: 640px) { .rule-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .rule-chip { border: 1px solid #e5e7eb; transition: border-color .15s, background-color .15s; }
    [data-mode="dark"] .rule-chip { border-color: #374151; }
    .rule-chip:has(input:checked) { border-color: #3073F1; background-color: rgba(48, 115, 241, .07); }
</style>

<div class="card">
    <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
        <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">3</span>
        <span class="text-sm font-semibold text-primary uppercase tracking-wide">Who Can Attend</span>
    </div>
    <div class="p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="area-mode flex items-start gap-3 p-3 rounded-lg cursor-pointer">
                <input type="radio" name="area_mode" value="all" class="form-radio mt-0.5" {{ $restrict ? '' : 'checked' }}>
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">All areas</p>
                    <p class="text-xs text-gray-400 mt-0.5">Residents of any area</p>
                </div>
            </label>
            <label class="area-mode flex items-start gap-3 p-3 rounded-lg cursor-pointer">
                <input type="radio" name="area_mode" value="selected" class="form-radio mt-0.5" {{ $restrict ? 'checked' : '' }}>
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Selected areas only</p>
                    <p class="text-xs text-gray-400 mt-0.5">Only residents of the areas you pick</p>
                </div>
            </label>
        </div>

        <div id="area-picker" class="{{ $restrict ? '' : 'hidden' }}">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400"><span id="area-count">{{ count($picked) }}</span> selected</p>
                <div class="flex gap-3 text-xs">
                    <button type="button" class="text-primary font-medium" onclick="areaSelectAll(true)">Select all</button>
                    <button type="button" class="text-gray-500 dark:text-gray-400 font-medium" onclick="areaSelectAll(false)">Clear</button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($addresses as $addr)
                    <label class="area-tile flex items-center gap-2.5 px-3 py-2 rounded-lg cursor-pointer">
                        <input type="checkbox" name="allowed_addresses[]" value="{{ $addr->id }}" class="form-checkbox area-check"
                            {{ in_array($addr->id, $picked, true) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ $addr->description }}</span>
                    </label>
                @endforeach
            </div>
            @error('allowed_addresses')<p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
            <p class="text-xs text-gray-400 mt-3">
                <i class="mgc_information_line"></i>
                Check-in (QR, manual and bulk add) only accepts residents of these areas, and the raffle's area filter lists only them.
            </p>
        </div>

        {{-- More rules --}}
        <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
            <button type="button" class="w-full flex items-center justify-between text-left" onclick="toggleRules()">
                <span>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">More rules</span>
                    <span class="text-xs text-gray-400 ms-1">optional — age, seniors, PWD, solo parents, voters, tags</span>
                </span>
                <span class="flex items-center gap-2">
                    <span id="rules-badge" class="{{ $hasRules ? '' : 'hidden' }} inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-primary/25 text-sky-800 dark:text-sky-200">
                        <span class="w-1.5 h-1.5 inline-block bg-primary rounded-full"></span>On
                    </span>
                    <i id="rules-chevron" class="{{ $hasRules ? 'mgc_up_line' : 'mgc_down_line' }} text-gray-400"></i>
                </span>
            </button>

            <div id="rules-body" class="{{ $hasRules ? '' : 'hidden' }} mt-4 space-y-5">
                <div class="rule-grid">
                    <div>
                        <label class="form-label text-sm" for="min_age">Age from</label>
                        <input type="number" name="min_age" id="min_age" value="{{ $minAge }}" min="0" max="150" class="form-input w-full rule-input" placeholder="Any">
                    </div>
                    <div>
                        <label class="form-label text-sm" for="max_age">Age to</label>
                        <input type="number" name="max_age" id="max_age" value="{{ $maxAge }}" min="0" max="150" class="form-input w-full rule-input" placeholder="Any">
                    </div>
                    <div>
                        <label class="form-label text-sm" for="eligible_gender">Gender</label>
                        <select name="eligible_gender" id="eligible_gender" class="form-select w-full rule-input">
                            <option value="" {{ $gender === '' ? 'selected' : '' }}>Anyone</option>
                            <option value="1" {{ $gender === '1' ? 'selected' : '' }}>Men only</option>
                            <option value="0" {{ $gender === '0' ? 'selected' : '' }}>Women only</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 -mt-2">
                    <span class="text-xs text-gray-400 self-center">Quick:</span>
                    <button type="button" class="text-xs px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20" onclick="setAge(60, '')">Senior 60+</button>
                    <button type="button" class="text-xs px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20" onclick="setAge(18, '')">Adults 18+</button>
                    <button type="button" class="text-xs px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20" onclick="setAge(15, 30)">Youth 15–30</button>
                    <button type="button" class="text-xs px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20" onclick="setAge('', 17)">Minors 0–17</button>
                    <button type="button" class="text-xs px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-400 hover:bg-dark/10" onclick="setAge('', '')">Any age</button>
                </div>
                @error('min_age')<p class="text-danger text-xs">{{ $message }}</p>@enderror
                @error('max_age')<p class="text-danger text-xs">{{ $message }}</p>@enderror

                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Only these groups</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\Event::GROUPS as $key => $label)
                            <label class="rule-chip flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer">
                                <input type="checkbox" name="eligible_groups[]" value="{{ $key }}" data-short="{{ \App\Models\Event::GROUP_SHORT[$key] }}" class="form-checkbox rule-input group-check" {{ in_array($key, $groups, true) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div id="groups-match" class="{{ count($groups) > 1 ? 'flex' : 'hidden' }} flex-wrap items-center gap-4 mt-3 text-sm text-gray-600 dark:text-gray-300">
                        <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="groups_match" value="any" class="form-radio" {{ $groupsAll ? '' : 'checked' }}> Any of these <span class="text-xs text-gray-400">(e.g. senior <b>or</b> PWD)</span></label>
                        <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="groups_match" value="all" class="form-radio" {{ $groupsAll ? 'checked' : '' }}> All of these <span class="text-xs text-gray-400">(senior <b>and</b> PWD)</span></label>
                    </div>
                </div>

                @if(isset($tags) && $tags->isNotEmpty())
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Only citizens with these tags <span class="text-xs font-normal text-gray-400">— any one of them</span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <label class="rule-chip flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer">
                                <input type="checkbox" name="eligible_tags[]" value="{{ $tag->id }}" data-short="{{ $tag->name }}" class="form-checkbox rule-input" {{ in_array($tag->id, $pickedTags, true) ? 'checked' : '' }}>
                                <span class="w-2.5 h-2.5 rounded-full inline-block" style="background: {{ $tag->color ?: '#6c757d' }}"></span>
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <p id="rules-summary" class="text-xs p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300"></p>
            </div>
        </div>
    </div>
</div>

@push('inline-scripts')
<script>
document.querySelectorAll('input[name="area_mode"]').forEach(r => r.addEventListener('change', () => {
    document.getElementById('area-picker').classList.toggle('hidden', document.querySelector('input[name="area_mode"]:checked').value !== 'selected');
}));
document.querySelectorAll('.area-check').forEach(cb => cb.addEventListener('change', updateAreaCount));
function updateAreaCount() {
    document.getElementById('area-count').textContent = document.querySelectorAll('.area-check:checked').length;
}
function toggleRules() {
    const body = document.getElementById('rules-body');
    const opening = body.classList.contains('hidden');
    body.classList.toggle('hidden');
    document.getElementById('rules-chevron').classList.toggle('mgc_down_line', !opening);
    document.getElementById('rules-chevron').classList.toggle('mgc_up_line', opening);
}
function setAge(min, max) {
    document.getElementById('min_age').value = min;
    document.getElementById('max_age').value = max;
    updateRulesSummary();
}
// Plain-language summary of who may attend, e.g. "age 60+ · Senior or PWD · tagged 4Ps".
function updateRulesSummary() {
    const esc = v => String(v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const min = document.getElementById('min_age').value, max = document.getElementById('max_age').value;
    const g = document.getElementById('eligible_gender').value;
    const groups = [...document.querySelectorAll('.group-check:checked')].map(c => c.dataset.short);
    const all = document.querySelector('input[name="groups_match"]:checked')?.value === 'all';
    const tags = [...document.querySelectorAll('input[name="eligible_tags[]"]:checked')].map(c => c.dataset.short);
    const match = document.getElementById('groups-match');
    match.classList.toggle('hidden', groups.length < 2);
    match.classList.toggle('flex', groups.length >= 2);
    const parts = [];
    if (min && max) parts.push(`age ${min}–${max}`); else if (min) parts.push(`age ${min}+`); else if (max) parts.push(`age ${max} and below`);
    if (g !== '') parts.push(g === '1' ? 'men only' : 'women only');
    if (groups.length) parts.push(groups.join(all ? ' and ' : ' or '));
    if (tags.length) parts.push('tagged ' + tags.join(' or '));
    document.getElementById('rules-summary').innerHTML = parts.length
        ? '<i class="mgc_user_3_line"></i> Only residents who are: <b>' + parts.map(esc).join('</b> · <b>') + '</b>. Ages are counted on the event date. Check-in, bulk add and the raffle all follow these rules.'
        : '<i class="mgc_information_line"></i> No extra rules — anyone in the allowed areas can attend.';
    document.getElementById('rules-badge').classList.toggle('hidden', !parts.length);
}
document.querySelectorAll('.rule-input, input[name="groups_match"]').forEach(el => {
    el.addEventListener('input', updateRulesSummary);
    el.addEventListener('change', updateRulesSummary);
});
updateRulesSummary();
function areaSelectAll(on) {
    document.querySelectorAll('.area-check').forEach(cb => cb.checked = on);
    updateAreaCount();
}
</script>
@endpush
