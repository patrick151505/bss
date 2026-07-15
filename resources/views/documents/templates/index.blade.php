@extends('layouts.vertical', [
    'title'         => 'Paper Templates',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Design once, reuse across multiple document types.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<div class="grid grid-cols-12 gap-6">

    {{-- ── Settings Sub-nav ── --}}
    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    {{-- ── Settings Content ── --}}
    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-end mb-6">
    <a href="{{ route('documents.templates.create') }}" class="btn bg-primary text-white flex items-center gap-2">
        <i class="mgc_add_line"></i> New Template
    </a>
</div>

@if($templates->isEmpty())
<div class="card p-12 text-center">
    <i class="mgc_paper_line text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
    <p class="text-gray-500 text-sm">No paper templates yet.</p>
    <a href="{{ route('documents.templates.create') }}" class="btn bg-primary text-white mt-4 inline-flex items-center gap-2">
        <i class="mgc_add_line"></i> Create First Template
    </a>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach($templates as $tpl)
    <div class="card flex flex-col overflow-hidden">

        {{-- Paper preview thumbnail --}}
        <div class="relative bg-gray-100 dark:bg-gray-800 h-48 flex items-center justify-center overflow-hidden">
            @if($tpl->currentVersion?->paper_bg)
                <img src="{{ asset('storage/' . $tpl->currentVersion->paper_bg) }}"
                     alt="{{ $tpl->name }}"
                     class="h-full w-full object-cover object-top">
            @else
                <div class="text-center">
                    <i class="mgc_paper_line text-4xl text-gray-300 dark:text-gray-600 block mb-1"></i>
                    <span class="text-xs text-gray-400">No background image</span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Auto-header from Settings</p>
                </div>
            @endif
            <div class="absolute top-2 right-2">
                @if($tpl->is_active)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success/90 text-white">Active</span>
                @else
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-400/90 text-white">Inactive</span>
                @endif
            </div>
        </div>

        {{-- Info --}}
        <div class="p-4 flex flex-col flex-1 gap-3">
            <div>
                <h6 class="font-semibold text-gray-800 dark:text-gray-100">{{ $tpl->name }}</h6>
                @if($tpl->description)
                <p class="text-xs text-gray-400 mt-0.5">{{ $tpl->description }}</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                <span class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                    <i class="mgc_paper_line"></i> {{ strtoupper($tpl->currentVersion?->paper_size ?? 'A4') }}
                </span>
                <span class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                    <i class="mgc_refresh_1_line"></i> {{ ucfirst($tpl->currentVersion?->orientation ?? 'portrait') }}
                </span>
                @if($tpl->currentVersion)
                <span class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                    <i class="mgc_history_line"></i> v{{ $tpl->currentVersion->version }}
                </span>
                @endif
                <span class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                    <i class="mgc_document_2_line"></i> {{ $tpl->document_types_count }} doc type(s)
                </span>
            </div>

            <div class="text-xs text-gray-400 flex items-center gap-1">
                <i class="mgc_time_line"></i> Updated {{ $tpl->updated_at->diffForHumans() }}
            </div>

            <div class="flex gap-2 mt-auto pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('documents.templates.edit', $tpl) }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3 flex items-center gap-1 flex-1 justify-center">
                    <i class="mgc_edit_2_line"></i> Edit
                </a>
                <form action="{{ route('documents.templates.duplicate', $tpl) }}" method="POST">
                    @csrf
                    <button type="submit" title="Duplicate this template"
                            class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-1">
                        <i class="mgc_copy_line"></i>
                    </button>
                </form>
                <form action="{{ route('documents.templates.toggle', $tpl) }}" method="POST">
                    @csrf @method('PATCH')
                    @if($tpl->is_active)
                    <button type="submit"
                            class="btn border-warning/40 text-warning text-sm py-1.5 px-3 hover:bg-warning/10 flex items-center gap-1">
                        <i class="mgc_pause_circle_line"></i> Deactivate
                    </button>
                    @else
                    <button type="submit"
                            class="btn border-success/40 text-success text-sm py-1.5 px-3 hover:bg-success/10 flex items-center gap-1">
                        <i class="mgc_play_circle_line"></i> Activate
                    </button>
                    @endif
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

    </div>

</div>

@endsection
