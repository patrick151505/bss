<!DOCTYPE html>
<html lang="en" data-mode="dark">

<head>
    @include('layouts.shared/title-meta', ['title' => "Forgot password"])

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
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Forgot your password?</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enter your account's email and we'll send you a link to set a new one.</p>
                    </div>

                    @if(session('status'))
                    <div class="mb-4 p-3 rounded-md bg-success/10 border border-success/30 text-sm text-success font-medium flex gap-2">
                        <i class="mgc_mail_send_line text-lg shrink-0"></i><span>{{ session('status') }}</span>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mb-4 p-3 rounded-md bg-danger/10 border border-danger/30 text-sm text-danger flex gap-2">
                        <i class="mgc_alert_line text-lg shrink-0"></i>
                        <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-200 mb-2" for="forgot-email">Email Address</label>
                            <input id="forgot-email" class="form-input" type="email" name="email" value="{{ old('email') }}"
                                   placeholder="Enter your email" required autofocus>
                        </div>

                        <div class="flex justify-center mb-4">
                            <button class="btn w-full text-white bg-primary gap-2"><i class="mgc_mail_send_line"></i> Send reset link</button>
                        </div>
                    </form>

                    <p class="text-gray-500 dark:text-gray-400 text-center text-sm">Remembered it? <a href="{{ route('login') }}" class="text-primary ms-1"><b>Log In</b></a></p>
                </div>
            </div>
        </div>
    </div>

</div>

</body>

</html>
