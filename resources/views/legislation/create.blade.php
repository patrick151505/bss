@extends('layouts.vertical', [
    'title'         => 'New ' . $legislation->typeLabel(),
    'sub_title'     => 'Ordinances & Resolutions',
    'sub_title_url' => route('legislation.index'),
    'tagline'       => 'Record a measure of the Sangguniang Barangay',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')
<form method="POST" action="{{ route('legislation.store') }}" enctype="multipart/form-data">
    @csrf
    @include('legislation._form')

    <div class="flex justify-end gap-2 mt-6">
        <a href="{{ route('legislation.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</a>
        <button type="submit" class="btn bg-primary text-white"><i class="mgc_check_line me-1"></i>Save</button>
    </div>
</form>
@endsection
