@extends('layouts.vertical', [
    'title'         => 'Barangay Settings',
    'sub_title'     => 'System',
    'tagline'       => 'General barangay information, officials, and zones.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@push('skeleton')
<div class="grid grid-cols-12 gap-6">
    @for($i = 0; $i < 3; $i++)
    <div class="col-span-12 lg:col-span-4">
        <div class="card p-5 space-y-4">
            <div class="h-4 bg-gray-200 rounded dark:bg-gray-700 w-36"></div>
            @for($f = 0; $f < 4; $f++)
            <div class="space-y-1">
                <div class="h-3 bg-gray-200 rounded dark:bg-gray-700 w-24"></div>
                <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-full"></div>
            </div>
            @endfor
            <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-full mt-2"></div>
        </div>
    </div>
    @endfor
</div>
@endpush

@section('content')

<div class="grid grid-cols-12 gap-6">

    {{-- ── Settings Sub-nav ── --}}
    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    {{-- ── Settings Content ── --}}
    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

        {{-- Success flash --}}
        @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
            <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-success font-medium">{{ session('success') }}</p>
        </div>
        @endif

        {{-- Validation errors --}}
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

        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-12 gap-6">

                {{-- ── Left Column ── --}}
                <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">

            {{-- Barangay Identity --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_building_2_line me-2 text-primary"></i>Barangay Identity</h5>
                </div>
                <div class="grid md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div class="md:col-span-2">
                        <label class="form-label">Barangay Name <span class="text-danger">*</span></label>
                        <input type="text" name="barangay_name"
                            value="{{ old('barangay_name', $setting->barangay_name) }}"
                            class="form-input @error('barangay_name') border-danger @enderror"
                            placeholder="e.g. Barangay San Isidro">
                        @error('barangay_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Street / Purok / Sitio Address</label>
                        <input type="text" name="address"
                            value="{{ old('address', $setting->address) }}"
                            class="form-input @error('address') border-danger @enderror"
                            placeholder="e.g. 123 Rizal Street">
                        @error('address') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Municipality / City</label>
                        <input type="text" name="municipality"
                            value="{{ old('municipality', $setting->municipality) }}"
                            class="form-input @error('municipality') border-danger @enderror"
                            placeholder="e.g. Marikina City">
                        @error('municipality') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Province</label>
                        <input type="text" name="province"
                            value="{{ old('province', $setting->province) }}"
                            class="form-input @error('province') border-danger @enderror"
                            placeholder="e.g. Metro Manila">
                        @error('province') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Contact Information --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_phone_line me-2 text-primary"></i>Contact Information</h5>
                </div>
                <div class="grid md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact"
                            value="{{ old('contact', $setting->contact) }}"
                            class="form-input @error('contact') border-danger @enderror"
                            placeholder="e.g. 09XXXXXXXXX">
                        @error('contact') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email"
                            value="{{ old('email', $setting->email) }}"
                            class="form-input @error('email') border-danger @enderror"
                            placeholder="e.g. brgy.sanisidro@gmail.com">
                        @error('email') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Officials --}}
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <h5 class="card-title"><i class="mgc_user_star_line me-2 text-primary"></i>Barangay Captain</h5>
                        <a href="{{ route('officials.index') }}"
                            class="text-xs text-primary hover:underline flex items-center gap-1">
                            <i class="mgc_group_line"></i> Manage All Officials
                        </a>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">Barangay Captain Name</label>
                        <input type="text" name="captain_name"
                            value="{{ old('captain_name', $setting->captain_name) }}"
                            class="form-input @error('captain_name') border-danger @enderror"
                            placeholder="e.g. Juan dela Cruz">
                        @error('captain_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Position / Title</label>
                        <input type="text" name="captain_position"
                            value="{{ old('captain_position', $setting->captain_position ?? 'Barangay Captain') }}"
                            class="form-input @error('captain_position') border-danger @enderror"
                            placeholder="Barangay Captain">
                        @error('captain_position') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Signature image (full width) --}}
                    <div class="md:col-span-2">
                        <label class="form-label">Signature Image</label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            {{-- Preview box (wide, checkerboard so transparent PNGs are visible) --}}
                            <div class="shrink-0 w-56 h-24 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden bg-white"
                                 style="background-image:linear-gradient(45deg,#f3f4f6 25%,transparent 25%),linear-gradient(-45deg,#f3f4f6 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#f3f4f6 75%),linear-gradient(-45deg,transparent 75%,#f3f4f6 75%);background-size:14px 14px;background-position:0 0,0 7px,7px -7px,-7px 0;">
                                @if($setting->captain_signature)
                                    <img id="signature-preview"
                                        src="{{ asset('storage/' . str_replace('public/', '', $setting->captain_signature)) }}"
                                        class="max-w-full max-h-full object-contain" alt="Captain Signature">
                                @else
                                    <div id="signature-placeholder" class="text-gray-300 dark:text-gray-600 text-center">
                                        <i class="mgc_signature_line text-3xl"></i>
                                        <p class="text-[10px] mt-0.5">No signature</p>
                                    </div>
                                    <img id="signature-preview" src="" class="max-w-full max-h-full object-contain hidden" alt="Signature Preview">
                                @endif
                            </div>
                            <div class="flex-1">
                                <label for="signature-input"
                                    class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer inline-flex items-center">
                                    <i class="mgc_upload_2_line me-1"></i> Upload Signature
                                </label>
                                <input type="file" id="signature-input" name="captain_signature"
                                    class="hidden" accept="image/png,image/jpeg,image/jpg">
                                <p class="text-xs text-gray-400 mt-2">Transparent PNG recommended (so it sits cleanly on the certificate). Max 2MB.</p>
                                @error('captain_signature') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Citizen ID Format --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_card_line me-2 text-primary"></i>Citizen ID Format</h5>
                </div>
                <div class="grid md:grid-cols-2 grid-cols-1 gap-4 p-6">

                    <div>
                        <label class="form-label">ID Prefix</label>
                        <input type="text" name="citizen_id_prefix" id="inp-prefix"
                            value="{{ old('citizen_id_prefix', $setting->citizen_id_prefix ?? 'EBT') }}"
                            class="form-input @error('citizen_id_prefix') border-danger @enderror"
                            placeholder="e.g. EBT"
                            maxlength="20"
                            oninput="updateIdPreview()">
                        <p class="text-xs text-gray-400 mt-1">Letters and numbers only, no spaces.</p>
                        @error('citizen_id_prefix') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Number of Digits</label>
                        <input type="number" name="citizen_id_digits" id="inp-digits"
                            value="{{ old('citizen_id_digits', $setting->citizen_id_digits ?? 6) }}"
                            class="form-input @error('citizen_id_digits') border-danger @enderror"
                            min="1" max="10"
                            oninput="updateIdPreview()">
                        <p class="text-xs text-gray-400 mt-1">How many digits to pad (1–10).</p>
                        @error('citizen_id_digits') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Default ID Validity</label>
                        <select name="id_validity" class="form-select @error('id_validity') border-danger @enderror">
                            @foreach(['6m' => '6 Months', '1y' => '1 Year', '2y' => '2 Years'] as $val => $label)
                            <option value="{{ $val }}" {{ old('id_validity', $setting->id_validity ?? '2y') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            How long a generated barangay ID stays valid. Applied to every new ID.
                        </p>
                        @error('id_validity') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label text-gray-400">Preview</label>
                        <div class="flex items-center gap-2">
                            <span id="id-format-preview"
                                  class="font-mono font-bold text-sm tracking-widest bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600">
                                {{ ($setting->citizen_id_prefix ?? 'EBT') }}-{{ str_pad('1', $setting->citizen_id_digits ?? 6, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="text-xs text-gray-400">← how citizen IDs will appear</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- ── Right Column ── --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

            {{-- Barangay Logo Upload --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_pic_line me-2 text-primary"></i>Barangay Logo / Seal</h5>
                </div>
                <div class="p-6 flex flex-col items-center gap-4">

                    @if($setting->logo)
                        <img id="logo-preview"
                            src="{{ asset('storage/' . str_replace('public/', '', $setting->logo)) }}"
                            class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow"
                            alt="Barangay Logo">
                    @else
                        <div id="logo-placeholder"
                            class="w-28 h-28 rounded-full bg-primary/20 flex items-center justify-center text-primary shadow">
                            <i class="mgc_building_2_line text-4xl"></i>
                        </div>
                        <img id="logo-preview" src="" class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow hidden" alt="Logo Preview">
                    @endif

                    <label for="logo-input"
                        class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer w-full text-center">
                        <i class="mgc_upload_2_line me-1"></i> Upload Logo
                    </label>
                    <input type="file" id="logo-input" name="logo"
                        class="hidden" accept="image/jpeg,image/png,image/jpg">
                    <p class="text-xs text-gray-400 text-center">JPG or PNG. Max 2MB.<br>Recommended: square image (512×512).</p>

                </div>
            </div>

            {{-- Municipal Logo Upload --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_pic_2_line me-2 text-primary"></i>Municipal Logo / Seal</h5>
                </div>
                <div class="p-6 flex flex-col items-center gap-4">

                    @if($setting->municipal_logo)
                        <img id="municipal-logo-preview"
                            src="{{ asset('storage/' . str_replace('public/', '', $setting->municipal_logo)) }}"
                            class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow"
                            alt="Municipal Logo">
                    @else
                        <div id="municipal-logo-placeholder"
                            class="w-28 h-28 rounded-full bg-secondary/20 flex items-center justify-center text-secondary shadow">
                            <i class="mgc_building_3_line text-4xl"></i>
                        </div>
                        <img id="municipal-logo-preview" src="" class="w-28 h-28 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow hidden" alt="Municipal Logo Preview">
                    @endif

                    <label for="municipal-logo-input"
                        class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer w-full text-center">
                        <i class="mgc_upload_2_line me-1"></i> Upload Municipal Logo
                    </label>
                    <input type="file" id="municipal-logo-input" name="municipal_logo"
                        class="hidden" accept="image/jpeg,image/png,image/jpg">
                    <p class="text-xs text-gray-400 text-center">JPG or PNG. Max 2MB.<br>Used on printed documents and certificates.</p>

                </div>
            </div>

            {{-- Preview Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_eye_line me-2 text-primary"></i>Preview</h5>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-3">
                        @if($setting->logo)
                            <img src="{{ asset('storage/' . str_replace('public/', '', $setting->logo)) }}"
                                class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-gray-600"
                                alt="Logo" id="preview-logo-small">
                        @else
                            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center text-primary" id="preview-logo-small-placeholder">
                                <i class="mgc_building_2_line text-xl"></i>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100" id="preview-name">
                                {{ $setting->barangay_name ?: 'Barangay Name' }}
                            </p>
                            <p class="text-xs text-gray-500" id="preview-address">
                                {{ $setting->fullAddress() ?: 'Municipality, Province' }}
                            </p>
                        </div>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700 my-3">
                    <div class="text-xs text-gray-500 space-y-1">
                        <p><span class="font-medium">Captain:</span>
                            <span id="preview-captain">{{ $setting->captain_name ?: '—' }}</span>
                        </p>
                        <p><span class="font-medium">Contact:</span>
                            <span id="preview-contact">{{ $setting->contact ?: '—' }}</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Save --}}
            <div class="card">
                <div class="p-6">
                    <button type="submit" class="btn bg-primary text-white w-full">
                        <i class="mgc_save_line me-1"></i> Save Settings
                    </button>
                </div>
            </div>

                </div>

            </div>
        </form>

    </div>

</div>

@endsection

@section('script')
<script>
window.addEventListener('DOMContentLoaded', () => {
    // Logo preview helper
    function wireLogoPreview(inputId, previewId, placeholderId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                const preview     = document.getElementById(previewId);
                const placeholder = document.getElementById(placeholderId);
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    }

    wireLogoPreview('logo-input',          'logo-preview',          'logo-placeholder');
    wireLogoPreview('municipal-logo-input', 'municipal-logo-preview', 'municipal-logo-placeholder');
    wireLogoPreview('signature-input',      'signature-preview',      'signature-placeholder');

    // Live preview updates
    const fields = {
        'barangay_name': 'preview-name',
        'captain_name':  'preview-captain',
        'contact':       'preview-contact',
    };

    Object.entries(fields).forEach(([inputName, previewId]) => {
        const input   = document.querySelector(`[name="${inputName}"]`);
        const preview = document.getElementById(previewId);
        if (!input || !preview) return;
        input.addEventListener('input', () => {
            preview.textContent = input.value || '—';
        });
    });

    // Live address preview (municipality + province)
    function updateAddressPreview() {
        const muni     = document.querySelector('[name="municipality"]')?.value || '';
        const province = document.querySelector('[name="province"]')?.value || '';
        const parts    = [muni, province].filter(Boolean);
        document.getElementById('preview-address').textContent = parts.join(', ') || 'Municipality, Province';
    }

    document.querySelector('[name="municipality"]')?.addEventListener('input', updateAddressPreview);
    document.querySelector('[name="province"]')?.addEventListener('input', updateAddressPreview);
});

function updateIdPreview() {
    const prefix = document.getElementById('inp-prefix')?.value.trim() || 'EBT';
    const digits = Math.min(10, Math.max(1, parseInt(document.getElementById('inp-digits')?.value) || 6));
    const padded = String(1).padStart(digits, '0');
    document.getElementById('id-format-preview').textContent = prefix + '-' + padded;
}
</script>
@endsection
