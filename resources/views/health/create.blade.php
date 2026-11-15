@extends('layouts.vertical', [
    'title'         => 'New Check-up',
    'sub_title'     => 'Health',
    'sub_title_url' => route('health.index'),
    'tagline'       => 'Record vital signs, findings and medicines given',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')
@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }} Ready for the next patient.</p>
</div>
@endif

<form method="POST" action="{{ route('health.store') }}" novalidate>
    @csrf
    @include('health._form', ['layout' => 'wizard'])
</form>
@endsection
