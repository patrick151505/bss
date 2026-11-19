@extends('layouts.vertical', [
    'title'         => 'Print Paper',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Choose the paper template each printout uses.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@php $canEdit = auth()->user()->can('settings.edit'); @endphp

@section('content')

<div class="grid grid-cols-12 gap-6">

    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

        @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
            <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-success font-medium">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
            <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm font-medium text-danger">Please check the selections below.</p>
        </div>
        @endif

        <form action="{{ route('settings.print-paper.update') }}" method="POST">
            @csrf
            <fieldset @unless($canEdit) disabled @endunless>

            <div class="card mb-6">
                <div class="p-5 flex flex-wrap items-start justify-between gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300 max-w-3xl">
                        <p>Pick a paper template for each printout. The printout uses the template's letterhead, paper size, orientation and margins.</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <strong>Latest</strong> follows the template whenever a new version is saved. A specific version stays fixed.
                            <strong>None</strong> prints with the plain header from General Info.
                        </p>
                    </div>
                    @can('documents.view')
                    <a href="{{ route('documents.templates.index') }}" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white shrink-0">
                        <i class="mgc_paper_line me-1"></i> Paper Templates
                    </a>
                    @endcan
                </div>
                @if($paperOptions->isEmpty())
                <div class="px-5 pb-5">
                    <p class="text-xs text-warning">No paper templates yet. Create one under Documents → Paper Templates; until then every printout uses the plain header.</p>
                </div>
                @endif
            </div>

            <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                @foreach($prints as $print => [$label, $where])
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{ $label }}</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $where }}</p>
                    </div>
                    <div class="p-5">
                        @include('settings.partials.paper-picker', ['print' => $print, 'label' => ''])
                    </div>
                </div>
                @endforeach
            </div>

            @if($canEdit)
            <div class="flex justify-end mt-6">
                <button type="submit" class="btn bg-primary text-white">
                    <i class="mgc_save_line me-1"></i> Save Print Paper
                </button>
            </div>
            @endif

            </fieldset>
        </form>

        @unless($canEdit)
        <p class="text-xs text-gray-400 mt-4">View only. Ask an administrator to change the print paper.</p>
        @endunless
    </div>
</div>

@endsection

@section('script')
<script>
// Choosing a template opens its "Pin a version" list. Only the open list's
// radios are enabled (so only they are submitted); if none of them is
// chosen yet, "Latest" is selected.
document.querySelectorAll('.paper-picker').forEach(picker => {
    const sync = () => {
        const chosen = picker.querySelector('.paper-tpl-radio:checked')?.value ?? '';
        picker.querySelectorAll('.paper-versions').forEach(block => {
            const open = block.dataset.tpl === chosen;
            block.classList.toggle('hidden', !open);
            block.querySelectorAll('input[type=radio]').forEach(r => { r.disabled = !open; if (!open) r.checked = false; });
            if (open && !block.querySelector('input[type=radio]:checked')) {
                block.querySelector('.paper-ver-latest').checked = true;
            }
        });
    };
    picker.querySelectorAll('.paper-tpl-radio').forEach(r => r.addEventListener('change', sync));
    sync();
});
</script>
@endsection
