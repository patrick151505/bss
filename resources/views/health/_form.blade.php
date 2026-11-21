{{--
    Check-up form, shared by create and edit. Expects $checkup, $patient (array|null), $medicineItems.
    $layout: 'grid' (edit page, everything on one page) or 'wizard' (create page, step by step).
    The sections live in health._sections.* and inherit the variables set up here.
--}}
@php
    $C = $checkup;
    $isNew = !$C->exists;
    $lbl = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $canDispense = $isNew && auth()->user()->can('health.dispense');
    $walkIn = old('citizen_id') ? false : (old('patient_name') !== null ? true : ($C->exists && !$C->citizen_id));
    $patientInit = old('citizen_id') ? ($patient && (int) $patient['id'] === (int) old('citizen_id') ? $patient : null) : ($walkIn ? null : $patient);
    $medRows = old('medicines', [['item_id' => '', 'quantity' => '', 'instructions' => '']]);
    $num = fn($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
@endphp

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="text-sm text-danger list-disc pl-4 space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

@if(($layout ?? 'grid') === 'wizard')
    @include('health._wizard')
@else
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        @include('health._sections.patient')
        @include('health._sections.vitals')
        @include('health._sections.consult')
    </div>
    <div class="space-y-6">
        @include('health._sections.visit')
        @include('health._sections.medicines')
        @include('health._sections.prescription')
    </div>
</div>
@endif

@include('health._form-scripts')
