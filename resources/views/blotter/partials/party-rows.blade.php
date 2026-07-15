{{--
    Inline party rows (no card wrapper).
    Props: $role, $color, $parties (array/collection)
--}}
@php $parties = $parties ?? []; @endphp

@if(count($parties) > 0)
    @foreach($parties as $i => $party)
    <div class="party-row border border-gray-200 dark:border-gray-700 rounded-xl p-4 relative bg-white dark:bg-gray-800">
        @if($i > 0)
        <button type="button" class="remove-party-btn absolute top-3 right-3 w-6 h-6 rounded-full text-gray-300 hover:text-danger hover:bg-danger/10 flex items-center justify-center transition"
                onclick="removePartyRow(this)">
            <i class="mgc_close_line text-xs"></i>
        </button>
        @endif

        <input type="hidden" name="{{ $role }}s[{{ $i }}][citizen_id]" class="citizen-id-input" value="{{ $party->citizen_id ?? '' }}">

        <div class="mb-3">
            <label class="text-gray-400 text-xs font-medium inline-block mb-1.5">
                Search registered citizen <span class="text-gray-300">(optional)</span>
            </label>
            <div class="relative">
                <input type="text"
                       class="form-input text-sm w-full citizen-search {{ ($party->citizen_id ?? null) ? 'hidden' : '' }}"
                       placeholder="Type name to search…" autocomplete="off">
                <div class="citizen-dd hidden absolute top-full left-0 right-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-y-auto text-sm"
                     style="z-index:9999;max-height:200px"></div>
            </div>
            <div class="citizen-chip {{ ($party->citizen_id ?? null) ? '' : 'hidden' }} mt-2 flex items-center gap-2 bg-{{ $color }}/10 border border-{{ $color }}/20 rounded-lg px-3 py-2">
                <i class="mgc_check_circle_line text-{{ $color }} text-sm shrink-0"></i>
                <span class="citizen-chip-name text-sm text-{{ $color }} font-medium flex-1 truncate">{{ $party->name ?? '' }}</span>
                <button type="button" class="citizen-clear text-xs text-gray-400 hover:text-danger shrink-0">
                    <i class="mgc_close_line"></i> Clear
                </button>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 pt-3 space-y-3">
            <div>
                <label class="text-gray-600 dark:text-gray-400 text-xs font-medium inline-block mb-1.5">
                    Full Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="{{ $role }}s[{{ $i }}][name]"
                       value="{{ $party->name ?? '' }}"
                       placeholder="Full name"
                       class="form-input text-sm party-name"
                       {{ ($party->citizen_id ?? null) ? 'readonly' : '' }} required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-gray-600 dark:text-gray-400 text-xs font-medium inline-block mb-1.5">Address</label>
                    <input type="text" name="{{ $role }}s[{{ $i }}][address]"
                           value="{{ $party->address ?? '' }}"
                           placeholder="Home address"
                           class="form-input text-sm party-address"
                           {{ ($party->citizen_id ?? null) ? 'readonly' : '' }}>
                </div>
                <div>
                    <label class="text-gray-600 dark:text-gray-400 text-xs font-medium inline-block mb-1.5">Contact No.</label>
                    <input type="text" name="{{ $role }}s[{{ $i }}][contact]"
                           value="{{ $party->contact ?? '' }}"
                           placeholder="09XXXXXXXXX"
                           class="form-input text-sm party-contact"
                           {{ ($party->citizen_id ?? null) ? 'readonly' : '' }}>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@else
{{-- Default empty first row --}}
<div class="party-row border border-gray-200 dark:border-gray-700 rounded-xl p-4 relative bg-white dark:bg-gray-800">
    <input type="hidden" name="{{ $role }}s[0][citizen_id]" class="citizen-id-input" value="">

    <div class="mb-3">
        <label class="text-gray-400 text-xs font-medium inline-block mb-1.5">
            Search registered citizen <span class="text-gray-300">(optional)</span>
        </label>
        <div class="relative">
            <input type="text" class="form-input text-sm w-full citizen-search"
                   placeholder="Type name to search…" autocomplete="off">
            <div class="citizen-dd hidden absolute top-full left-0 right-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-y-auto text-sm"
                 style="z-index:9999;max-height:200px"></div>
        </div>
        <div class="citizen-chip hidden mt-2 flex items-center gap-2 bg-{{ $color }}/10 border border-{{ $color }}/20 rounded-lg px-3 py-2">
            <i class="mgc_check_circle_line text-{{ $color }} text-sm shrink-0"></i>
            <span class="citizen-chip-name text-sm text-{{ $color }} font-medium flex-1 truncate"></span>
            <button type="button" class="citizen-clear text-xs text-gray-400 hover:text-danger shrink-0">
                <i class="mgc_close_line"></i> Clear
            </button>
        </div>
    </div>

    <div class="border-t border-gray-100 dark:border-gray-800 pt-3 space-y-3">
        <div>
            <label class="text-gray-600 dark:text-gray-400 text-xs font-medium inline-block mb-1.5">
                Full Name <span class="text-danger">*</span>
            </label>
            <input type="text" name="{{ $role }}s[0][name]"
                   placeholder="Full name" class="form-input text-sm party-name" required>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-gray-600 dark:text-gray-400 text-xs font-medium inline-block mb-1.5">Address</label>
                <input type="text" name="{{ $role }}s[0][address]"
                       placeholder="Home address" class="form-input text-sm party-address">
            </div>
            <div>
                <label class="text-gray-600 dark:text-gray-400 text-xs font-medium inline-block mb-1.5">Contact No.</label>
                <input type="text" name="{{ $role }}s[0][contact]"
                       placeholder="09XXXXXXXXX" class="form-input text-sm party-contact">
            </div>
        </div>
    </div>
</div>
@endif
