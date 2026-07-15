@extends('layouts.vertical', ['title' => 'Edit Citizen', 'sub_title' => 'Citizen Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

@php
    $idFormat    = \App\Models\Setting::instance()->formatCitizenId($citizen->id);
    $addressMap  = $addresses->keyBy('id')->map(fn($a) => ['is_subd' => $a->is_subd, 'rules' => $a->rules_required]);
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

<form action="{{ route('citizens.update', $citizen->id) }}" method="POST" enctype="multipart/form-data" id="citizen-edit-form">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-12 gap-6">

        {{-- ── Left Column ── --}}
        <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">

            {{-- Household Assignment --}}
            @php
                $hhIsAssigned = $citizen->familyId !== null;
                $hhRoleLabel  = match((int)$citizen->isHead) {
                    2 => 'Head of Household',
                    1 => 'Head of Family',
                    default => 'Family Member',
                };
                $hhRoleColor  = match((int)$citizen->isHead) {
                    2 => 'success',
                    1 => 'primary',
                    default => 'warning',
                };
                $hhRoleIcon   = match((int)$citizen->isHead) {
                    2 => 'mgc_home_3_fill',
                    1 => 'mgc_group_2_fill',
                    default => 'mgc_user_3_fill',
                };
            @endphp
            <div class="card border-2 {{ $hhIsAssigned ? 'border-'.$hhRoleColor.'/30' : 'border-dashed border-gray-200 dark:border-gray-700' }}">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-{{ $hhIsAssigned ? $hhRoleColor : 'success' }}/10 text-{{ $hhIsAssigned ? $hhRoleColor : 'success' }} flex items-center justify-center shrink-0">
                            <i class="{{ $hhIsAssigned ? $hhRoleIcon : 'mgc_home_3_line' }} text-lg"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">Household Assignment</p>
                            <p class="text-xs text-gray-400">
                                @if($hhIsAssigned)
                                    Currently assigned · changes take effect on save
                                @else
                                    Optional — can be set later from the Household module
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="badge bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-semibold px-2 py-1 rounded-full">Optional</span>
                </div>

                {{-- Current assignment (shown if already assigned and no pending change) --}}
                @if($hhIsAssigned)
                <div id="hh-current" class="mx-6 mb-4 rounded-xl border border-{{ $hhRoleColor }}/30 bg-{{ $hhRoleColor }}/5 p-4">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 rounded-lg bg-{{ $hhRoleColor }}/15 text-{{ $hhRoleColor }} flex items-center justify-center shrink-0 mt-0.5">
                            <i class="{{ $hhRoleIcon }}"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-{{ $hhRoleColor }} uppercase tracking-wide mb-1">{{ $hhRoleLabel }}</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $currentHousehold?->full_address ?? '—' }}
                            </p>
                            @if($currentHousehold)
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $currentHousehold->formatted_id }}
                                    @if($citizen->isHead == 0 && $citizen->relation)
                                        · {{ $citizen->relation->description }}
                                    @endif
                                    @if($currentFamily)
                                        · {{ $currentFamily->formatted_id }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Pending change summary (shown after user picks something in modal) --}}
                <div id="hh-summary" class="hidden mx-6 mb-4 rounded-xl border border-success/30 bg-success/5 p-4">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 rounded-lg bg-success/15 text-success flex items-center justify-center shrink-0 mt-0.5">
                            <i class="mgc_refresh_1_line"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-success uppercase tracking-wide mb-1">Will be changed on save</p>
                            <p class="text-xs font-bold uppercase tracking-wide mb-0.5" id="hh-summary-role" style="color:inherit"></p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="hh-summary-main"></p>
                            <p class="text-xs text-gray-500 mt-0.5" id="hh-summary-sub"></p>
                        </div>
                        <button type="button" onclick="clearHouseholdAssignment()"
                            class="text-gray-400 hover:text-danger transition-colors shrink-0 mt-0.5" title="Undo change">
                            <i class="mgc_close_line text-base"></i>
                        </button>
                    </div>
                </div>

                {{-- Remove strip (shown when user clicks Remove) --}}
                <div id="hh-remove-summary" class="hidden mx-6 mb-4 rounded-xl border border-danger/30 bg-danger/5 p-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-danger/15 text-danger flex items-center justify-center shrink-0">
                            <i class="mgc_home_delete_line"></i>
                        </span>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-danger uppercase tracking-wide">Will be removed on save</p>
                            <p class="text-xs text-gray-500 mt-0.5">Household assignment will be cleared when you save.</p>
                        </div>
                        <button type="button" onclick="undoRemoveHousehold()"
                            class="text-gray-400 hover:text-success transition-colors shrink-0" title="Undo">
                            <i class="mgc_close_line text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="px-6 pb-5 flex gap-2">
                    <button type="button" id="hh-open-btn" onclick="openHouseholdModal()"
                        class="btn border-2 border-dashed border-success/40 text-success hover:bg-success hover:text-white hover:border-success flex-1 gap-2 justify-center transition-colors">
                        <i class="mgc_{{ $hhIsAssigned ? 'edit' : 'add' }}_line"></i>
                        {{ $hhIsAssigned ? 'Change Assignment' : 'Assign Household' }}
                    </button>
                    @if($hhIsAssigned)
                    <button type="button" id="hh-remove-btn" onclick="removeHouseholdAssignment()"
                        class="btn border-2 border-danger/30 text-danger hover:bg-danger hover:text-white gap-1">
                        <i class="mgc_delete_line"></i> Remove
                    </button>
                    @endif
                </div>

                {{-- Hidden fields submitted with form --}}
                <input type="hidden" name="hh_action"       id="hh_action">
                <input type="hidden" name="hh_role"         id="hh_role">
                <input type="hidden" name="hh_household_id" id="hh_household_id">
                <input type="hidden" name="hh_family_id"    id="hh_family_id">
                <input type="hidden" name="hh_relation_id"  id="hh_relation_id">
            </div>

            {{-- Personal Information --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_user_3_line me-2 text-primary"></i>Personal Information</h5>
                </div>
                <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="fname" value="{{ old('fname', $citizen->fname) }}"
                            class="form-input @error('fname') border-danger @enderror" required>
                        @error('fname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="mname" value="{{ old('mname', $citizen->mname) }}"
                            class="form-input @error('mname') border-danger @enderror">
                        @error('mname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lname" value="{{ old('lname', $citizen->lname) }}"
                            class="form-input @error('lname') border-danger @enderror" required>
                        @error('lname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" value="{{ old('suffix', $citizen->suffix) }}"
                            class="form-input" placeholder="Jr., Sr., III">
                    </div>

                    <div>
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') border-danger @enderror" required>
                            <option value="1" {{ old('gender', $citizen->gender) == 1 ? 'selected' : '' }}>Male</option>
                            <option value="0" {{ old('gender', $citizen->gender) == 0 ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                        <select name="civil_status" class="form-select @error('civil_status') border-danger @enderror" required>
                            <option value="">— Select —</option>
                            @foreach($civilStatuses as $cs)
                                <option value="{{ $cs->id }}" {{ old('civil_status', $citizen->civil_status) == $cs->id ? 'selected' : '' }}>
                                    {{ $cs->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('civil_status') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Birthday <span class="text-danger">*</span></label>
                        <input type="date" name="bday"
                            value="{{ old('bday', $citizen->bday ? $citizen->bday->format('Y-m-d') : '') }}"
                            class="form-input @error('bday') border-danger @enderror" required>
                        @error('bday') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Place of Birth <span class="text-danger">*</span></label>
                        <input type="text" name="bplace" value="{{ old('bplace', $citizen->bplace) }}"
                            class="form-input @error('bplace') border-danger @enderror" required>
                        @error('bplace') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Citizenship <span class="text-danger">*</span></label>
                        <input type="text" name="citizenship" value="{{ old('citizenship', $citizen->citizenship) }}"
                            class="form-input @error('citizenship') border-danger @enderror" placeholder="e.g. Filipino">
                        @error('citizenship') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation', $citizen->occupation) }}"
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
                                    {{ old('address', $citizen->address) == $addr->id ? 'selected' : '' }}>
                                    {{ $addr->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('address') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Subdivision fields (blk, lot, phase) --}}
                    <div id="field-blk" class="hidden">
                        <label class="form-label">Block No.</label>
                        <input type="number" name="blk" value="{{ old('blk', $citizen->blk) }}"
                            class="form-input" min="1">
                        @error('blk') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-lot" class="hidden">
                        <label class="form-label">Lot No.</label>
                        <input type="number" name="lot" value="{{ old('lot', $citizen->lot) }}"
                            class="form-input" min="1">
                        @error('lot') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-phase" class="hidden">
                        <label class="form-label">Phase</label>
                        <input type="number" name="phase" value="{{ old('phase', $citizen->phase) }}"
                            class="form-input" min="1">
                        @error('phase') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Non-subd fields (no, street) --}}
                    <div id="field-no" class="hidden">
                        <label class="form-label">House No.</label>
                        <input type="text" name="no" value="{{ old('no', $citizen->no) }}"
                            class="form-input">
                        @error('no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-street" class="hidden">
                        <label class="form-label">Street</label>
                        <input type="text" name="street" value="{{ old('street', $citizen->street) }}"
                            class="form-input">
                        @error('street') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Complete address (for is_subd == 2) --}}
                    <div id="field-complete_address" class="lg:col-span-3 hidden">
                        <label class="form-label">Complete Address</label>
                        <textarea name="complete_address" rows="2"
                            class="form-input w-full">{{ old('complete_address', $citizen->complete_address) }}</textarea>
                        @error('complete_address') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Month / Year of Stay <span class="text-danger">*</span></label>
                        <input type="month" name="year_stay"
                            value="{{ old('year_stay', $citizen->year_stay ? $citizen->year_stay->format('Y-m') : '') }}"
                            class="form-input @error('year_stay') border-danger @enderror" required>
                        @error('year_stay') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Owner Status <span class="text-danger">*</span></label>
                        <select name="owner_status" class="form-select @error('owner_status') border-danger @enderror" required>
                            <option value="">— Select —</option>
                            <option value="1" {{ old('owner_status', $citizen->owner_status) == 1 ? 'selected' : '' }}>Owner</option>
                            <option value="0" {{ old('owner_status', $citizen->owner_status) == 0 ? 'selected' : '' }}>Renter / Others</option>
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
                        <input type="text" name="contact" value="{{ old('contact', $citizen->contact) }}"
                            class="form-input @error('contact') border-danger @enderror"
                            placeholder="09XXXXXXXXX" maxlength="11">
                        @error('contact') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $citizen->email) }}"
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
                <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">Approval Status</label>
                        <select name="approval_status" class="form-select">
                            @foreach($approvalStatuses as $as)
                                <option value="{{ $as->id }}" {{ old('approval_status', $citizen->approval_status) == $as->id ? 'selected' : '' }}>
                                    {{ $as->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Active Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $citizen->is_active) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $citizen->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">PWD</label>
                        <select name="is_pwd" class="form-select">
                            <option value="0" {{ old('is_pwd', $citizen->is_pwd) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('is_pwd', $citizen->is_pwd) == 1 ? 'selected' : '' }}>Yes — PWD</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Solo Parent</label>
                        <select name="is_soloparents" class="form-select">
                            <option value="0" {{ old('is_soloparents', $citizen->is_soloparents) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('is_soloparents', $citizen->is_soloparents) == 1 ? 'selected' : '' }}>Yes — Solo Parent</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Voter</label>
                        <select name="voters" id="voters-select" class="form-select">
                            <option value="0" {{ old('voters', $citizen->voters) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('voters', $citizen->voters) == 1 ? 'selected' : '' }}>Yes — Registered Voter</option>
                        </select>
                    </div>

                    <div id="field-precinct" class="{{ old('voters', $citizen->voters) == 1 ? '' : 'hidden' }}">
                        <label class="form-label">Precinct No. <span class="text-danger">*</span></label>
                        <input type="text" name="pricinct_no" value="{{ old('pricinct_no', $citizen->pricinct_no) }}"
                            class="form-input @error('pricinct_no') border-danger @enderror"
                            placeholder="0055F" maxlength="6">
                        @error('pricinct_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">ID Released</label>
                        <select name="is_id_release" class="form-select">
                            <option value="0" {{ old('is_id_release', $citizen->is_id_release) == 0 ? 'selected' : '' }}>Not Yet</option>
                            <option value="1" {{ old('is_id_release', $citizen->is_id_release) == 1 ? 'selected' : '' }}>Released</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Verified</label>
                        <select name="is_verify" class="form-select">
                            <option value="0" {{ old('is_verify', $citizen->is_verify) == 0 ? 'selected' : '' }}>Unverified</option>
                            <option value="1" {{ old('is_verify', $citizen->is_verify) == 1 ? 'selected' : '' }}>Verified</option>
                        </select>
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
                        <input type="text" name="ic_fullname" value="{{ old('ic_fullname', $citizen->ic_fullname) }}"
                            class="form-input @error('ic_fullname') border-danger @enderror">
                        @error('ic_fullname') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Relationship</label>
                        <input type="text" name="ic_relationship" value="{{ old('ic_relationship', $citizen->ic_relationship) }}"
                            class="form-input @error('ic_relationship') border-danger @enderror"
                            placeholder="e.g. Spouse, Parent, Sibling">
                        @error('ic_relationship') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Contact No.</label>
                        <input type="text" name="ic_contact" value="{{ old('ic_contact', $citizen->ic_contact) }}"
                            class="form-input @error('ic_contact') border-danger @enderror"
                            placeholder="09XXXXXXXXX" maxlength="11">
                        @error('ic_contact') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="ic_email" value="{{ old('ic_email', $citizen->ic_email) }}"
                            class="form-input @error('ic_email') border-danger @enderror">
                        @error('ic_email') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="lg:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="ic_address" rows="2"
                            class="form-input w-full @error('ic_address') border-danger @enderror">{{ old('ic_address', $citizen->ic_address) }}</textarea>
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
                        class="form-input w-full">{{ old('note', $citizen->note) }}</textarea>
                </div>
            </div>

        </div>

        {{-- ── Right Column ── --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

            {{-- Profile Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_pic_line me-2 text-primary"></i>Profile Photo</h5>
                </div>
                <div class="p-6 flex flex-col items-center gap-4">

                    @if($citizen->profile)
                        <img id="profile-preview"
                            src="{{ asset('storage/' . $citizen->profile) }}"
                            class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow"
                            alt="Profile">
                    @else
                        <div id="profile-placeholder"
                            class="w-28 h-28 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-3xl shadow">
                            {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                        </div>
                        <img id="profile-preview" src="" class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow hidden" alt="Profile">
                    @endif

                    <label for="profile-input" class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer w-full text-center">
                        <i class="mgc_upload_2_line me-1"></i> Upload Photo
                    </label>
                    <input type="file" id="profile-input" name="profile"
                        class="hidden" accept="image/jpeg,image/png,image/jpg">
                    <p class="text-xs text-gray-400 text-center">JPG or PNG. Max 2MB. Will be resized to 512×512.</p>

                </div>
            </div>

            {{-- Citizen ID Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_id_card_line me-2 text-primary"></i>Record</h5>
                </div>
                <div class="p-6 flex flex-col gap-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Citizen ID</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $idFormat }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">QR Code</span>
                        <span class="font-mono text-xs text-gray-500 truncate max-w-[140px]" title="{{ $citizen->qrcode }}">{{ $citizen->qrcode }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Registered</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $citizen->date_created ? $citizen->date_created->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Last Updated</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $citizen->date_last_updated ? $citizen->date_last_updated->format('M d, Y') : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Tags --}}
            @include('citizens._tags_card', ['selectedTagIds' => old('tags', $citizenTagIds)])

            {{-- Actions --}}
            <div class="card">
                <div class="p-6 flex flex-col gap-3">
                    <button type="submit" class="btn bg-primary text-white w-full">
                        <i class="mgc_save_line me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('citizens.show', $citizen->id) }}"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 w-full text-center">
                        <i class="mgc_eye_line me-1"></i> View Profile
                    </a>
                    <a href="{{ route('citizens.index') }}"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 w-full text-center">
                        <i class="mgc_arrow_left_line me-1"></i> Back to List
                    </a>
                </div>
            </div>

        </div>

    </div>

</form>

{{-- ══════════════════════════════════════════════════════
     HOUSEHOLD ASSIGNMENT MODAL
══════════════════════════════════════════════════════ --}}
<div id="hh-modal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeHouseholdModal()"></div>
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

        {{-- Progress bar --}}
        <div class="h-1 bg-gray-100 dark:bg-gray-700 shrink-0">
            <div id="hh-progress" class="h-full bg-success transition-all duration-300" style="width:50%"></div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Step 1: Role --}}
            <div id="hh-step-1" class="p-6">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">What is this person's role?</p>
                <p class="text-xs text-gray-400 mb-5">Select the role that best describes how this citizen relates to a household.</p>
                <div class="space-y-3" id="hh-role-cards">
                    <button type="button" class="hh-role-card w-full text-left" data-role="household_head" data-color="success" onclick="selectHhRole(this)">
                        <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-success/50 transition-all">
                            <span class="hh-role-icon w-11 h-11 rounded-xl bg-success/10 text-success flex items-center justify-center shrink-0 text-xl transition-all"><i class="mgc_home_3_fill"></i></span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm">Head of Household</p>
                                <p class="text-xs text-gray-400 mt-0.5">Owns or manages the physical address. A new household will be created.</p>
                            </div>
                            <span class="hh-role-check w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 transition-all">
                                <i class="mgc_check_line text-white text-xs hidden"></i>
                            </span>
                        </div>
                    </button>
                    <button type="button" class="hh-role-card w-full text-left" data-role="family_head" data-color="primary" onclick="selectHhRole(this)">
                        <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary/50 transition-all">
                            <span class="hh-role-icon w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-xl transition-all"><i class="mgc_group_2_fill"></i></span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm">Head of Family</p>
                                <p class="text-xs text-gray-400 mt-0.5">Leads a family unit within an existing household.</p>
                            </div>
                            <span class="hh-role-check w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 transition-all">
                                <i class="mgc_check_line text-white text-xs hidden"></i>
                            </span>
                        </div>
                    </button>
                    <button type="button" class="hh-role-card w-full text-left" data-role="member" data-color="warning" onclick="selectHhRole(this)">
                        <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-warning/50 transition-all">
                            <span class="hh-role-icon w-11 h-11 rounded-xl bg-warning/10 text-warning flex items-center justify-center shrink-0 text-xl transition-all"><i class="mgc_user_3_fill"></i></span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-gray-100 text-sm">Family Member</p>
                                <p class="text-xs text-gray-400 mt-0.5">Belongs to an existing family. Select household → family → relation.</p>
                            </div>
                            <span class="hh-role-check w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 transition-all">
                                <i class="mgc_check_line text-white text-xs hidden"></i>
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Step 2a: Household Head --}}
            <div id="hh-step-2-household-head" class="p-6 hidden">
                <div class="rounded-xl bg-success/5 border border-success/20 p-5 text-center">
                    <span class="text-4xl block mb-3">🏠</span>
                    <p class="font-bold text-gray-800 dark:text-gray-100 mb-1">New Household will be created</p>
                    <p class="text-sm text-gray-500">A household record will be automatically created using this citizen's saved address. This citizen will be set as the Household Head.</p>
                </div>
            </div>

            {{-- Step 2b: Family Head — search household --}}
            <div id="hh-step-2-family-head" class="p-6 hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Search Household</p>
                <p class="text-xs text-gray-400 mb-4">Type an address, zone, or HH number to find the household.</p>
                <div class="relative mb-4">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="hh-search-fh" placeholder="e.g. Zone 1, Blk 3, HH-00012…" class="form-input pl-9 w-full" autocomplete="off">
                </div>
                <div id="hh-search-results-fh" class="space-y-2 max-h-72 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-6">Start typing to search households…</p>
                </div>
            </div>

            {{-- Step 2c: Member — search household --}}
            <div id="hh-step-2-member" class="p-6 hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Search Household</p>
                <p class="text-xs text-gray-400 mb-4">Find the household this citizen belongs to.</p>
                <div class="relative mb-4">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="hh-search-m" placeholder="e.g. Zone 1, Blk 3, HH-00012…" class="form-input pl-9 w-full" autocomplete="off">
                </div>
                <div id="hh-search-results-m" class="space-y-2 max-h-60 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-6">Start typing to search households…</p>
                </div>
            </div>

            {{-- Step 3: Member — family + relation --}}
            <div id="hh-step-3-member" class="p-6 hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Select Family & Relation</p>
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
            <button type="button" id="hh-back-btn" onclick="hhBack()" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 gap-1 hidden">
                <i class="mgc_left_line"></i> Back
            </button>
            <div class="flex-1"></div>
            <button type="button" onclick="closeHouseholdModal()" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">Cancel</button>
            <button type="button" id="hh-next-btn" onclick="hhNext()" class="btn bg-success text-white gap-1">Next <i class="mgc_right_line"></i></button>
            <button type="button" id="hh-confirm-btn" onclick="hhConfirm()" class="btn bg-success text-white gap-1 hidden"><i class="mgc_check_line"></i> Confirm</button>
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
            // Reset labels
            ['blk','lot','phase','no','street'].forEach(f => {
                const lbl = document.querySelector(`#field-${f} label`);
                if (lbl) lbl.innerHTML = lbl.textContent.trim();
            });

            if (isSubd === 1) {
                // Show all three subdivision fields; mark required ones
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

        // Init on page load
        toggleAddressFields('#address-select');

        // On change
        $('#address-select').on('change', function () {
            toggleAddressFields(this);
        });

        // Voter → show/hide precinct
        $('#voters-select').on('change', function () {
            if ($(this).val() === '1') {
                $('#field-precinct').removeClass('hidden');
            } else {
                $('#field-precinct').addClass('hidden');
            }
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
const HH_SEARCH_URL  = '{{ route('households.search') }}';
const HH_IS_ASSIGNED = {{ $hhIsAssigned ? 'true' : 'false' }};

let hhState = {
    role: null, householdId: null, householdAddr: null,
    addressData: null, families: [], familyId: null, relationId: null, step: 1,
};

const colorMap = {
    success: { border: '#10b981', bg: 'rgba(16,185,129,0.06)', iconBg: '#10b981', checkBg: '#10b981' },
    primary: { border: '#4361ee', bg: 'rgba(67,97,238,0.06)',  iconBg: '#4361ee', checkBg: '#4361ee' },
    warning: { border: '#e2a907', bg: 'rgba(226,169,7,0.06)',  iconBg: '#e2a907', checkBg: '#e2a907' },
};

// ── Modal open / close ────────────────────────────────────────────────────────

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

// ── Step navigation ───────────────────────────────────────────────────────────

function showHhStep(step) {
    hhState.step = step;
    const role   = hhState.role;
    ['hh-step-1','hh-step-2-household-head','hh-step-2-family-head','hh-step-2-member','hh-step-3-member']
        .forEach(id => document.getElementById(id).classList.add('hidden'));

    const totals = { household_head: 2, family_head: 2, member: 3 };
    const total  = totals[role] ?? 2;
    document.getElementById('hh-step-total').textContent = step === 1 ? '?' : total;
    document.getElementById('hh-step-label').textContent = step;
    document.getElementById('hh-progress').style.width   = (step / (step === 1 ? 2 : total) * 100) + '%';
    document.getElementById('hh-back-btn').classList.toggle('hidden', step === 1);

    const isLast = step > 1 && step === total;
    document.getElementById('hh-next-btn').classList.toggle('hidden', isLast);
    document.getElementById('hh-confirm-btn').classList.toggle('hidden', !isLast);

    if (step === 1) {
        document.getElementById('hh-step-1').classList.remove('hidden');
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

function hhNext() {
    if (hhState.step === 1) {
        if (!hhState.role) {
            Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Please select a role', showConfirmButton:false, timer:2000 });
            return;
        }
        showHhStep(2); return;
    }
    if (hhState.step === 2 && hhState.role === 'member') {
        if (!hhState.householdId) {
            Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Please select a household', showConfirmButton:false, timer:2000 });
            return;
        }
        showHhStep(3); return;
    }
}

function hhBack() { showHhStep(hhState.step - 1); }

function hhConfirm() {
    if (hhState.role === 'household_head') { applyHouseholdAssignment(); return; }
    if (hhState.role === 'family_head') {
        if (!hhState.householdId) {
            Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Please select a household', showConfirmButton:false, timer:2000 });
            return;
        }
        applyHouseholdAssignment(); return;
    }
    if (hhState.role === 'member') {
        hhState.familyId   = document.getElementById('hh-family-select').value;
        hhState.relationId = document.getElementById('hh-relation-select').value;
        if (!hhState.familyId || !hhState.relationId) {
            Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Please select a family and relation', showConfirmButton:false, timer:2000 });
            return;
        }
        applyHouseholdAssignment(); return;
    }
}

// ── Apply / clear / remove ────────────────────────────────────────────────────

function applyHouseholdAssignment() {
    document.getElementById('hh_action').value       = 'assign';
    document.getElementById('hh_role').value         = hhState.role;
    document.getElementById('hh_household_id').value = hhState.householdId ?? '';
    document.getElementById('hh_family_id').value    = hhState.familyId    ?? '';
    document.getElementById('hh_relation_id').value  = hhState.relationId  ?? '';

    const roleLabels = { household_head:'Head of Household', family_head:'Head of Family', member:'Family Member' };
    document.getElementById('hh-summary-role').textContent = roleLabels[hhState.role];

    if (hhState.role === 'household_head') {
        document.getElementById('hh-summary-main').textContent = 'New household will be created';
        document.getElementById('hh-summary-sub').textContent  = "Address taken from this citizen's saved address";
    } else if (hhState.role === 'family_head') {
        document.getElementById('hh-summary-main').textContent = hhState.householdAddr ?? '';
        document.getElementById('hh-summary-sub').textContent  = 'Will be added as a new family head in this household';
    } else {
        const famText = document.getElementById('hh-family-select').options[document.getElementById('hh-family-select').selectedIndex]?.text ?? '';
        const relText = document.getElementById('hh-relation-select').options[document.getElementById('hh-relation-select').selectedIndex]?.text ?? '';
        document.getElementById('hh-summary-main').textContent = hhState.householdAddr ?? '';
        document.getElementById('hh-summary-sub').textContent  = `${famText} · ${relText}`;
    }

    // Show pending-change strip, hide remove strip and current strip
    document.getElementById('hh-summary').classList.remove('hidden');
    document.getElementById('hh-remove-summary').classList.add('hidden');
    const cur = document.getElementById('hh-current');
    if (cur) cur.classList.add('hidden');

    document.getElementById('hh-open-btn').innerHTML = '<i class="mgc_edit_line"></i> Change Assignment';

    // Auto-fill address fields for family_head / member
    fillAddressFromHousehold(hhState.addressData);

    closeHouseholdModal();
}

function clearHouseholdAssignment() {
    document.getElementById('hh_action').value       = '';
    document.getElementById('hh_role').value         = '';
    document.getElementById('hh_household_id').value = '';
    document.getElementById('hh_family_id').value    = '';
    document.getElementById('hh_relation_id').value  = '';
    document.getElementById('hh-summary').classList.add('hidden');
    // Restore current strip if originally assigned
    const cur = document.getElementById('hh-current');
    if (cur) cur.classList.remove('hidden');
    document.getElementById('hh-open-btn').innerHTML = HH_IS_ASSIGNED
        ? '<i class="mgc_edit_line"></i> Change Assignment'
        : '<i class="mgc_add_line"></i> Assign Household';
}

function removeHouseholdAssignment() {
    document.getElementById('hh_action').value = 'remove';
    document.getElementById('hh_role').value   = '';
    document.getElementById('hh-remove-summary').classList.remove('hidden');
    document.getElementById('hh-summary').classList.add('hidden');
    const cur = document.getElementById('hh-current');
    if (cur) cur.classList.add('hidden');
}

function undoRemoveHousehold() {
    document.getElementById('hh_action').value = '';
    document.getElementById('hh-remove-summary').classList.add('hidden');
    const cur = document.getElementById('hh-current');
    if (cur) cur.classList.remove('hidden');
}

// ── Role card selection ───────────────────────────────────────────────────────

function selectHhRole(btn) {
    document.querySelectorAll('.hh-role-card').forEach(card => {
        const inner = card.querySelector('div');
        inner.style.borderColor = ''; inner.style.background = '';
        card.querySelector('.hh-role-icon').style.background = '';
        card.querySelector('.hh-role-icon').style.color      = '';
        card.querySelector('.hh-role-check').style.borderColor = '';
        card.querySelector('.hh-role-check').style.background  = '';
        card.querySelector('.hh-role-check i').classList.add('hidden');
    });
    const colors = colorMap[btn.dataset.color];
    const inner  = btn.querySelector('div');
    inner.style.borderColor = colors.border; inner.style.background = colors.bg;
    btn.querySelector('.hh-role-icon').style.background = colors.iconBg;
    btn.querySelector('.hh-role-icon').style.color      = '#fff';
    btn.querySelector('.hh-role-check').style.borderColor = colors.checkBg;
    btn.querySelector('.hh-role-check').style.background  = colors.checkBg;
    btn.querySelector('.hh-role-check i').classList.remove('hidden');
    hhState.role = btn.dataset.role;
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
            <span class="w-9 h-9 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0"><i class="mgc_home_3_line"></i></span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">${h.full_address}</p>
                <p class="text-xs text-gray-400">${h.formatted_id} · Head: ${h.head_name} · ${h.family_count} famil${h.family_count===1?'y':'ies'}</p>
            </div>
            <i class="mgc_right_line text-gray-300 shrink-0"></i>`;
        btn.addEventListener('click', () => {
            hhState.householdId   = h.id;
            hhState.householdAddr = h.full_address;
            hhState.families      = h.families;
            hhState.addressData   = h;
            const parent = btn.closest('.space-y-2') ?? box;
            parent.querySelectorAll('.hh-result-item').forEach(el => { el.style.borderColor=''; el.style.background=''; });
            btn.style.borderColor = '#10b981';
            btn.style.background  = 'rgba(16,185,129,0.06)';
        });
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

// ── Address auto-fill ─────────────────────────────────────────────────────────

function fillAddressFromHousehold(h) {
    if (!h || hhState.role === 'household_head') return;
    const addressSelect = document.getElementById('address-select');
    if (!addressSelect) return;
    if (h.addressId) {
        addressSelect.value = h.addressId;
        addressSelect.dispatchEvent(new Event('change'));
    }
    setTimeout(() => {
        const isSubd = parseInt(h.is_subd ?? 0);
        if (isSubd === 1) {
            const blkEl = document.querySelector('[name="blk"]');
            const lotEl = document.querySelector('[name="lot"]');
            const phEl  = document.querySelector('[name="phase"]');
            const stEl  = document.querySelector('[name="street"]');
            if (blkEl) blkEl.value = h.blk ?? '';
            if (lotEl) lotEl.value = h.lot ?? '';
            if (phEl)  { const m = (h.phaseStreet??'').match(/^(\d+)/); phEl.value = m ? m[1] : ''; }
            if (stEl)  { const m = (h.phaseStreet??'').match(/^\d+\s*(.*)/); stEl.value = m ? m[1].trim() : (h.phaseStreet??''); }
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

// ── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    hhSearch('hh-search-fh', 'hh-search-results-fh');
    hhSearch('hh-search-m',  'hh-search-results-m');
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeHouseholdModal(); });
});
</script>
@endsection
