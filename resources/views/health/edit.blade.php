@extends('layouts.vertical', [
    'title'       => 'Edit Check-up',
    'tagline'     => $checkup->patientName() . ' · ' . $checkup->checked_at->format('M d, Y g:i A'),
    'breadcrumbs' => [
        ['label' => 'Health',                         'url' => route('health.index')],
        ['label' => $checkup->patientName(),          'url' => route('health.show', $checkup)],
        ['label' => 'Edit',                           'url' => ''],
    ],
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@section('content')
<form method="POST" action="{{ route('health.update', $checkup) }}">
    @csrf
    @method('PUT')
    @include('health._form')

    <div class="flex justify-end gap-2 mt-6">
        <a href="{{ route('health.show', $checkup) }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</a>
        <button type="submit" class="btn bg-primary text-white"><i class="mgc_check_line me-1"></i>Save changes</button>
    </div>
</form>
@endsection
