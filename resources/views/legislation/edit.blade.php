@extends('layouts.vertical', [
    'title'       => 'Edit ' . $legislation->number,
    'tagline'     => $legislation->reference(),
    'breadcrumbs' => [
        ['label' => 'Ordinances & Resolutions', 'url' => route('legislation.index')],
        ['label' => $legislation->number,       'url' => route('legislation.show', $legislation)],
        ['label' => 'Edit',                     'url' => ''],
    ],
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@section('content')
<form method="POST" action="{{ route('legislation.update', $legislation) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('legislation._form')

    <div class="flex justify-end gap-2 mt-6">
        <a href="{{ route('legislation.show', $legislation) }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</a>
        <button type="submit" class="btn bg-primary text-white"><i class="mgc_check_line me-1"></i>Save changes</button>
    </div>
</form>
@endsection
