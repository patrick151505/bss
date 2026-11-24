@php
    // Barangay logo from Settings → Barangay; falls back to the system logo.
    $setting  = \App\Models\Setting::instance();
    $brgyLogo = $setting->logo && \Illuminate\Support\Facades\Storage::exists($setting->logo)
        ? asset(str_replace('public/', 'storage/', $setting->logo))
        : null;
    $brgyName = $setting->barangay_name;
    $place    = collect([$setting->municipality, $setting->province])->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="en" data-mode="dark">

<head>
    @include('layouts.shared/title-meta', ['title' => "Login"])

    @include('layouts.shared/head-css')
</head>

<body>

@include('layouts.shared/top-loader')

<div class="min-h-screen w-full bg-gradient-to-br from-rose-100 to-teal-100 dark:from-gray-700 dark:via-gray-900 dark:to-black
            flex flex-col justify-center items-center px-4 py-8 sm:py-12">

    <div class="w-full max-w-sm sm:max-w-md">

        {{-- Barangay logo + name --}}
        <div class="flex flex-col items-center text-center mb-6">
            @if($brgyLogo)
                <img src="{{ $brgyLogo }}" alt="{{ $brgyName ?: 'Barangay' }} logo"
                     class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover bg-white shadow-lg ring-4 ring-white/80 dark:ring-gray-700">
            @else
                <img class="block dark:hidden" style="height:3.5rem;" src="{{ asset('images/logo-dark.png') }}" alt="Logo">
                <img class="hidden dark:block" style="height:3.5rem;" src="{{ asset('images/logo-light.png') }}" alt="Logo">
            @endif

            @if($brgyName)
            <h1 class="mt-4 text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100 leading-tight">{{ $brgyName }}</h1>
            @endif
            @if($place)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $place }}</p>
            @endif
        </div>

        <div class="card overflow-hidden rounded-2xl shadow-xl">
            <div class="p-6 sm:p-8">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Welcome back</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Log in to continue.</p>

                @if(session('status'))
                <div class="mb-4 p-3 rounded-md bg-success/10 border border-success/30 text-sm text-success font-medium flex gap-2">
                    <i class="mgc_check_circle_line text-lg shrink-0"></i><span>{{ session('status') }}</span>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-4 p-3 rounded-md bg-danger/10 border border-danger/30 text-sm text-danger font-medium flex gap-2" role="alert">
                    <i class="mgc_alert_line text-lg shrink-0"></i><span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-200 mb-2"
                               for="LoggingEmailAddress">Email Address</label>
                        <div class="relative">
                            <i class="mgc_mail_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none"></i>
                            {{-- text-base (16px) stops phones from zooming in on focus --}}
                            <input id="LoggingEmailAddress" class="form-input ps-10 py-2.5 text-base sm:text-sm" type="email"
                                   placeholder="Enter your email" value="{{ old('email') }}" name="email"
                                   autocomplete="username" inputmode="email" autocapitalize="none" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-200 mb-2"
                               for="loggingPassword">Password</label>
                        <div class="relative">
                            <i class="mgc_lock_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none"></i>
                            <input id="loggingPassword" class="form-input ps-10 pe-11 py-2.5 text-base sm:text-sm" type="password"
                                   placeholder="Enter your password" name="password" autocomplete="current-password" required>
                            <button type="button" id="pw-toggle" aria-label="Show password"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center text-gray-400 hover:text-primary">
                                <i class="mgc_eye_line text-lg" id="pw-toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
                        <label class="flex items-center gap-2 py-1 cursor-pointer text-sm text-gray-600 dark:text-gray-300" for="checkbox-signin">
                            <input type="checkbox" class="form-checkbox rounded w-4 h-4" id="checkbox-signin" name="remember" value="1">
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}"
                           class="text-sm text-primary border-b border-dashed border-primary py-1">Forgot password?</a>
                    </div>

                    <button type="submit" id="login-btn" class="btn w-full text-white bg-primary py-3 text-base font-semibold">
                        <span id="login-btn-text">Log In</span>
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6">
            &copy; {{ date('Y') }} {{ $brgyName ?: 'Barangay' }}
        </p>
    </div>

</div>

<script>
// Show / hide password
document.getElementById('pw-toggle').addEventListener('click', function () {
    const inp = document.getElementById('loggingPassword');
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    document.getElementById('pw-toggle-icon').className = (show ? 'mgc_eye_close_line' : 'mgc_eye_line') + ' text-lg';
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
// Prevent double taps while logging in on slow connections
document.getElementById('login-form').addEventListener('submit', function () {
    const btn = document.getElementById('login-btn');
    btn.disabled = true;
    btn.classList.add('opacity-75');
    document.getElementById('login-btn-text').textContent = 'Logging in…';
});
</script>

</body>

</html>
