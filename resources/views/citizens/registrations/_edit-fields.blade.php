{{-- Every field of a pending online registration, editable before approval.
     Values: last submit (old) → saved registration. --}}
@php
    $v   = fn(string $k, $default = null) => old($k, in_array($k, ['fname', 'mname', 'lname', 'suffix']) ? $r->$k : ($k === 'bday' ? $r->bday?->format('Y-m-d') : ($d[$k] ?? $default)));
    $bad = fn(string $k) => $errors->has($k) ? ' border-danger' : '';
    $req = '<span class="text-danger">*</span>';
@endphp

<div class="card">
    <div class="card-header"><h4 class="card-title">Personal</h4></div>
    <div class="grid md:grid-cols-2 gap-4 p-5">
        <div><label class="form-label">First name {!! $req !!}</label><input type="text" name="fname" value="{{ $v('fname') }}" maxlength="250" required class="form-input w-full{{ $bad('fname') }}"></div>
        <div><label class="form-label">Middle name</label><input type="text" name="mname" value="{{ $v('mname') }}" maxlength="250" class="form-input w-full"></div>
        <div><label class="form-label">Last name {!! $req !!}</label><input type="text" name="lname" value="{{ $v('lname') }}" maxlength="250" required class="form-input w-full{{ $bad('lname') }}"></div>
        <div><label class="form-label">Suffix</label><input type="text" name="suffix" value="{{ $v('suffix') }}" maxlength="50" class="form-input w-full"></div>
        <div>
            <label class="form-label">Gender {!! $req !!}</label>
            <select name="gender" required class="form-select w-full{{ $bad('gender') }}">
                <option value="1" @selected((string) $v('gender') === '1')>Male</option>
                <option value="0" @selected((string) $v('gender') === '0')>Female</option>
            </select>
        </div>
        <div>
            <label class="form-label">Civil status {!! $req !!}</label>
            <select name="civil_status" required class="form-select w-full{{ $bad('civil_status') }}">
                @foreach($civilStatuses as $cs)
                <option value="{{ $cs->id }}" @selected((string) $v('civil_status') === (string) $cs->id)>{{ $cs->description }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="form-label">Birthday {!! $req !!}</label><input type="date" name="bday" id="rv-bday" value="{{ $v('bday') }}" max="{{ now()->subDay()->format('Y-m-d') }}" required class="form-input w-full{{ $bad('bday') }}"></div>
        <div><label class="form-label">Birthplace {!! $req !!}</label><input type="text" name="bplace" value="{{ $v('bplace') }}" maxlength="250" required class="form-input w-full{{ $bad('bplace') }}"></div>
        <div><label class="form-label">Citizenship {!! $req !!}</label><input type="text" name="citizenship" value="{{ $v('citizenship', 'Filipino') }}" maxlength="250" required class="form-input w-full{{ $bad('citizenship') }}"></div>
        <div><label class="form-label">Occupation</label><input type="text" name="occupation" value="{{ $v('occupation') }}" maxlength="250" class="form-input w-full"></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Address &amp; residency</h4></div>
    <div class="grid md:grid-cols-3 gap-4 p-5">
        <div class="md:col-span-3">
            <label class="form-label">Zone / Area {!! $req !!}</label>
            <select name="address" id="rv-address" required class="form-select w-full{{ $bad('address') }}">
                @foreach($addresses as $a)
                <option value="{{ $a->id }}" data-subd="{{ $a->is_subd }}" data-rules="{{ $a->rules_required }}" @selected((string) $v('address') === (string) $a->id)>{{ $a->description }}</option>
                @endforeach
            </select>
        </div>
        <div id="rv-f-blk"><label class="form-label">Block No.</label><input type="number" name="blk" value="{{ $v('blk') }}" min="1" class="form-input w-full{{ $bad('blk') }}"></div>
        <div id="rv-f-lot"><label class="form-label">Lot No.</label><input type="number" name="lot" value="{{ $v('lot') }}" min="1" class="form-input w-full{{ $bad('lot') }}"></div>
        <div id="rv-f-phase"><label class="form-label">Phase</label><input type="number" name="phase" value="{{ $v('phase') }}" min="1" class="form-input w-full{{ $bad('phase') }}"></div>
        <div id="rv-f-no"><label class="form-label">House No.</label><input type="text" name="no" value="{{ $v('no') }}" maxlength="50" class="form-input w-full{{ $bad('no') }}"></div>
        <div id="rv-f-street" class="md:col-span-2"><label class="form-label">Street</label><input type="text" name="street" value="{{ $v('street') }}" maxlength="250" class="form-input w-full{{ $bad('street') }}"></div>
        <div id="rv-f-complete_address" class="md:col-span-3"><label class="form-label">Complete address</label><textarea name="complete_address" rows="2" maxlength="500" class="form-input w-full">{{ $v('complete_address') }}</textarea></div>
        <div><label class="form-label">Living here since {!! $req !!}</label><input type="month" name="year_stay" value="{{ $v('year_stay') }}" max="{{ now()->format('Y-m') }}" required class="form-input w-full{{ $bad('year_stay') }}"></div>
        <div>
            <label class="form-label">Home ownership {!! $req !!}</label>
            <select name="owner_status" required class="form-select w-full{{ $bad('owner_status') }}">
                <option value="1" @selected((string) $v('owner_status') === '1')>Owner</option>
                <option value="0" @selected((string) $v('owner_status') === '0')>Renter / Others</option>
            </select>
        </div>
        <div><label class="form-label">House owner's name</label><input type="text" name="house_owner_name" value="{{ $v('house_owner_name') }}" maxlength="150" class="form-input w-full{{ $bad('house_owner_name') }}" placeholder="If renting"></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Contact, voter &amp; groups</h4></div>
    <div class="grid md:grid-cols-2 gap-4 p-5">
        <div><label class="form-label">Mobile {!! $req !!}</label><input type="tel" name="contact" value="{{ $v('contact') }}" maxlength="11" inputmode="numeric" required class="form-input w-full{{ $bad('contact') }}"></div>
        <div><label class="form-label">Email</label><input type="email" name="email" value="{{ $v('email') }}" maxlength="250" class="form-input w-full{{ $bad('email') }}"></div>

        <div class="md:col-span-2 grid sm:grid-cols-2 gap-4 items-end p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="hidden" name="voters" value="0">
                <input type="checkbox" id="rv-voters" name="voters" value="1" class="form-checkbox rounded text-primary" @checked((string) $v('voters') === '1')>
                Registered voter
            </label>
            <div id="rv-precinct">
                <label class="form-label">Precinct No. {!! $req !!} <span class="text-xs font-normal text-gray-400">— correct it if the resident typed it wrong</span></label>
                <input type="text" name="pricinct_no" value="{{ $v('pricinct_no') }}" minlength="5" maxlength="6" placeholder="e.g. 0055F" class="form-input w-full uppercase{{ $bad('pricinct_no') }}">
            </div>
        </div>

        <div class="md:col-span-2 flex flex-wrap gap-x-6 gap-y-2">
            @foreach(['is_senior' => ['senior', 'Senior citizen (60+)'], 'is_pwd' => ['pwd', 'PWD'], 'is_soloparents' => ['solo', 'Solo parent']] as $flag => [$key, $label])
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="hidden" name="{{ $flag }}" value="0">
                <input type="checkbox" name="{{ $flag }}" value="1" class="form-checkbox rounded text-primary rv-group" data-key="{{ $key }}" @checked((string) $v($flag) === '1')>
                {{ $label }}
            </label>
            @endforeach
        </div>
        @if($errors->has('is_senior'))<p class="md:col-span-2 text-xs text-danger">{{ $errors->first('is_senior') }}</p>@endif
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title">Emergency contact</h4></div>
    <div class="grid md:grid-cols-2 gap-4 p-5">
        <div><label class="form-label">Name</label><input type="text" name="ic_fullname" value="{{ $v('ic_fullname') }}" maxlength="250" class="form-input w-full"></div>
        <div><label class="form-label">Relationship</label><input type="text" name="ic_relationship" value="{{ $v('ic_relationship') }}" maxlength="250" class="form-input w-full"></div>
        <div><label class="form-label">Mobile</label><input type="tel" name="ic_contact" value="{{ $v('ic_contact') }}" maxlength="11" inputmode="numeric" class="form-input w-full{{ $bad('ic_contact') }}"></div>
        <div><label class="form-label">Email</label><input type="email" name="ic_email" value="{{ $v('ic_email') }}" maxlength="250" class="form-input w-full{{ $bad('ic_email') }}"></div>
        <div class="md:col-span-2"><label class="form-label">Address</label><input type="text" name="ic_address" value="{{ $v('ic_address') }}" maxlength="500" class="form-input w-full"></div>
    </div>
</div>
