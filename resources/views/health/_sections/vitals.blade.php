{{-- Check-up form section. Inherits the variables set up in health._form. --}}
<div class="card p-6">
    <h4 class="card-title mb-4">Vital signs <span class="text-xs font-normal text-gray-400 ms-1">fill in what was measured</span></h4>
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-x-5 gap-y-4">
        <div>
            <label class="{{ $lbl }}">Blood pressure <span class="text-xs font-normal text-gray-400">mmHg</span></label>
            <div class="flex items-center gap-1.5">
                <input type="number" name="bp_systolic" value="{{ old('bp_systolic', $C->bp_systolic) }}" min="50" max="300" class="form-input w-full text-center vital" data-v="bp">
                <span class="text-gray-400">/</span>
                <input type="number" name="bp_diastolic" value="{{ old('bp_diastolic', $C->bp_diastolic) }}" min="30" max="200" class="form-input w-full text-center vital" data-v="bp">
            </div>
            <p class="mt-1 min-h-[22px]" data-flag="bp"></p>
        </div>
        <div>
            <label class="{{ $lbl }}">Temperature <span class="text-xs font-normal text-gray-400">°C</span></label>
            <input type="number" step="0.1" name="temperature" value="{{ old('temperature', $num($C->temperature)) }}" min="30" max="45" class="form-input w-full vital" data-v="temp">
            <p class="mt-1 min-h-[22px]" data-flag="temp"></p>
        </div>
        <div>
            <label class="{{ $lbl }}">Pulse <span class="text-xs font-normal text-gray-400">beats/min</span></label>
            <input type="number" name="pulse" value="{{ old('pulse', $C->pulse) }}" min="20" max="250" class="form-input w-full vital" data-v="pulse">
            <p class="mt-1 min-h-[22px]" data-flag="pulse"></p>
        </div>
        <div>
            <label class="{{ $lbl }}">Respiratory rate <span class="text-xs font-normal text-gray-400">breaths/min</span></label>
            <input type="number" name="resp_rate" value="{{ old('resp_rate', $C->resp_rate) }}" min="5" max="80" class="form-input w-full">
        </div>
        <div>
            <label class="{{ $lbl }}">Oxygen saturation <span class="text-xs font-normal text-gray-400">%</span></label>
            <input type="number" name="o2_sat" value="{{ old('o2_sat', $C->o2_sat) }}" min="50" max="100" class="form-input w-full vital" data-v="o2">
            <p class="mt-1 min-h-[22px]" data-flag="o2"></p>
        </div>
        <div>
            <label class="{{ $lbl }}">Blood sugar <span class="text-xs font-normal text-gray-400">mg/dL</span></label>
            <input type="number" step="0.1" name="blood_sugar" value="{{ old('blood_sugar', $num($C->blood_sugar)) }}" min="10" max="1000" class="form-input w-full vital" data-v="sugar">
            <div class="flex items-center justify-between gap-2 mt-1 min-h-[22px]">
                <span data-flag="sugar"></span>
                <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                    <input type="checkbox" name="sugar_fasting" value="1" id="sugar_fasting" class="form-checkbox rounded text-primary vital" data-v="sugar" @checked(old('sugar_fasting', $C->sugar_fasting))> Fasting
                </label>
            </div>
        </div>
        <div>
            <label class="{{ $lbl }}">Weight <span class="text-xs font-normal text-gray-400">kg</span></label>
            <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $num($C->weight_kg)) }}" min="0.5" max="400" class="form-input w-full vital" data-v="bmi">
        </div>
        <div>
            <label class="{{ $lbl }}">Height <span class="text-xs font-normal text-gray-400">cm</span></label>
            <input type="number" step="0.1" name="height_cm" value="{{ old('height_cm', $num($C->height_cm)) }}" min="30" max="250" class="form-input w-full vital" data-v="bmi">
        </div>
        <div>
            <label class="{{ $lbl }}">BMI</label>
            <div class="form-input w-full bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-100" id="bmi-out">—</div>
            <p class="mt-1 min-h-[22px]" data-flag="bmi"></p>
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">Flags are screening guides for adults, not a diagnosis.</p>
</div>
