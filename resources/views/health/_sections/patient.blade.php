{{-- Check-up form section. Inherits the variables set up in health._form. --}}
<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center gap-3">
            <h4 class="card-title">Patient</h4>
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="walkin-toggle" class="form-checkbox rounded text-primary" @checked($walkIn)>
                Walk-in (not in citizen records)
            </label>
        </div>
    </div>
    <div class="p-6">
        <input type="hidden" name="citizen_id" id="citizen_id" value="{{ $walkIn ? '' : ($patientInit['id'] ?? '') }}">

        {{-- Citizen picker --}}
        <div id="citizen-box" class="{{ $walkIn ? 'hidden' : '' }}">
            <div id="patient-chip" class="hidden items-center gap-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                <img id="pc-photo" class="w-12 h-12 rounded-full object-cover hidden" alt="">
                <div id="pc-initials" class="w-12 h-12 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center shrink-0"></div>
                <div class="min-w-0 flex-1">
                    <p id="pc-name" class="font-semibold text-gray-800 dark:text-gray-100"></p>
                    <p id="pc-meta" class="text-xs text-gray-500 dark:text-gray-400"></p>
                    <p id="pc-last" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
                </div>
                <button type="button" onclick="clearPatient()" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Change</button>
            </div>
            <div id="patient-search-wrap">
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text" id="patient-search" autocomplete="off" class="form-input ps-9 w-full"
                           placeholder="Search name or contact no., or scan the Barangay ID">
                </div>
                <div id="patient-results" class="mt-2 space-y-1"></div>
            </div>
        </div>

        {{-- Walk-in --}}
        <div id="walkin-box" class="grid sm:grid-cols-4 gap-4 {{ $walkIn ? '' : 'hidden' }}">
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Name <span class="text-danger">*</span></label>
                <input type="text" name="patient_name" id="patient_name" value="{{ old('patient_name', $C->patient_name) }}" maxlength="255" class="form-input w-full" {{ $walkIn ? '' : 'disabled' }}>
            </div>
            <div>
                <label class="{{ $lbl }}">Age</label>
                <input type="number" name="patient_age" value="{{ old('patient_age', $C->patient_age) }}" min="0" max="130" class="form-input w-full" {{ $walkIn ? '' : 'disabled' }}>
            </div>
            <div>
                <label class="{{ $lbl }}">Sex</label>
                <select name="patient_sex" class="form-select w-full" {{ $walkIn ? '' : 'disabled' }}>
                    <option value="">—</option>
                    <option value="M" @selected(old('patient_sex', $C->patient_sex) === 'M')>Male</option>
                    <option value="F" @selected(old('patient_sex', $C->patient_sex) === 'F')>Female</option>
                </select>
            </div>
        </div>
    </div>
</div>
