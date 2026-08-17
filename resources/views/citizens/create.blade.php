@extends('layouts.vertical', ['title' => 'Add Citizen', 'sub_title' => 'Citizen Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

@php
    $addressMap = $addresses->keyBy('id')->map(fn($a) => ['is_subd' => $a->is_subd, 'rules' => $a->rules_required]);
@endphp

{{-- Validation error summary --}}
@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <div>
        <p class="text-sm font-medium text-danger mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form action="{{ route('citizens.store') }}" method="POST" enctype="multipart/form-data" id="citizen-create-form">
    @csrf

    <div class="grid grid-cols-12 gap-6">

        {{-- ── Left Column ── --}}
        <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">

            {{-- Household Assignment --}}
            <div class="card border-2 border-dashed border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-success/10 text-success flex items-center justify-center shrink-0">
                            <i class="mgc_home_3_line text-lg"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">Household Assignment</p>
                            <p class="text-xs text-gray-400">Optional — can be set later from the Household module</p>
                        </div>
                    </div>
                    <span class="badge bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-semibold px-2 py-1 rounded-full">Optional</span>
                </div>

                {{-- Summary shown after selection --}}
                <div id="hh-summary" class="hidden mx-6 mb-4 rounded-xl border border-success/30 bg-success/5 p-4">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 rounded-lg bg-success/15 text-success flex items-center justify-center shrink-0 mt-0.5">
                            <i class="mgc_check_line"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-success uppercase tracking-wide mb-1" id="hh-summary-role"></p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="hh-summary-main"></p>
                            <p class="text-xs text-gray-500 mt-0.5" id="hh-summary-sub"></p>
                        </div>
                        <button type="button" onclick="clearHouseholdAssignment()"
                            class="text-gray-400 hover:text-danger transition-colors shrink-0 mt-0.5" title="Clear">
                            <i class="mgc_close_line text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="px-6 pb-5">
                    <button type="button" id="hh-open-btn" onclick="openHouseholdModal()"
                        class="btn border-2 border-dashed border-success/40 text-success hover:bg-success hover:text-white hover:border-success w-full gap-2 justify-center transition-colors">
                        <i class="mgc_add_line"></i> Assign Household
                    </button>
                </div>

                {{-- Hidden fields submitted with form --}}
                <input type="hidden" name="hh_role"          id="hh_role">
                <input type="hidden" name="hh_household_id"  id="hh_household_id">
                <input type="hidden" name="hh_family_id"     id="hh_family_id">
                <input type="hidden" name="hh_relation_id"   id="hh_relation_id">
            </div>

            {{-- Personal Information --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_user_3_line me-2 text-primary"></i>Personal Information</h5>
                </div>
                <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="fname" value="{{ old('fname') }}"
                            class="form-input @error('fname') border-danger @enderror" required>
                        @error('fname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="mname" value="{{ old('mname') }}"
                            class="form-input @error('mname') border-danger @enderror">
                        @error('mname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lname" value="{{ old('lname') }}"
                            class="form-input @error('lname') border-danger @enderror" required>
                        @error('lname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" value="{{ old('suffix') }}"
                            class="form-input" placeholder="Jr., Sr., III">
                    </div>

                    <div>
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') border-danger @enderror" required>
                            <option value="">— Select —</option>
                            <option value="1" {{ old('gender') == '1' ? 'selected' : '' }}>Male</option>
                            <option value="0" {{ old('gender') === '0' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                        <select name="civil_status" class="form-select @error('civil_status') border-danger @enderror" required>
                            <option value="">— Select —</option>
                            @foreach($civilStatuses as $cs)
                                <option value="{{ $cs->id }}" {{ old('civil_status') == $cs->id ? 'selected' : '' }}>
                                    {{ $cs->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('civil_status') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Birthday <span class="text-danger">*</span></label>
                        <input type="date" name="bday" value="{{ old('bday') }}"
                            class="form-input @error('bday') border-danger @enderror" required>
                        @error('bday') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Place of Birth <span class="text-danger">*</span></label>
                        <input type="text" name="bplace" value="{{ old('bplace') }}"
                            class="form-input @error('bplace') border-danger @enderror" required>
                        @error('bplace') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Citizenship <span class="text-danger">*</span></label>
                        <input type="text" name="citizenship" value="{{ old('citizenship', 'Filipino') }}"
                            class="form-input @error('citizenship') border-danger @enderror" placeholder="e.g. Filipino">
                        @error('citizenship') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation') }}"
                            class="form-input">
                    </div>

                </div>
            </div>

            {{-- Address --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_home_3_line me-2 text-primary"></i>Address & Residency</h5>
                </div>
                <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div class="lg:col-span-3">
                        <label class="form-label">Zone / Area <span class="text-danger">*</span></label>
                        <select name="address" id="address-select"
                            class="form-select @error('address') border-danger @enderror" required>
                            <option value="">— Select Zone —</option>
                            @foreach($addresses as $addr)
                                <option value="{{ $addr->id }}"
                                    data-subd="{{ $addr->is_subd }}"
                                    data-rules="{{ $addr->rules_required }}"
                                    {{ old('address') == $addr->id ? 'selected' : '' }}>
                                    {{ $addr->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('address') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Subdivision fields (blk, lot, phase) --}}
                    <div id="field-blk" class="hidden">
                        <label class="form-label">Block No.</label>
                        <input type="number" name="blk" value="{{ old('blk') }}"
                            class="form-input" min="1">
                        @error('blk') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-lot" class="hidden">
                        <label class="form-label">Lot No.</label>
                        <input type="number" name="lot" value="{{ old('lot') }}"
                            class="form-input" min="1">
                        @error('lot') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-phase" class="hidden">
                        <label class="form-label">Phase</label>
                        <input type="number" name="phase" value="{{ old('phase') }}"
                            class="form-input" min="1">
                        @error('phase') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Non-subd fields (no, street) --}}
                    <div id="field-no" class="hidden">
                        <label class="form-label">House No.</label>
                        <input type="text" name="no" value="{{ old('no') }}"
                            class="form-input">
                        @error('no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-street" class="hidden">
                        <label class="form-label">Street</label>
                        <input type="text" name="street" value="{{ old('street') }}"
                            class="form-input">
                        @error('street') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Complete address (for is_subd == 2) --}}
                    <div id="field-complete_address" class="lg:col-span-3 hidden">
                        <label class="form-label">Complete Address</label>
                        <textarea name="complete_address" rows="2"
                            class="form-input w-full">{{ old('complete_address') }}</textarea>
                        @error('complete_address') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Month / Year of Stay <span class="text-danger">*</span></label>
                        <input type="month" name="year_stay" value="{{ old('year_stay') }}"
                            class="form-input @error('year_stay') border-danger @enderror" required>
                        @error('year_stay') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Owner Status <span class="text-danger">*</span></label>
                        <select name="owner_status" class="form-select @error('owner_status') border-danger @enderror" required>
                            <option value="">— Select —</option>
                            <option value="1" {{ old('owner_status') == '1' ? 'selected' : '' }}>Owner</option>
                            <option value="0" {{ old('owner_status') === '0' ? 'selected' : '' }}>Renter / Others</option>
                        </select>
                        @error('owner_status') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Contact --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_phone_line me-2 text-primary"></i>Contact Information</h5>
                </div>
                <div class="grid lg:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">Contact No.</label>
                        <input type="text" name="contact" value="{{ old('contact') }}"
                            class="form-input @error('contact') border-danger @enderror"
                            placeholder="09XXXXXXXXX" maxlength="11">
                        @error('contact') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="form-input @error('email') border-danger @enderror">
                        @error('email') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Status & Flags --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_flag_line me-2 text-primary"></i>Status & Flags</h5>
                </div>
                <div class="p-6 space-y-5">

                    {{-- Approval + Active (kept as selects — they have >2 states / clear labels) --}}
                    <div class="grid md:grid-cols-2 grid-cols-1 gap-4">
                        <div>
                            <label class="form-label">Approval Status</label>
                            <select name="approval_status" class="form-select">
                                @foreach($approvalStatuses as $as)
                                    <option value="{{ $as->id }}" {{ old('approval_status', 2) == $as->id ? 'selected' : '' }}>
                                        {{ $as->description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Active Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    {{-- Flags as toggle switches --}}
                    <div class="grid md:grid-cols-2 grid-cols-1 gap-x-8 gap-y-3 pt-1">
                        @php
                            $flagRows = [
                                ['name' => 'is_pwd',         'label' => 'PWD',                'hint' => 'Person with disability'],
                                ['name' => 'is_soloparents', 'label' => 'Solo Parent',        'hint' => 'Registered solo parent'],
                                ['name' => 'is_verify',      'label' => 'Verified',           'hint' => 'Record has been verified'],
                            ];
                        @endphp
                        @foreach($flagRows as $f)
                        <label class="flex items-center justify-between gap-3 py-1 cursor-pointer">
                            <span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $f['label'] }}</span>
                                <span class="block text-xs text-gray-400">{{ $f['hint'] }}</span>
                            </span>
                            <input type="hidden" name="{{ $f['name'] }}" value="0">
                            <input type="checkbox" role="switch" name="{{ $f['name'] }}" value="1"
                                   class="form-switch text-primary shrink-0" {{ old($f['name'], 0) == 1 ? 'checked' : '' }}>
                        </label>
                        @endforeach

                        {{-- Voter toggle reveals the precinct field --}}
                        <label class="flex items-center justify-between gap-3 py-1 cursor-pointer">
                            <span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Voter</span>
                                <span class="block text-xs text-gray-400">Registered voter</span>
                            </span>
                            <input type="hidden" name="voters" value="0">
                            <input type="checkbox" role="switch" id="voters-select" name="voters" value="1"
                                   class="form-switch text-primary shrink-0" {{ old('voters', 0) == 1 ? 'checked' : '' }}>
                        </label>
                    </div>

                    {{-- Precinct No. — shown only when Voter is on --}}
                    <div id="field-precinct" class="md:w-1/2 {{ old('voters', 0) == 1 ? '' : 'hidden' }}">
                        <label class="form-label">Precinct No. <span class="text-danger">*</span></label>
                        <input type="text" name="pricinct_no" value="{{ old('pricinct_no') }}"
                            class="form-input @error('pricinct_no') border-danger @enderror"
                            placeholder="0055F" maxlength="6">
                        @error('pricinct_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Emergency Contact --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_alert_line me-2 text-danger"></i>In Case of Emergency</h5>
                </div>
                <div class="grid lg:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">Full Name</label>
                        <input type="text" name="ic_fullname" value="{{ old('ic_fullname') }}"
                            class="form-input @error('ic_fullname') border-danger @enderror">
                        @error('ic_fullname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Relationship</label>
                        <input type="text" name="ic_relationship" value="{{ old('ic_relationship') }}"
                            class="form-input @error('ic_relationship') border-danger @enderror"
                            placeholder="e.g. Spouse, Parent, Sibling">
                        @error('ic_relationship') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Contact No.</label>
                        <input type="text" name="ic_contact" value="{{ old('ic_contact') }}"
                            class="form-input @error('ic_contact') border-danger @enderror"
                            placeholder="09XXXXXXXXX" maxlength="11">
                        @error('ic_contact') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="ic_email" value="{{ old('ic_email') }}"
                            class="form-input @error('ic_email') border-danger @enderror">
                        @error('ic_email') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="lg:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="ic_address" rows="2"
                            class="form-input w-full @error('ic_address') border-danger @enderror">{{ old('ic_address') }}</textarea>
                        @error('ic_address') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Notes --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_document_line me-2 text-primary"></i>Notes</h5>
                </div>
                <div class="p-6">
                    <textarea name="note" rows="3"
                        class="form-input w-full">{{ old('note') }}</textarea>
                </div>
            </div>

        </div>

        {{-- ── Right Column ── --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

            {{-- Profile Photo --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_pic_line me-2 text-primary"></i>Profile Photo</h5>
                </div>
                <div class="p-6 flex flex-col items-center gap-4">

                    <div id="profile-placeholder"
                        class="w-28 h-28 rounded-full bg-primary/20 flex items-center justify-center text-primary shadow">
                        <i class="mgc_user_3_line text-4xl"></i>
                    </div>
                    <img id="profile-preview" src="" class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow hidden" alt="Profile">

                    <label for="profile-input" class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer w-full text-center">
                        <i class="mgc_upload_2_line me-1"></i> Upload Photo
                    </label>
                    <input type="file" id="profile-input" name="profile"
                        class="hidden" accept="image/jpeg,image/png,image/jpg">
                    <p class="text-xs text-gray-400 text-center">JPG or PNG. Max 2MB.</p>

                </div>
            </div>

            {{-- Tags --}}
            @include('citizens._tags_card', ['selectedTagIds' => old('tags', [])])

            {{-- Actions --}}
            <div class="card">
                <div class="p-6 flex flex-col gap-3">
                    <button type="submit" class="btn bg-primary text-white w-full">
                        <i class="mgc_user_add_line me-1"></i> Save Citizen
                    </button>
                    <a href="{{ route('citizens.index') }}"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 w-full text-center">
                        <i class="mgc_arrow_left_line me-1"></i> Back to List
                    </a>
                </div>
            </div>

        </div>

    </div>

</form>

{{-- ═══════════════════════════════════════════════════════════════════
     HOUSEHOLD ASSIGNMENT MODAL
════════════════════════════════════════════════════════════════════ --}}
<div id="hh-modal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeHouseholdModal()"></div>

    {{-- Panel --}}
    <div class="absolute inset-y-0 right-0 w-full max-w-lg flex flex-col bg-white dark:bg-gray-900 shadow-2xl">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-success/10 text-success flex items-center justify-center">
                    <i class="mgc_home_3_line text-lg"></i>
                </span>
                <div>
                    <p class="font-bold text-gray-800 dark:text-gray-100">Household Assignment</p>
                    <p class="text-xs text-gray-400">Step <span id="hh-step-label">1</span> of <span id="hh-step-total">2</span></p>
                </div>
            </div>
            <button type="button" onclick="closeHouseholdModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>

        {{-- Step progress bar --}}
        <div class="h-1 bg-gray-100 dark:bg-gray-700 shrink-0">
            <div id="hh-progress" class="h-full bg-success transition-all duration-300" style="width:50%"></div>
        </div>

        {{-- Body (scrollable) --}}
        <div class="flex-1 overflow-y-auto">

            {{-- ── STEP 1: Role picker ── --}}
            <div id="hh-step-1" class="p-6">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">What is this person's role?</p>
                <p class="text-xs text-gray-400 mb-5">Select the role that best describes how this citizen relates to a household.</p>

                <div class="space-y-3" id="hh-role-cards">
                    {{-- Household Head --}}
                    <button type="button" class="hh-role-card w-full text-left" data-role="household_head" data-color="success" onclick="selectHhRole(this)">
                        <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-success/50 transition-all">
                            <span class="hh-role-icon w-11 h-11 rounded-xl bg-success/10 text-success flex items-center justify-center shrink-0 text-xl transition-all">
                                <i class="mgc_home_3_fill"></i>
                            </span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm">Head of Household</p>
                                <p class="text-xs text-gray-400 mt-0.5">Owns or manages the physical address. A new household will be created for this citizen.</p>
                            </div>
                            <span class="hh-role-check w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 transition-all">
                                <i class="mgc_check_line text-white text-xs hidden"></i>
                            </span>
                        </div>
                    </button>

                    {{-- Family Head --}}
                    <button type="button" class="hh-role-card w-full text-left" data-role="family_head" data-color="primary" onclick="selectHhRole(this)">
                        <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary/50 transition-all">
                            <span class="hh-role-icon w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl transition-all">
                                <i class="mgc_group_2_fill"></i>
                            </span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm">Head of Family</p>
                                <p class="text-xs text-gray-400 mt-0.5">Leads a family unit within an existing household. Select which household to join.</p>
                            </div>
                            <span class="hh-role-check w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 transition-all">
                                <i class="mgc_check_line text-white text-xs hidden"></i>
                            </span>
                        </div>
                    </button>

                    {{-- Member --}}
                    <button type="button" class="hh-role-card w-full text-left" data-role="member" data-color="warning" onclick="selectHhRole(this)">
                        <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-warning/50 transition-all">
                            <span class="hh-role-icon w-11 h-11 rounded-xl bg-warning/10 text-warning flex items-center justify-center shrink-0 text-xl transition-all">
                                <i class="mgc_user_3_fill"></i>
                            </span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm">Family Member</p>
                                <p class="text-xs text-gray-400 mt-0.5">Belongs to an existing family under a household. Must select household → family → relation.</p>
                            </div>
                            <span class="hh-role-check w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 transition-all">
                                <i class="mgc_check_line text-white text-xs hidden"></i>
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            {{-- ── STEP 2a: Household Head — no extra input needed ── --}}
            <div id="hh-step-2-household-head" class="p-6 hidden">
                <div class="rounded-xl bg-success/5 border border-success/20 p-5 text-center">
                    <span class="text-4xl block mb-3">🏠</span>
                    <p class="font-bold text-gray-800 dark:text-gray-100 mb-1">New Household will be created</p>
                    <p class="text-sm text-gray-500">A household record will be automatically created using this citizen's address after saving. This citizen will be set as the Household Head.</p>
                </div>
            </div>

            {{-- ── STEP 2b: Family Head — pick household ── --}}
            <div id="hh-step-2-family-head" class="p-6 hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Search Household</p>
                <p class="text-xs text-gray-400 mb-4">Type an address, zone, or HH number to find the household.</p>

                <div class="relative mb-4">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="hh-search-fh" placeholder="e.g. Zone 1, Blk 3, HH-00012…"
                        class="form-input pl-9 w-full" autocomplete="off">
                </div>

                <div id="hh-search-results-fh" class="space-y-2 max-h-72 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-6">Start typing to search households…</p>
                </div>
            </div>

            {{-- ── STEP 2c: Member — pick household ── --}}
            <div id="hh-step-2-member" class="p-6 hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Search Household</p>
                <p class="text-xs text-gray-400 mb-4">Find the household this citizen belongs to.</p>

                <div class="relative mb-4">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="hh-search-m" placeholder="e.g. Zone 1, Blk 3, HH-00012…"
                        class="form-input pl-9 w-full" autocomplete="off">
                </div>

                <div id="hh-search-results-m" class="space-y-2 max-h-60 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-6">Start typing to search households…</p>
                </div>
            </div>

            {{-- ── STEP 3: Member — pick family + relation ── --}}
            <div id="hh-step-3-member" class="p-6 hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Select Family & Relation</p>

                {{-- Selected household recap --}}
                <div id="hh-selected-household-recap" class="rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3 mb-4 flex items-center gap-3">
                    <i class="mgc_home_3_line text-success text-lg"></i>
                    <div>
                        <p class="text-xs text-gray-400">Household</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="hh-recap-address">—</p>
                    </div>
                </div>

                <label class="form-label">Family <span class="text-danger">*</span></label>
                <select id="hh-family-select" class="form-select mb-4">
                    <option value="">— Select Family —</option>
                </select>

                <label class="form-label">Relation to Family Head <span class="text-danger">*</span></label>
                <select id="hh-relation-select" class="form-select">
                    <option value="">— Select Relation —</option>
                    @foreach($relations as $rel)
                        <option value="{{ $rel->id }}">{{ $rel->description }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex gap-3 shrink-0">
            <button type="button" id="hh-back-btn" onclick="hhBack()"
                class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 gap-1 hidden">
                <i class="mgc_left_line"></i> Back
            </button>
            <div class="flex-1"></div>
            <button type="button" onclick="closeHouseholdModal()"
                class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                Cancel
            </button>
            <button type="button" id="hh-next-btn" onclick="hhNext()"
                class="btn bg-success text-white gap-1">
                Next <i class="mgc_right_line"></i>
            </button>
            <button type="button" id="hh-confirm-btn" onclick="hhConfirm()"
                class="btn bg-success text-white gap-1 hidden">
                <i class="mgc_check_line"></i> Confirm
            </button>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
window.addEventListener('DOMContentLoaded', () => {
    $(document).ready(function () {

        // Show/hide address sub-fields based on selected zone
        function toggleAddressFields(selectEl) {
            const selected = $(selectEl).find('option:selected');
            const isSubd   = parseInt(selected.data('subd') || 0);
            const rules    = (selected.data('rules') || '').split(',').map(r => r.trim()).filter(Boolean);

            // Hide all address sub-fields first, clear required
            $('#field-blk, #field-lot, #field-phase, #field-no, #field-street, #field-complete_address')
                .addClass('hidden');
            $('#field-blk input, #field-lot input, #field-phase input, #field-no input, #field-street input')
                .removeAttr('required');
            // Reset labels to no asterisk
            ['blk','lot','phase','no','street'].forEach(f => {
                const lbl = document.querySelector(`#field-${f} label`);
                if (lbl) lbl.innerHTML = lbl.textContent.trim();
            });

            if (isSubd === 1) {
                // For subdivisions: show all three, mark required ones with *
                ['blk', 'lot', 'phase'].forEach(f => {
                    const $field = $(`#field-${f}`);
                    const isRequired = rules.includes(f);
                    $field.removeClass('hidden');
                    const $input = $field.find('input');
                    const lbl    = $field.find('label')[0];
                    if (isRequired) {
                        $input.attr('required', true);
                        if (lbl) lbl.innerHTML = lbl.textContent.trim() + ' <span class="text-danger">*</span>';
                    }
                });
            } else if (isSubd === 2) {
                $('#field-complete_address').removeClass('hidden');
            } else if (isSubd === 0 && selected.val()) {
                $('#field-no, #field-street').removeClass('hidden');
            }
        }

        // Init on page load (in case of old() repopulation after validation fail)
        toggleAddressFields('#address-select');

        $('#address-select').on('change', function () {
            toggleAddressFields(this);
        });

        // Voter toggle → show/hide precinct
        $('#voters-select').on('change', function () {
            $('#field-precinct').toggleClass('hidden', !$(this).is(':checked'));
        });

        // Profile photo preview
        $('#profile-input').on('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                $('#profile-preview').attr('src', e.target.result).removeClass('hidden');
                $('#profile-placeholder').addClass('hidden');
            };
            reader.readAsDataURL(file);
        });

    });
});
</script>

<script>
// ── Household modal state ─────────────────────────────────────────────────────
const HH_SEARCH_URL = '{{ route('households.search') }}';

let hhState = {
    role:          null,   // 'household_head' | 'family_head' | 'member'
    householdId:   null,
    householdAddr: null,
    addressData:   null,   // full household payload for address auto-fill
    families:      [],
    familyId:      null,
    relationId:    null,
    step:          1,
};

function openHouseholdModal() {
    resetHhModal();
    document.getElementById('hh-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeHouseholdModal() {
    document.getElementById('hh-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

function resetHhModal() {
    hhState = { role: null, householdId: null, householdAddr: null, addressData: null, families: [], familyId: null, relationId: null, step: 1 };
    // Reset role card visuals
    document.querySelectorAll('.hh-role-card').forEach(card => {
        const inner = card.querySelector('div');
        inner.style.borderColor = '';
        inner.style.background  = '';
        card.querySelector('.hh-role-icon').style.background = '';
        card.querySelector('.hh-role-icon').style.color      = '';
        card.querySelector('.hh-role-check').style.borderColor = '';
        card.querySelector('.hh-role-check').style.background  = '';
        card.querySelector('.hh-role-check i').classList.add('hidden');
    });
    document.getElementById('hh-search-fh').value = '';
    document.getElementById('hh-search-m').value  = '';
    document.getElementById('hh-family-select').innerHTML = '<option value="">— Select Family —</option>';
    document.getElementById('hh-relation-select').value   = '';
    document.getElementById('hh-search-results-fh').innerHTML = '<p class="text-xs text-gray-400 text-center py-6">Start typing to search households…</p>';
    document.getElementById('hh-search-results-m').innerHTML  = '<p class="text-xs text-gray-400 text-center py-6">Start typing to search households…</p>';
    showHhStep(1);
}

function showHhStep(step) {
    hhState.step = step;
    const role = hhState.role;

    // Hide all step panels
    ['hh-step-1',
     'hh-step-2-household-head', 'hh-step-2-family-head', 'hh-step-2-member',
     'hh-step-3-member',
    ].forEach(id => document.getElementById(id).classList.add('hidden'));

    // Total steps per role
    const totals = { household_head: 2, family_head: 2, member: 3 };
    const total  = totals[role] ?? 2;
    document.getElementById('hh-step-total').textContent = total;
    document.getElementById('hh-step-label').textContent = step;
    document.getElementById('hh-progress').style.width   = (step / total * 100) + '%';

    // Back button
    document.getElementById('hh-back-btn').classList.toggle('hidden', step === 1);

    // Next / Confirm buttons
    const isLast = step === total;
    document.getElementById('hh-next-btn').classList.toggle('hidden', isLast);
    document.getElementById('hh-confirm-btn').classList.toggle('hidden', !isLast);

    // Show the right panel
    if (step === 1) {
        document.getElementById('hh-step-1').classList.remove('hidden');
        document.getElementById('hh-step-total').textContent = '?';
        document.getElementById('hh-next-btn').classList.remove('hidden');
        document.getElementById('hh-confirm-btn').classList.add('hidden');
        return;
    }

    if (step === 2) {
        if (role === 'household_head') document.getElementById('hh-step-2-household-head').classList.remove('hidden');
        if (role === 'family_head') {
            document.getElementById('hh-step-2-family-head').classList.remove('hidden');
            if (!document.getElementById('hh-search-fh').value.trim())
                loadHhPrePicks(document.getElementById('hh-search-results-fh'));
        }
        if (role === 'member') {
            document.getElementById('hh-step-2-member').classList.remove('hidden');
            if (!document.getElementById('hh-search-m').value.trim())
                loadHhPrePicks(document.getElementById('hh-search-results-m'));
        }
    }

    if (step === 3 && role === 'member') {
        document.getElementById('hh-step-3-member').classList.remove('hidden');
        document.getElementById('hh-recap-address').textContent = hhState.householdAddr ?? '—';
        // Populate family dropdown
        const sel = document.getElementById('hh-family-select');
        sel.innerHTML = '<option value="">— Select Family —</option>';
        hhState.families.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = `FM-${String(f.id).padStart(5,'0')} — Head: ${f.head_name}`;
            sel.appendChild(opt);
        });
    }
}

// Role card selection (JS-driven, no Tailwind has-[] needed)
const colorMap = {
    success: { border: '#10b981', bg: 'rgba(16,185,129,0.06)', iconBg: '#10b981', checkBg: '#10b981' },
    primary: { border: '#4361ee', bg: 'rgba(67,97,238,0.06)',  iconBg: '#4361ee', checkBg: '#4361ee' },
    warning: { border: '#e2a907', bg: 'rgba(226,169,7,0.06)',  iconBg: '#e2a907', checkBg: '#e2a907' },
};

function selectHhRole(btn) {
    // Reset all cards
    document.querySelectorAll('.hh-role-card').forEach(card => {
        const inner = card.querySelector('div');
        inner.style.borderColor = '';
        inner.style.background  = '';
        const icon  = card.querySelector('.hh-role-icon');
        icon.style.background = '';
        icon.style.color      = '';
        const check = card.querySelector('.hh-role-check');
        check.style.borderColor = '';
        check.style.background  = '';
        check.querySelector('i').classList.add('hidden');
    });

    // Highlight selected
    const color  = btn.dataset.color;
    const colors = colorMap[color];
    const inner  = btn.querySelector('div');
    inner.style.borderColor = colors.border;
    inner.style.background  = colors.bg;
    const icon = btn.querySelector('.hh-role-icon');
    icon.style.background = colors.iconBg;
    icon.style.color      = '#fff';
    const check = btn.querySelector('.hh-role-check');
    check.style.borderColor = colors.checkBg;
    check.style.background  = colors.checkBg;
    check.querySelector('i').classList.remove('hidden');

    hhState.role = btn.dataset.role;
}

function hhNext() {
    if (hhState.step === 1) {
        if (!hhState.role) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please select a role', showConfirmButton: false, timer: 2000 });
            return;
        }
        showHhStep(2);
        return;
    }

    if (hhState.step === 2 && hhState.role === 'member') {
        if (!hhState.householdId) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please select a household', showConfirmButton: false, timer: 2000 });
            return;
        }
        showHhStep(3);
        return;
    }
}

function hhBack() {
    showHhStep(hhState.step - 1);
}

function hhConfirm() {
    // Validate based on role
    if (hhState.role === 'household_head') {
        applyHouseholdAssignment();
        return;
    }

    if (hhState.role === 'family_head') {
        if (!hhState.householdId) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please select a household', showConfirmButton: false, timer: 2000 });
            return;
        }
        applyHouseholdAssignment();
        return;
    }

    if (hhState.role === 'member') {
        const familyId   = document.getElementById('hh-family-select').value;
        const relationId = document.getElementById('hh-relation-select').value;
        if (!familyId || !relationId) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please select a family and relation', showConfirmButton: false, timer: 2000 });
            return;
        }
        hhState.familyId   = familyId;
        hhState.relationId = relationId;
        applyHouseholdAssignment();
        return;
    }
}

function applyHouseholdAssignment() {
    // Write hidden inputs
    document.getElementById('hh_role').value         = hhState.role;
    document.getElementById('hh_household_id').value = hhState.householdId ?? '';
    document.getElementById('hh_family_id').value    = hhState.familyId    ?? '';
    document.getElementById('hh_relation_id').value  = hhState.relationId  ?? '';

    // Update summary card
    const roleLabels = {
        household_head: 'Head of Household',
        family_head:    'Head of Family',
        member:         'Family Member',
    };
    document.getElementById('hh-summary-role').textContent = roleLabels[hhState.role];

    if (hhState.role === 'household_head') {
        document.getElementById('hh-summary-main').textContent = 'New household will be created';
        document.getElementById('hh-summary-sub').textContent  = 'Address will be taken from this citizen\'s address';
    } else if (hhState.role === 'family_head') {
        document.getElementById('hh-summary-main').textContent = hhState.householdAddr ?? '';
        document.getElementById('hh-summary-sub').textContent  = 'Will be added as a new family head in this household';
    } else {
        const famLabel = document.getElementById('hh-family-select').options[document.getElementById('hh-family-select').selectedIndex]?.text ?? '';
        const relLabel = document.getElementById('hh-relation-select').options[document.getElementById('hh-relation-select').selectedIndex]?.text ?? '';
        document.getElementById('hh-summary-main').textContent = hhState.householdAddr ?? '';
        document.getElementById('hh-summary-sub').textContent  = `${famLabel} · ${relLabel}`;
    }

    document.getElementById('hh-summary').classList.remove('hidden');
    document.getElementById('hh-open-btn').innerHTML = '<i class="mgc_edit_line"></i> Change Assignment';
    document.getElementById('hh-open-btn').classList.remove('border-dashed', 'border-success/40');
    document.getElementById('hh-open-btn').classList.add('border-success', 'bg-success/5');

    // Auto-fill address fields from the selected household
    fillAddressFromHousehold(hhState.addressData ?? null);

    closeHouseholdModal();
}

function clearHouseholdAssignment() {
    document.getElementById('hh_role').value         = '';
    document.getElementById('hh_household_id').value = '';
    document.getElementById('hh_family_id').value    = '';
    document.getElementById('hh_relation_id').value  = '';
    document.getElementById('hh-summary').classList.add('hidden');
    document.getElementById('hh-open-btn').innerHTML = '<i class="mgc_add_line"></i> Assign Household';
    document.getElementById('hh-open-btn').classList.add('border-dashed', 'border-success/40');
    document.getElementById('hh-open-btn').classList.remove('border-success', 'bg-success/5');
}

// ── Household search ──────────────────────────────────────────────────────────

function renderHhResults(data, box) {
    if (!data.length) {
        box.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">No households found.</p>';
        return;
    }
    box.innerHTML = '';
    data.forEach(h => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'hh-result-item w-full text-left rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-success hover:bg-green-50 dark:hover:bg-green-950/20 p-3 transition-all flex items-center gap-3';
        btn.innerHTML = `
            <span class="w-9 h-9 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0">
                <i class="mgc_home_3_line"></i>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">${h.full_address}</p>
                <p class="text-xs text-gray-400">${h.formatted_id} · Head: ${h.head_name} · ${h.family_count} famil${h.family_count === 1 ? 'y' : 'ies'}</p>
            </div>
            <i class="mgc_right_line text-gray-300 shrink-0"></i>
        `;
        btn.addEventListener('click', () => onSelectHousehold(h, btn, box));
        box.appendChild(btn);
    });
}

function loadHhPrePicks(box) {
    box.innerHTML = '<p class="text-xs text-gray-400 text-center py-4 animate-pulse">Loading recent households…</p>';
    fetch(HH_SEARCH_URL)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data.length) {
                box.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">No households yet.</p>';
                return;
            }
            // Label header
            const label = document.createElement('p');
            label.className = 'text-xs text-gray-400 font-medium mb-2';
            label.textContent = 'Recent households — type to search';
            box.innerHTML = '';
            box.appendChild(label);
            const wrap = document.createElement('div');
            wrap.className = 'space-y-2';
            box.appendChild(wrap);
            renderHhResults(res.data, wrap);
        });
}

function hhSearch(inputId, resultsId) {
    const input = document.getElementById(inputId);
    const box   = document.getElementById(resultsId);
    let timer;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            loadHhPrePicks(box);
            return;
        }
        box.innerHTML = '<p class="text-xs text-gray-400 text-center py-4 animate-pulse">Searching…</p>';
        timer = setTimeout(() => {
            fetch(`${HH_SEARCH_URL}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    box.innerHTML = '';
                    const wrap = document.createElement('div');
                    wrap.className = 'space-y-2';
                    box.appendChild(wrap);
                    renderHhResults(res.data, wrap);
                });
        }, 350);
    });
}

function onSelectHousehold(h, btn, box) {
    hhState.householdId   = h.id;
    hhState.householdAddr = h.full_address;
    hhState.families      = h.families;
    hhState.addressData   = h; // store full payload for auto-fill on confirm

    // Highlight selected
    box.querySelectorAll('.hh-result-item').forEach(el => {
        el.style.borderColor = '';
        el.style.background  = '';
    });
    btn.style.borderColor = '#10b981';
    btn.style.background  = 'rgba(16,185,129,0.06)';
}

// ── Auto-fill citizen address from household ──────────────────────────────────

function fillAddressFromHousehold(h) {
    // Only fill for family_head and member roles
    if (!h || hhState.role === 'household_head') return;

    const addressSelect = document.getElementById('address-select');
    if (!addressSelect) return;

    // Set zone/area
    if (h.addressId) {
        addressSelect.value = h.addressId;
        // Trigger the existing toggleAddressFields logic
        addressSelect.dispatchEvent(new Event('change'));
    }

    // Wait one tick for the fields to un-hide, then populate values
    setTimeout(() => {
        const isSubd = parseInt(h.is_subd ?? 0);

        if (isSubd === 1) {
            const blkEl   = document.querySelector('[name="blk"]');
            const lotEl   = document.querySelector('[name="lot"]');
            const phaseEl = document.querySelector('[name="phase"]');
            const stEl    = document.querySelector('[name="street"]');
            if (blkEl)   blkEl.value   = h.blk        ?? '';
            if (lotEl)   lotEl.value   = h.lot        ?? '';
            if (phaseEl) {
                // phaseStreet may be "Ph 2 Rizal St" — try to extract just the phase number
                const phaseMatch = (h.phaseStreet ?? '').match(/^(\d+)/);
                phaseEl.value = phaseMatch ? phaseMatch[1] : '';
            }
            if (stEl) {
                // street is the remainder after the phase number
                const stMatch = (h.phaseStreet ?? '').match(/^\d+\s*(.*)/);
                stEl.value = stMatch ? stMatch[1].trim() : (h.phaseStreet ?? '');
            }
        } else if (isSubd === 2) {
            const caEl = document.querySelector('[name="complete_address"]');
            if (caEl) caEl.value = h.completeAdress ?? '';
        } else {
            const noEl = document.querySelector('[name="no"]');
            const stEl = document.querySelector('[name="street"]');
            if (noEl) noEl.value = h.no          ?? '';
            if (stEl) stEl.value = h.phaseStreet ?? '';
        }
    }, 50);
}

// Attach search listeners once DOM ready
document.addEventListener('DOMContentLoaded', () => {
    hhSearch('hh-search-fh', 'hh-search-results-fh');
    hhSearch('hh-search-m',  'hh-search-results-m');

    // Close modal on Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeHouseholdModal();
    });
});
</script>
@endsection
