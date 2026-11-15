{{-- Check-up form section. Inherits the variables set up in health._form. --}}
<div class="card p-6 space-y-4">
    <h4 class="card-title">Consultation</h4>
    <div>
        <label class="{{ $lbl }}">Complaint <span class="text-xs font-normal text-gray-400">reason for the visit</span></label>
        <textarea name="complaint" rows="2" class="form-input w-full" placeholder="Headache for 3 days, dizziness…">{{ old('complaint', $C->complaint) }}</textarea>
    </div>
    <div>
        <label class="{{ $lbl }}">Findings / assessment</label>
        <textarea name="findings" rows="2" class="form-input w-full">{{ old('findings', $C->findings) }}</textarea>
    </div>
    <div>
        <label class="{{ $lbl }}">Advice / treatment</label>
        <textarea name="advice" rows="2" class="form-input w-full" placeholder="Rest, increase fluids, low-salt diet…">{{ old('advice', $C->advice) }}</textarea>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="{{ $lbl }}">Referred to</label>
            <input type="text" name="referred_to" value="{{ old('referred_to', $C->referred_to) }}" maxlength="255" class="form-input w-full" placeholder="e.g. Rural Health Unit, district hospital">
        </div>
        <div>
            <label class="{{ $lbl }}">Follow-up on</label>
            <input type="date" name="follow_up_on" value="{{ old('follow_up_on', $C->follow_up_on?->format('Y-m-d')) }}" class="form-input w-full">
        </div>
    </div>
</div>
