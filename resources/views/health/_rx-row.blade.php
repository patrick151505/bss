{{-- One prescription (reseta) line on the check-up form. Expects $i, $row. --}}
<div class="rx-row grid grid-cols-12 gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="col-span-12 sm:col-span-7 flex gap-2 items-center">
        <span class="rx-num w-5 shrink-0 text-xs font-semibold text-gray-400 text-right"></span>
        <input type="text" name="prescriptions[{{ $i }}][medicine]" value="{{ $row['medicine'] ?? '' }}" maxlength="255" list="rx-medicine-list"
               class="form-input flex-1 min-w-0 text-sm rx-medicine" placeholder="Generic name, strength, form — e.g. Amoxicillin 500 mg capsule">
    </div>
    <div class="col-span-5 sm:col-span-2">
        <input type="text" name="prescriptions[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" maxlength="50"
               class="form-input w-full text-sm" placeholder="Qty, e.g. #21">
    </div>
    <div class="col-span-7 sm:col-span-3 flex gap-2">
        <button type="button" onclick="this.closest('.rx-row').remove(); renumberRx();" class="btn btn-sm bg-danger/10 text-danger hover:bg-danger hover:text-white ms-auto" title="Remove">
            <i class="mgc_close_line"></i>
        </button>
    </div>
    <div class="col-span-12">
        <input type="text" name="prescriptions[{{ $i }}][sig]" value="{{ $row['sig'] ?? '' }}" maxlength="255" list="rx-sig-list"
               class="form-input w-full text-sm" placeholder="How to take — e.g. 1 capsule 3 times a day for 7 days">
    </div>
</div>
