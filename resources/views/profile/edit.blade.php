@extends('layouts.vertical', [
    'title'   => 'My Profile',
    'tagline' => 'Update your name and password.',
    'mode'    => $mode ?? '',
    'demo'    => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

@php
    $startTab = $errors->has('current_password') || $errors->has('password') ? 'password' : 'info';
@endphp

<div class="grid grid-cols-12 gap-6">

    {{-- ── Left: Profile Sub-nav ── --}}
    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        <div class="card p-2">
            <ul class="space-y-1">
                <li>
                    <button type="button" onclick="showProfileTab('info')" id="profile-tab-btn-info"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition text-left">
                        <i class="mgc_user_3_line text-lg"></i>
                        Profile
                    </button>
                </li>
                <li>
                    <button type="button" onclick="showProfileTab('password')" id="profile-tab-btn-password"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition text-left">
                        <i class="mgc_lock_line text-lg"></i>
                        Change Password
                    </button>
                </li>
            </ul>
        </div>
    </div>

    {{-- ── Right: Tab Content ── --}}
    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

        {{-- Profile Info --}}
        <div id="profile-tab-info" class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                <i class="mgc_user_3_line text-primary"></i> Profile Information
            </h6>

            <form action="{{ route('profile.update-info') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="flex flex-col items-center gap-3">
                    @if($user->photo)
                        <img id="photo-preview"
                             src="{{ asset('storage/' . str_replace('public/', '', $user->photo)) }}"
                             class="w-24 h-24 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow"
                             alt="{{ $user->name }}">
                    @else
                        <div id="photo-placeholder"
                             class="w-24 h-24 rounded-full bg-primary/20 flex items-center justify-center text-primary shadow">
                            <i class="mgc_user_3_line text-4xl"></i>
                        </div>
                        <img id="photo-preview" src="" class="w-24 h-24 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow hidden" alt="Photo Preview">
                    @endif

                    <label for="photo-input"
                           class="btn border-gray-300 dark:border-gray-600 text-sm cursor-pointer">
                        <i class="mgc_upload_2_line me-1"></i> Change Photo
                    </label>
                    <input type="file" id="photo-input" name="photo"
                           class="hidden" accept="image/jpeg,image/png,image/jpg" onchange="previewProfilePhoto(this)">
                    <p class="text-xs text-gray-400 @error('photo') text-danger @enderror">
                        @error('photo') {{ $message }} @else JPG or PNG. Max 2MB. @enderror
                    </p>
                </div>

                <div>
                    <label class="form-label text-sm">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-input @error('name') border-danger @enderror"
                           value="{{ old('name', $user->name) }}" required maxlength="255">
                    @error('name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label text-sm">Email</label>
                    <input type="email" class="form-input bg-gray-50 dark:bg-gray-800 text-gray-400"
                           value="{{ $user->email }}" disabled>
                    <p class="text-xs text-gray-400 mt-1">Contact an administrator to change your email address.</p>
                </div>

                <button type="submit" class="btn bg-primary text-white flex items-center gap-2">
                    <i class="mgc_save_line"></i> Save Changes
                </button>
            </form>
        </div>

        {{-- Change Password --}}
        <div id="profile-tab-password" class="card p-5">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                <i class="mgc_lock_line text-warning"></i> Change Password
            </h6>

            <form action="{{ route('profile.update-password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="form-label text-sm">Current Password <span class="text-danger">*</span></label>
                    <input type="password" name="current_password"
                           class="form-input @error('current_password') border-danger @enderror"
                           required autocomplete="current-password">
                    @error('current_password') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label text-sm">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-input @error('password') border-danger @enderror"
                           required minlength="8" autocomplete="new-password">
                    @error('password') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label text-sm">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation"
                           class="form-input" required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn bg-warning text-white flex items-center gap-2">
                    <i class="mgc_shield_check_line"></i> Change Password
                </button>
            </form>
        </div>

    </div>

</div>

@endsection

@push('inline-scripts')
<script>
function previewProfilePhoto(input) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        const preview     = document.getElementById('photo-preview');
        const placeholder = document.getElementById('photo-placeholder');
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

function showProfileTab(tab) {
    ['info', 'password'].forEach(t => {
        document.getElementById('profile-tab-' + t).classList.toggle('hidden', t !== tab);
        document.getElementById('profile-tab-btn-' + t).classList.toggle('bg-primary/10', t === tab);
        document.getElementById('profile-tab-btn-' + t).classList.toggle('text-primary', t === tab);
        document.getElementById('profile-tab-btn-' + t).classList.toggle('text-gray-600', t !== tab);
        document.getElementById('profile-tab-btn-' + t).classList.toggle('dark:text-gray-300', t !== tab);
        document.getElementById('profile-tab-btn-' + t).classList.toggle('hover:bg-gray-50', t !== tab);
        document.getElementById('profile-tab-btn-' + t).classList.toggle('dark:hover:bg-gray-800', t !== tab);
    });
}

showProfileTab('{{ $startTab }}');
</script>
@endpush
