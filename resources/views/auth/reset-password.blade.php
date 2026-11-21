<!DOCTYPE html>
<html lang="en" data-mode="dark">

<head>
    @include('layouts.shared/title-meta', ['title' => "Set a new password"])

    @include('layouts.shared/head-css')
</head>

<body>

@include('layouts.shared/top-loader')

<div class="bg-gradient-to-r from-rose-100 to-teal-100 dark:from-gray-700 dark:via-gray-900 dark:to-black">

    <div class="min-h-screen w-screen flex justify-center items-center py-8">

        <div class="2xl:w-1/4 lg:w-1/3 md:w-1/2 w-full">
            <div class="card overflow-hidden sm:rounded-md rounded-none">
                <div class="p-6">
                    <a href="{{ route('login') }}" class="flex justify-center mb-6">
                        <img class="block dark:hidden" style="height:3.5rem;" src="{{ asset('images/logo-dark.png') }}" alt="">
                        <img class="hidden dark:block" style="height:3.5rem;" src="{{ asset('images/logo-light.png') }}" alt="">
                    </a>

                    <div class="text-center mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Set a new password</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose a password with at least 8 characters.</p>
                    </div>

                    @if($errors->any())
                    <div class="mb-4 p-3 rounded-md bg-danger/10 border border-danger/30 text-sm text-danger flex gap-2">
                        <i class="mgc_alert_line text-lg shrink-0"></i>
                        <div>
                            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                            @if($errors->has('email'))
                            <p class="mt-1 text-xs">If the link has expired, ask your administrator to send a new one.</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-200 mb-2" for="reset-email">Email Address</label>
                            <input id="reset-email" class="form-input bg-gray-50 dark:bg-gray-700" type="email" name="email"
                                   value="{{ old('email', $request->email) }}" required readonly>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-200 mb-2" for="reset-password">New password</label>
                            <div class="relative">
                                <input id="reset-password" class="form-input pe-10" type="password" name="password"
                                       placeholder="At least 8 characters" minlength="8" required autofocus autocomplete="new-password">
                                <button type="button" class="absolute inset-y-0 end-0 px-3 text-gray-400 hover:text-primary" onclick="togglePw(this)" title="Show password">
                                    <i class="mgc_eye_line text-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-200 mb-2" for="reset-password2">Confirm new password</label>
                            <input id="reset-password2" class="form-input" type="password" name="password_confirmation"
                                   placeholder="Type it again" minlength="8" required autocomplete="new-password">
                            <p id="pw-match" class="text-xs text-danger mt-1 hidden">The passwords don't match.</p>
                        </div>

                        <div class="flex justify-center mb-4">
                            <button class="btn w-full text-white bg-primary gap-2"><i class="mgc_key_2_line"></i> Save new password</button>
                        </div>
                    </form>

                    <p class="text-gray-500 dark:text-gray-400 text-center text-sm">Remembered it? <a href="{{ route('login') }}" class="text-primary ms-1"><b>Log In</b></a></p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function togglePw(btn) {
    const show = document.getElementById('reset-password').type === 'password';
    ['reset-password', 'reset-password2'].forEach(id => document.getElementById(id).type = show ? 'text' : 'password');
    btn.querySelector('i').className = (show ? 'mgc_eye_close_line' : 'mgc_eye_line') + ' text-lg';
}
(function () {
    const a = document.getElementById('reset-password'), b = document.getElementById('reset-password2'), msg = document.getElementById('pw-match');
    const check = () => { const bad = b.value && a.value !== b.value; msg.classList.toggle('hidden', !bad); b.setCustomValidity(bad ? "The passwords don't match." : ''); };
    a.addEventListener('input', check); b.addEventListener('input', check);
})();
</script>

</body>

</html>
