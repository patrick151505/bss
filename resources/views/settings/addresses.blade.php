@extends('layouts.vertical', [
    'title'         => 'Zones & Addresses',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Manage barangay zones, subdivisions, and address rules.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<div class="grid grid-cols-12 gap-6">

    {{-- ── Settings Sub-nav ── --}}
    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    {{-- ── Settings Content ── --}}
    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <div>
        <p class="text-sm font-medium text-danger mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-12 gap-6">

    {{-- ── Left: Zone List ── --}}
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title"><i class="mgc_map_line me-2 text-primary"></i>Zones / Areas</h5>
                <span class="text-xs text-gray-400">{{ $addresses->count() }} zones</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Name / Description</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-32">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Required Fields</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-20">Citizens</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($addresses as $addr)
                        @php
                            $typeLabel = match($addr->is_subd) {
                                1 => ['label' => 'Subdivision', 'class' => 'bg-primary/10 text-primary'],
                                2 => ['label' => 'Others',      'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'],
                                default => ['label' => 'Street/Zone', 'class' => 'bg-success/10 text-success'],
                            };
                            $rulesArr = $addr->rules_required
                                ? array_filter(array_map('trim', explode(',', $addr->rules_required)))
                                : [];
                            $ruleLabels = ['blk' => 'Block', 'lot' => 'Lot', 'phase' => 'Phase'];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ !$addr->is_active ? 'opacity-50' : '' }}" id="row-{{ $addr->id }}">
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-100">
                                {{ $addr->description }}
                                @if(!$addr->is_active)
                                    <span class="ms-1 text-xs text-gray-400 font-normal">(inactive)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $typeLabel['class'] }}">
                                    {{ $typeLabel['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($addr->is_subd === 1)
                                    @if(count($rulesArr))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($rulesArr as $r)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-warning/10 text-warning">
                                                    <i class="mgc_check_line text-[10px]"></i>
                                                    {{ $ruleLabels[$r] ?? $r }} required
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">No required fields set</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm font-semibold {{ $addr->citizens_count > 0 ? 'text-gray-700 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600' }}">
                                    {{ number_format($addr->citizens_count) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($addr->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success inline-block"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <button onclick="editZone({{ $addr->id }}, {{ json_encode($addr->description) }}, {{ $addr->is_subd }}, {{ json_encode($addr->rules_required ?? '') }})"
                                            class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition" title="Edit">
                                        <i class="mgc_edit_line text-base"></i>
                                    </button>
                                    <form action="{{ route('addresses.toggle', $addr) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="p-1.5 rounded transition {{ $addr->is_active ? 'text-gray-400 hover:text-warning hover:bg-warning/10' : 'text-gray-400 hover:text-success hover:bg-success/10' }}"
                                                title="{{ $addr->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="{{ $addr->is_active ? 'mgc_eye_close_line' : 'mgc_eye_line' }} text-base"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                                <i class="mgc_map_line text-4xl mb-2 block opacity-30"></i>
                                No zones yet. Add one on the right.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Right: Add / Edit Form ── --}}
    <div class="col-span-12 lg:col-span-4">
        <div class="card" id="form-card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title" id="form-title"><i class="mgc_add_circle_line me-2 text-primary"></i>Add Zone</h5>
                <button onclick="cancelEdit()" id="cancel-btn" class="hidden text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
                    <i class="mgc_close_line"></i> Cancel
                </button>
            </div>
            <div class="p-5">

                <form id="zone-form" action="{{ route('addresses.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    <input type="hidden" name="_zone_id" id="form-zone-id" value="">

                    {{-- Name --}}
                    <div>
                        <label class="form-label">Zone / Area Name <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="inp-description"
                               class="form-input" placeholder="e.g. PUROK 1 or NSDC VILLAGE"
                               required maxlength="250">
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="is_subd" id="inp-type" class="form-select" onchange="toggleRules()" required>
                            <option value="0">Street / Zone</option>
                            <option value="1">Subdivision</option>
                            <option value="2">Others (free-text address)</option>
                        </select>
                    </div>

                    {{-- Required fields (subdivision only) --}}
                    <div id="rules-section" class="hidden">
                        <label class="form-label">Required Fields for this Subdivision</label>
                        <p class="text-xs text-gray-400 mb-3">
                            Checked fields will be <span class="font-semibold text-warning">required</span> when a citizen selects this zone.
                        </p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <input type="checkbox" name="rule_blk" id="rule-blk" value="1"
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Block No.</p>
                                    <p class="text-xs text-gray-400">Citizen must enter their block number</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <input type="checkbox" name="rule_lot" id="rule-lot" value="1"
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Lot No.</p>
                                    <p class="text-xs text-gray-400">Citizen must enter their lot number</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <input type="checkbox" name="rule_phase" id="rule-phase" value="1"
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Phase</p>
                                    <p class="text-xs text-gray-400">Citizen must enter their phase number</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" id="submit-btn" class="btn bg-primary text-white w-full">
                        <i class="mgc_add_circle_line me-1"></i> Add Zone
                    </button>
                </form>

            </div>
        </div>

        {{-- Legend --}}
        <div class="card mt-4 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Zone Types</p>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex gap-2">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-success/10 text-success shrink-0 mt-0.5">Street/Zone</span>
                    <p class="text-xs text-gray-400">Shows house no. and street fields. Typical for regular barangay zones.</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary shrink-0 mt-0.5">Subdivision</span>
                    <p class="text-xs text-gray-400">Shows blk/lot/phase. You control which are required.</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300 shrink-0 mt-0.5">Others</span>
                    <p class="text-xs text-gray-400">Shows a single complete address text field.</p>
                </div>
            </div>
        </div>
    </div>

</div>

    </div>

</div>

@endsection

@push('inline-scripts')
<script>
let editingId = null;

function toggleRules() {
    const type = document.getElementById('inp-type').value;
    document.getElementById('rules-section').classList.toggle('hidden', type !== '1');
}

function editZone(id, description, isSubd, rulesStr) {
    editingId = id;

    // Update form action to PUT route
    const form = document.getElementById('zone-form');
    form.action = `{{ url('settings/addresses') }}/${id}`;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('form-zone-id').value = id;

    // Populate fields
    document.getElementById('inp-description').value = description;
    document.getElementById('inp-type').value = isSubd;
    toggleRules();

    // Set checkboxes
    const rules = rulesStr ? rulesStr.split(',').map(r => r.trim()) : [];
    document.getElementById('rule-blk').checked   = rules.includes('blk');
    document.getElementById('rule-lot').checked   = rules.includes('lot');
    document.getElementById('rule-phase').checked = rules.includes('phase');

    // Update UI
    document.getElementById('form-title').innerHTML = '<i class="mgc_edit_line me-2 text-warning"></i>Edit Zone';
    document.getElementById('submit-btn').innerHTML = '<i class="mgc_save_line me-1"></i> Save Changes';
    document.getElementById('submit-btn').className = 'btn bg-warning text-white w-full';
    document.getElementById('cancel-btn').classList.remove('hidden');

    // Scroll to form
    document.getElementById('form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelEdit() {
    editingId = null;
    const form = document.getElementById('zone-form');
    form.action = '{{ route('addresses.store') }}';
    document.getElementById('form-method').value = 'POST';
    document.getElementById('form-zone-id').value = '';
    form.reset();
    document.getElementById('rules-section').classList.add('hidden');

    document.getElementById('form-title').innerHTML = '<i class="mgc_add_circle_line me-2 text-primary"></i>Add Zone';
    document.getElementById('submit-btn').innerHTML = '<i class="mgc_add_circle_line me-1"></i> Add Zone';
    document.getElementById('submit-btn').className = 'btn bg-primary text-white w-full';
    document.getElementById('cancel-btn').classList.add('hidden');
}

</script>
@endpush
