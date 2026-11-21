@extends('registration._shell', ['pageTitle' => 'Registration sent', 'kioskHomeLabel' => 'Done', 'kioskIdle' => 30])

@section('body')
<div class="card p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mx-auto mb-4">
        <i class="mgc_check_circle_line text-success text-4xl"></i>
    </div>
    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Thank you — your registration was sent</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
        Barangay staff will review your details. Once approved, you are listed as a resident.
        You may be asked to visit the barangay hall with a valid ID to confirm your identity.
    </p>
    @if(\App\Models\Kiosk::onKiosk(request()))
    <p class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-6">Please go to the counter so staff can approve your registration.</p>
    <p class="text-base text-gray-500 dark:text-gray-400">Pumunta po sa counter para maaprubahan ang iyong rehistro.</p>
    <a href="{{ route('kiosk.home') }}" class="btn bg-primary text-white text-lg px-8 py-3 mt-6 inline-flex"><i class="mgc_home_3_line"></i> Back to start</a>
    @endif
</div>
@endsection
