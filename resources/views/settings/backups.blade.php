@extends('layouts.vertical', [
    'title'         => 'Backups',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Automatic weekly backups of the database and uploads, copied to Google Drive.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')
<div class="grid grid-cols-12 gap-6">

    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    <div class="col-span-12 lg:col-span-9 xl:col-span-10 space-y-6">

        @if(session('success'))
        <div class="p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
            <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-success font-medium">{{ session('success') }}</p>
        </div>
        @endif
        @if(session('error'))
        <div class="p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
            <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
        </div>
        @endif

        {{-- Setup problems block every backup --}}
        @if($problems)
        <div class="p-4 rounded-lg bg-danger/10 border border-danger/30">
            <p class="text-sm font-semibold text-danger flex items-center gap-2"><i class="mgc_alert_line text-lg"></i> Backups cannot run yet</p>
            <ul class="list-disc ms-6 mt-1 text-sm text-gray-700 dark:text-gray-200 space-y-0.5">
                @foreach($problems as $p)<li>{{ $p }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Status row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Last backup --}}
            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $overdue ? 'bg-danger/10' : 'bg-success/10' }}">
                        <i class="{{ $overdue ? 'mgc_alert_line text-danger' : 'mgc_check_circle_line text-success' }} text-2xl"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last good backup</p>
                        @if($lastSuccess)
                            <p class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $lastSuccess->started_at->format('M d, Y g:i A') }}</p>
                            <p class="text-xs {{ $overdue ? 'text-danger font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $lastSuccess->started_at->diffForHumans() }} · {{ $lastSuccess->sizeLabel() }}
                                @if($overdue) — older than a week, check below @endif
                            </p>
                        @else
                            <p class="text-lg font-bold text-danger">None yet</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $schedulerSeen && $schedulerSeen->gt(now()->subMinutes(15)) ? 'bg-primary/10' : 'bg-warning/10' }}">
                        <i class="mgc_time_line text-2xl {{ $schedulerSeen && $schedulerSeen->gt(now()->subMinutes(15)) ? 'text-primary' : 'text-warning' }}"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Automatic backup</p>
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-100">Every {{ $day }}, {{ $time }}</p>
                        @if($schedulerSeen && $schedulerSeen->gt(now()->subMinutes(15)))
                            <p class="text-xs text-gray-500 dark:text-gray-400">Next: {{ $nextRun->format('M d, Y g:i A') }} · keeps the last {{ intdiv($keepDays, 7) }} weeks</p>
                        @else
                            <p class="text-xs text-warning font-medium">Server timer not running — the cron job is missing (see below)</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Google Drive --}}
            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $drive ? 'bg-success/10' : 'bg-gray-100 dark:bg-gray-700' }}">
                        <i class="mgc_cloud_line text-2xl {{ $drive ? 'text-success' : 'text-gray-400' }}"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Google Drive copy</p>
                        @if($drive)
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate" title="{{ $drive['email'] }}">{{ $drive['email'] ?: 'Connected' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="Folder in Google Drive"><i class="mgc_folder_line"></i> {{ \App\Support\GoogleDrive::folderName() }}</p>
                            @if(!empty($drive['folder_id']))
                            <a href="https://drive.google.com/drive/folders/{{ $drive['folder_id'] }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 mt-1 text-xs font-medium text-primary hover:underline">
                                <i class="mgc_external_link_line"></i> Open folder in Google Drive
                            </a>
                            @endif
                            <form method="POST" action="{{ route('settings.backups.google.disconnect') }}" id="disconnect-form">@csrf
                                <button type="button" onclick="confirmDisconnect()" class="text-xs text-danger hover:underline">Disconnect</button>
                            </form>
                        @elseif($driveReady)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Not connected</p>
                            <a href="{{ route('settings.backups.google.connect') }}" class="btn btn-sm bg-primary text-white"><i class="mgc_link_line"></i> Connect Google Drive</a>
                        @else
                            <p class="text-xs text-warning font-medium">Google credentials are not in the server .env yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Backups list --}}
        <div class="card">
            <div class="card-header">
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h4 class="card-title">Backups</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Each one is a password-protected ZIP with the whole database and every uploaded photo and file.</p>
                    </div>
                    <form method="POST" action="{{ route('settings.backups.run') }}" id="run-form">@csrf
                        <button type="button" onclick="runBackup()" id="run-btn" class="btn bg-primary text-white" @disabled($problems)>
                            <i class="mgc_save_line"></i> Back up now
                        </button>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Google Drive</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Made by</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($backups as $b)
                        <tr>
                            <td class="px-6 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $b->started_at?->format('M d, Y') }}
                                <span class="block text-xs text-gray-400">{{ $b->started_at?->format('g:i A') }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @php
                                    [$cls, $dot, $label] = match ($b->status) {
                                        'success' => ['bg-green-100 text-green-800', 'bg-green-400', 'Done'],
                                        'failed'  => ['bg-red-100 text-red-800', 'bg-red-400', 'Failed'],
                                        default   => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400', 'Running'],
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $cls }}">
                                    <span class="w-1.5 h-1.5 inline-block {{ $dot }} rounded-full"></span>{{ $label }}
                                </span>
                                @if($b->message)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md">{{ $b->message }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                @if($b->size){{ $b->sizeLabel() }}<span class="block text-xs text-gray-400">{{ number_format($b->files_count) }} files</span>@else — @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if($b->drive_status === 'uploaded')
                                    <a href="{{ $b->driveUrl() }}" target="_blank" rel="noopener" class="text-success text-xs font-medium hover:underline"><i class="mgc_check_line"></i> Copied — open</a>
                                @elseif($b->drive_status === 'failed')
                                    <span class="text-danger text-xs font-medium"><i class="mgc_close_line"></i> Not copied</span>
                                @elseif($b->drive_status === 'skipped')
                                    <span class="text-gray-400 text-xs">Server only</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $b->triggeredBy?->name ?? 'Automatic' }}
                            </td>
                            <td class="px-6 py-3 text-end whitespace-nowrap">
                                @if($b->hasLocalFile())
                                    <a href="{{ route('settings.backups.download', $b) }}" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_download_2_line"></i></a>
                                @elseif($b->deleted_local_at)
                                    <span class="text-xs text-gray-400" title="Older than {{ $keepDays }} days">Removed</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-12 text-gray-400">
                            <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No backups yet. Click "Back up now" to make the first one.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Help --}}
        <div class="card">
            <button type="button" onclick="toggleAccordion('help-body', 'help-chevron')" class="w-full flex items-center justify-between px-6 py-4 text-start">
                <span class="font-semibold text-gray-800 dark:text-gray-100"><i class="mgc_question_line me-1 text-primary"></i> How backups work · setting up the server · restoring</span>
                <i id="help-chevron" class="mgc_down_line text-gray-400"></i>
            </button>
            <div id="help-body" class="hidden px-6 pb-6 text-sm text-gray-600 dark:text-gray-300 space-y-4">
                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">What is backed up</p>
                    <p>Every {{ $day }} at {{ $time }} the whole database and all uploaded files (photos, signatures, logos, templates) are put into one ZIP locked with the backup password. It is kept on the server and copied to Google Drive. Backups older than {{ $keepDays }} days (the last {{ intdiv($keepDays, 7) }} weeks are kept) are deleted in both places. Use "Back up now" any time, e.g. before a big update. If a backup fails, every Super Admin gets an email.</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">Server setup (once)</p>
                    <p>In CloudPanel → Sites → Cron Jobs, add a job that runs every minute:</p>
                    <code class="block mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700 text-xs">* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">Google Cloud redirect address</p>
                    <p>In Google Cloud → Clients → your web client → <em>Authorized redirect URIs</em>, this exact address must be listed (it comes from APP_URL in .env). Google error "redirect_uri_mismatch" means it is missing or different:</p>
                    <code class="block mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700 text-xs break-all">{{ \App\Support\GoogleDrive::callbackUrl() }}</code>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">Opening a backup</p>
                    <p>Download it here or from Google Drive and open it with <strong>7-Zip</strong> (free) using the backup password. Windows' built-in "Extract all" cannot open password-protected backups. Inside: <code>database.sql</code> and the <code>uploads</code> folder.</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">Restoring (IT staff)</p>
                    <p>Restoring replaces live data, so it is done on the server, not from this page: import <code>database.sql</code> into an <strong>empty</strong> database (CloudPanel → Databases → Import, or <code>mysql dbname &lt; database.sql</code>), point the system's .env at it, and copy the <code>uploads</code> folder into <code>storage/app/public</code>.</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('inline-scripts')
<script>
function toggleAccordion(bodyId, chevronId) {
    const opening = document.getElementById(bodyId).classList.contains('hidden');
    document.getElementById(bodyId).classList.toggle('hidden');
    document.getElementById(chevronId).classList.toggle('mgc_down_line', !opening);
    document.getElementById(chevronId).classList.toggle('mgc_up_line', opening);
}
function runBackup() {
    Swal.fire({
        title: 'Make a backup now?',
        text: 'It takes about a minute. Please keep this page open.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, back up now',
        reverseButtons: true,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#727cf5', 'important'),
    }).then(r => {
        if (!r.isConfirmed) return;
        const btn = document.getElementById('run-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="mgc_loading_line animate-spin"></i> Backing up…';
        document.getElementById('run-form').submit();
    });
}
function confirmDisconnect() {
    Swal.fire({
        title: 'Disconnect Google Drive?',
        text: 'New backups will be kept on the server only. Backups already in Google Drive stay there.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Disconnect',
        reverseButtons: true,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important'),
    }).then(r => { if (r.isConfirmed) document.getElementById('disconnect-form').submit(); });
}
</script>
@endpush
