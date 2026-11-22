@extends('layouts.vertical', [
    'title'         => 'Login History',
    'breadcrumbs'   => [
        ['label' => 'Settings', 'url' => route('settings.index')],
        ['label' => 'Users',    'url' => route('users.index')],
        ['label' => 'Login History', 'url' => ''],
    ],
    'tagline'       => 'Every sign-in, wrong password, lockout and log-out, with the device and IP address used.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')
<div class="grid grid-cols-12 gap-6">

    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    <div class="col-span-12 lg:col-span-9 xl:col-span-10 space-y-6">

        {{-- ── Today ── --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['Logins today',        $stats['login'],   'mgc_login_line',       'bg-success/10', 'text-success', 'login'],
                ['People signed in',    $stats['users'],   'mgc_user_3_line',      'bg-primary/10', 'text-primary', 'login'],
                ['Failed today',        $stats['failed'],  'mgc_alert_line',       'bg-warning/10', 'text-warning', 'failed'],
                ['Locked / blocked',    $stats['blocked'], 'mgc_lock_line',        'bg-danger/10',  'text-danger',  'locked'],
            ] as [$label, $count, $icon, $bg, $fg, $ev])
            <a href="{{ route('settings.login-history', ['event' => $ev, 'date_from' => today()->toDateString(), 'date_to' => today()->toDateString()]) }}"
               class="card p-5 hover:shadow-md transition">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full {{ $bg }} flex items-center justify-center shrink-0">
                        <i class="{{ $icon }} {{ $fg }} text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($count) }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- ── Filters ── --}}
        <form method="GET" action="{{ route('settings.login-history') }}" class="card p-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_11rem_12rem_10rem_10rem_auto] items-end">
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1">Search</label>
                    <div class="relative">
                        <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email or IP" class="form-input text-sm ps-9 w-full">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1">What happened</label>
                    <select name="event" class="form-select text-sm w-full">
                        <option value="">All</option>
                        @foreach(\App\Models\LoginLog::EVENTS as $key => [$label])
                        <option value="{{ $key }}" @selected(($filters['event'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1">Account</label>
                    <select name="user_id" class="form-select text-sm w-full">
                        <option value="">All accounts</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-input text-sm w-full">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-input text-sm w-full">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn bg-primary text-white"><i class="mgc_filter_line"></i> Filter</button>
                    @if(array_filter($filters))
                    <a href="{{ route('settings.login-history') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Clear</a>
                    @endif
                </div>
            </div>
            @if(!empty($filters['ip']))
            <input type="hidden" name="ip" value="{{ $filters['ip'] }}">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                Showing IP <span class="font-mono">{{ $filters['ip'] }}</span> only ·
                <a href="{{ route('settings.login-history', \Illuminate\Support\Arr::except($filters, 'ip')) }}" class="text-primary hover:underline">show all IPs</a>
            </p>
            @endif
        </form>

        {{-- ── List ── --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h4 class="card-title">Sign-in activity</h4>
                <span class="text-xs text-gray-400">{{ number_format($logs->total()) }} {{ \Illuminate\Support\Str::plural('entry', $logs->total()) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Date &amp; time</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Account</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">What happened</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Device</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">IP address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($logs as $log)
                        @php [$evLabel, $evPill, $evDot] = \App\Models\LoginLog::EVENTS[$log->event] ?? [ucfirst($log->event), 'bg-gray-100 text-gray-700', 'bg-gray-400']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-800 dark:text-gray-200">
                                {{ $log->created_at->format('M d, Y') }}
                                <span class="block text-xs text-gray-400">{{ $log->created_at->format('g:i A') }} · {{ $log->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($log->user)
                                <a href="{{ route('settings.login-history', ['user_id' => $log->user_id]) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-primary">{{ $log->user->name }}</a>
                                <span class="block text-xs text-gray-400">{{ $log->user->email }}</span>
                                @else
                                <span class="text-gray-800 dark:text-gray-200">{{ $log->email ?: '—' }}</span>
                                <span class="block text-xs text-gray-400">No matching account</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $evPill }}">
                                    <span class="w-1.5 h-1.5 inline-block {{ $evDot }} rounded-full"></span>{{ $evLabel }}
                                </span>
                                @if($log->note)
                                <span class="block text-xs text-gray-400 mt-1">{{ $log->note }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300" title="{{ $log->user_agent }}">
                                <i class="{{ $log->isMobile() ? 'mgc_cellphone_line' : 'mgc_computer_line' }} text-gray-400 me-1"></i>{{ $log->device() }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($log->ip)
                                <a href="{{ route('settings.login-history', ['ip' => $log->ip]) }}" class="font-mono text-xs text-gray-700 dark:text-gray-300 hover:text-primary" title="Show everything from this IP">{{ $log->ip }}</a>
                                @else
                                <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-12 text-gray-400">
                            <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No sign-in activity found.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700">{{ $logs->links() }}</div>
            @endif
        </div>

    </div>
</div>
@endsection
