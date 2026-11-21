{{-- Check-up form section: prescription (reseta) for medicines not available at the station.
     Inherits the variables set up in health._form. Saved with the check-up; nothing comes out of stock. --}}
@php
    $rxRows = old('prescriptions', $C->exists && $C->relationLoaded('prescriptions') && $C->prescriptions->isNotEmpty()
        ? $C->prescriptions->map(fn($p) => ['medicine' => $p->medicine, 'quantity' => $p->quantity, 'sig' => $p->sig])->all()
        : []);
    // Licence / PTR: this check-up's, or what this staff member used last time.
    $lastRx = !$C->exists
        ? \App\Models\HealthCheckup::where('created_by', auth()->id())->whereNotNull('prescriber_license')->latest('id')->first(['prescriber_license', 'prescriber_ptr'])
        : null;
    // Suggestions: medicine items (in stock or not) and names written before.
    $rxNames = \App\Models\InventoryItem::whereHas('category', fn($c) => $c->medicine())->orderBy('name')->limit(300)->pluck('name')
        ->merge(\App\Models\HealthPrescription::select('medicine')->distinct()->orderBy('medicine')->limit(300)->pluck('medicine'))
        ->unique()->sort()->values();
    $rxSigs = \App\Models\HealthPrescription::whereNotNull('sig')->select('sig')->distinct()->limit(100)->pluck('sig');
@endphp
<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center gap-3">
            <div>
                <h4 class="card-title flex items-center gap-2"><span class="font-serif text-lg leading-none text-primary">℞</span> Prescription (reseta)</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">For medicines not available here — the patient buys them outside. Prints as a reseta; nothing comes out of stock.</p>
            </div>
            <button type="button" onclick="addRxRow()" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white shrink-0">
                <i class="mgc_add_line me-1"></i>Add
            </button>
        </div>
    </div>
    <div class="p-6">
        <div id="rx-rows" class="space-y-3">
            @foreach($rxRows as $i => $row)
            @include('health._rx-row', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <p id="rx-empty" class="text-sm text-gray-400 {{ count($rxRows) ? 'hidden' : '' }}">No prescription. Click <b>Add</b> to write one.</p>

        <div class="grid sm:grid-cols-2 gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <div>
                <label class="{{ $lbl }}">Licence no. <span class="text-xs font-normal text-gray-400">(PRC, optional)</span></label>
                <input type="text" name="prescriber_license" maxlength="50" class="form-input w-full text-sm"
                       value="{{ old('prescriber_license', $C->prescriber_license ?? $lastRx?->prescriber_license) }}">
            </div>
            <div>
                <label class="{{ $lbl }}">PTR no. <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                <input type="text" name="prescriber_ptr" maxlength="50" class="form-input w-full text-sm"
                       value="{{ old('prescriber_ptr', $C->prescriber_ptr ?? $lastRx?->prescriber_ptr) }}">
            </div>
            <p class="sm:col-span-2 text-xs text-gray-400">The reseta is signed by the person in <b>Attended by</b>; these numbers print under the name.</p>
        </div>
    </div>
</div>

<datalist id="rx-medicine-list">@foreach($rxNames as $n)<option value="{{ $n }}">@endforeach</datalist>
<datalist id="rx-sig-list">@foreach($rxSigs as $n)<option value="{{ $n }}">@endforeach</datalist>
<template id="rx-row-tpl">@include('health._rx-row', ['i' => '__I__', 'row' => []])</template>

@push('inline-scripts')
<script>
let rxSeq = {{ count($rxRows) + 1 }};
function renumberRx() {
    const rows = document.querySelectorAll('#rx-rows .rx-row');
    rows.forEach((r, n) => r.querySelector('.rx-num').textContent = (n + 1) + '.');
    document.getElementById('rx-empty').classList.toggle('hidden', rows.length > 0);
}
function addRxRow(focus = true) {
    const wrap = document.createElement('div');
    wrap.innerHTML = document.getElementById('rx-row-tpl').innerHTML.replaceAll('__I__', 'n' + rxSeq++);
    const row = wrap.firstElementChild;
    document.getElementById('rx-rows').appendChild(row);
    renumberRx();
    if (focus) row.querySelector('.rx-medicine').focus();
    return row;
}
renumberRx();
</script>
@endpush
