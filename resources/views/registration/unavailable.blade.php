@extends('registration._shell', ['pageTitle' => 'Registration unavailable'])

@section('body')
<div class="card p-8 text-center">
    <i class="mgc_link_unlink_line text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">This registration link is not available</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        @switch($state)
            @case('expired') The link has expired. @break
            @case('full') The link has reached its registration limit. @break
            @case('disabled') The link has been turned off. @break
            @default The link is not valid.
        @endswitch
        Please ask the barangay hall for a new link, or register in person.
    </p>
</div>
@endsection
