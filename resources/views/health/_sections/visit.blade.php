{{-- Check-up form section. Inherits the variables set up in health._form. --}}
<div class="card p-6 space-y-4">
    <div>
        <label class="{{ $lbl }}">Date &amp; time <span class="text-danger">*</span></label>
        <input type="datetime-local" name="checked_at" required value="{{ old('checked_at', $C->checked_at?->format('Y-m-d\TH:i')) }}" class="form-input w-full">
    </div>
    <div>
        <label class="{{ $lbl }}">Attended by</label>
        <input type="text" name="attended_by" value="{{ old('attended_by', $C->attended_by) }}" maxlength="255" list="attended-list" class="form-input w-full" placeholder="Doctor, nurse or BHW">
        <datalist id="attended-list">
            @foreach(\App\Models\HealthCheckup::whereNotNull('attended_by')->select('attended_by')->distinct()->orderBy('attended_by')->limit(30)->pluck('attended_by') as $a)
            <option value="{{ $a }}">
            @endforeach
        </datalist>
    </div>
</div>
