{{--
    Reusable multi-party card.
    Props:
      $role   = 'complainant' | 'respondent'
      $color  = 'success' | 'danger'
      $label  = 'Complainant' | 'Respondent'
      $parties = collection/array of existing parties (for edit), or []
--}}
@php $parties = $parties ?? []; @endphp

<div class="card">
    <div class="card-header flex items-center justify-between">
        <h5 class="card-title text-sm">
            <i class="mgc_user_3_line me-2 text-{{ $color }}"></i>{{ $label }}
        </h5>
        <button type="button" onclick="addPartyRow('{{ $role }}')"
                class="btn btn-sm bg-{{ $color }}/10 text-{{ $color }} hover:bg-{{ $color }}/20 gap-1">
            <i class="mgc_add_line text-xs"></i> Add {{ $label }}
        </button>
    </div>
    <div class="p-5">
        <div id="{{ $role }}-rows" class="flex flex-col gap-4">
            @if(count($parties) > 0)
                @foreach($parties as $i => $party)
                <div class="party-row border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative">
                    @if($i > 0)
                    <button type="button" onclick="removePartyRow(this)"
                            class="absolute top-3 right-3 text-gray-300 hover:text-danger">
                        <i class="mgc_close_line text-sm"></i>
                    </button>
                    @endif

                    <input type="hidden" name="{{ $role }}s[{{ $i }}][citizen_id]"
                           class="citizen-id-input" value="{{ $party->citizen_id ?? '' }}">

                    {{-- Citizen lookup chip --}}
                    <div class="mb-3">
                        <label class="form-label text-xs text-gray-400">Search registered citizen <span class="text-gray-300">(optional)</span></label>
                        <div class="relative">
                            <input type="text"
                                   class="form-input text-sm w-full citizen-search {{ ($party->citizen_id ?? null) ? 'hidden' : '' }}"
                                   placeholder="Type name to search…" autocomplete="off">
                            <div class="citizen-dd hidden absolute top-full left-0 right-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-y-auto text-sm"
                                 style="z-index:9999;max-height:200px"></div>
                        </div>
                        <div class="citizen-chip {{ ($party->citizen_id ?? null) ? '' : 'hidden' }} mt-2 flex items-center gap-2 bg-{{ $color }}/10 border border-{{ $color }}/30 rounded-lg px-3 py-2">
                            <i class="mgc_check_circle_line text-{{ $color }} text-sm shrink-0"></i>
                            <span class="citizen-chip-name text-sm text-{{ $color }} font-medium flex-1 truncate">{{ $party->name ?? '' }}</span>
                            <button type="button" class="citizen-clear text-xs text-gray-400 hover:text-danger shrink-0">
                                <i class="mgc_close_line"></i> Clear
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 pt-3 grid grid-cols-1 gap-3">
                        <div>
                            <label class="form-label text-xs">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="{{ $role }}s[{{ $i }}][name]"
                                   value="{{ $party->name ?? '' }}"
                                   placeholder="Full name"
                                   class="form-input text-sm party-name"
                                   {{ ($party->citizen_id ?? null) ? 'readonly' : '' }}
                                   required>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label text-xs">Address</label>
                                <input type="text" name="{{ $role }}s[{{ $i }}][address]"
                                       value="{{ $party->address ?? '' }}"
                                       placeholder="Home address"
                                       class="form-input text-sm party-address"
                                       {{ ($party->citizen_id ?? null) ? 'readonly' : '' }}>
                            </div>
                            <div>
                                <label class="form-label text-xs">Contact No.</label>
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
            <div class="party-row border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative">
                <input type="hidden" name="{{ $role }}s[0][citizen_id]" class="citizen-id-input" value="">

                <div class="mb-3">
                    <label class="form-label text-xs text-gray-400">Search registered citizen <span class="text-gray-300">(optional)</span></label>
                    <div class="relative">
                        <input type="text" class="form-input text-sm w-full citizen-search"
                               placeholder="Type name to search…" autocomplete="off">
                        <div class="citizen-dd hidden absolute top-full left-0 right-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-y-auto text-sm"
                             style="z-index:9999;max-height:200px"></div>
                    </div>
                    <div class="citizen-chip hidden mt-2 flex items-center gap-2 bg-{{ $color }}/10 border border-{{ $color }}/30 rounded-lg px-3 py-2">
                        <i class="mgc_check_circle_line text-{{ $color }} text-sm shrink-0"></i>
                        <span class="citizen-chip-name text-sm text-{{ $color }} font-medium flex-1 truncate"></span>
                        <button type="button" class="citizen-clear text-xs text-gray-400 hover:text-danger shrink-0">
                            <i class="mgc_close_line"></i> Clear
                        </button>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-800 pt-3 grid grid-cols-1 gap-3">
                    <div>
                        <label class="form-label text-xs">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="{{ $role }}s[0][name]"
                               placeholder="Full name" class="form-input text-sm party-name" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs">Address</label>
                            <input type="text" name="{{ $role }}s[0][address]"
                                   placeholder="Home address" class="form-input text-sm party-address">
                        </div>
                        <div>
                            <label class="form-label text-xs">Contact No.</label>
                            <input type="text" name="{{ $role }}s[0][contact]"
                                   placeholder="09XXXXXXXXX" class="form-input text-sm party-contact">
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
