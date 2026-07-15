@extends('layouts.vertical', ['title' => 'Budget Logs', 'sub_title' => 'Budget', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')

{{-- Filters --}}
<div class="card mb-5">
    <div class="card-header">
        <h5 class="card-title"><i class="mgc_history_line me-2 text-primary"></i>Budget Activity Logs</h5>
    </div>
    <div class="px-4 pb-4">
        <form method="GET" action="{{ route('budget.logs.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-xs font-medium text-gray-500">Module</label>
                <select name="module" class="form-select text-sm py-2">
                    <option value="">All Modules</option>
                    @foreach(\App\Models\BudgetLog::MODULE_LABELS as $value => $label)
                        <option value="{{ $value }}" {{ request('module') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-xs font-medium text-gray-500">Action</label>
                <select name="action" class="form-select text-sm py-2">
                    <option value="">All Actions</option>
                    @foreach(\App\Models\BudgetLog::ACTION_COLORS as $value => $_)
                        <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-xs font-medium text-gray-500">User</label>
                <select name="user_id" class="form-select text-sm py-2">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm py-2">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm py-2">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn bg-primary text-white text-sm">
                    <i class="mgc_filter_line me-1"></i> Filter
                </button>
                @if(request()->hasAny(['module','action','user_id','date_from','date_to']))
                    <a href="{{ route('budget.logs.index') }}" class="btn border-gray-300 dark:border-gray-600 text-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Log table --}}
<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left w-40">Date & Time</th>
                    <th class="px-4 py-3 text-left w-32">Action</th>
                    <th class="px-4 py-3 text-left w-32">Module</th>
                    <th class="px-4 py-3 text-left w-36">User</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-center w-16">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($logs as $log)
                @php
                    $actionColor = \App\Models\BudgetLog::ACTION_COLORS[$log->action] ?? 'bg-gray-100 text-gray-600';
                    $hasProps    = ! empty($log->properties);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                        <p>{{ $log->created_at->format('M d, Y') }}</p>
                        <p class="text-gray-400">{{ $log->created_at->format('g:i A') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $actionColor }}">
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs font-medium text-gray-600 dark:text-gray-400">
                        {{ \App\Models\BudgetLog::MODULE_LABELS[$log->module] ?? ucfirst($log->module) }}
                        @if($log->subject_id)
                            <span class="text-gray-400 font-mono"> #{{ $log->subject_id }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                        {{ $log->user?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $log->description }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($hasProps)
                            <button type="button"
                                    class="text-primary hover:text-primary/70 text-lg toggle-details"
                                    data-target="props-{{ $log->id }}"
                                    title="Show details">
                                <i class="mgc_information_line"></i>
                            </button>
                        @endif
                    </td>
                </tr>
                @if($hasProps)
                <tr id="props-{{ $log->id }}" class="hidden bg-gray-50/60 dark:bg-gray-800/30">
                    <td colspan="6" class="px-6 py-4">
                        @php $props = $log->properties; @endphp

                        {{-- Created details (flat key-value) --}}
                        @if(!empty($props['details']))
                        <div class="mb-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Details</p>
                            <div class="flex flex-wrap gap-x-6 gap-y-1.5">
                                @foreach($props['details'] as $label => $value)
                                <div class="text-xs">
                                    <span class="text-gray-400">{{ $label }}:</span>
                                    <span class="text-gray-700 dark:text-gray-300 font-medium ms-1">{{ $value }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Header field diff (before/after for updates) --}}
                        @if(!empty($props['changed']))
                        <div class="mb-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Field Changes</p>
                            <div class="overflow-x-auto">
                                <table class="text-xs border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-100 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-500">Field</th>
                                            <th class="px-3 py-2 text-left font-semibold text-danger">Before</th>
                                            <th class="px-3 py-2 text-left font-semibold text-success">After</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach($props['changed'] as $field => $diff)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-600 dark:text-gray-400">{{ $field }}</td>
                                            <td class="px-3 py-2 text-danger">{{ $diff['from'] }}</td>
                                            <td class="px-3 py-2 text-success">{{ $diff['to'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        {{-- Line items before/after --}}
                        @php
                            $hasItems  = array_key_exists('items_before', $props)
                                      || array_key_exists('items_after', $props)
                                      || array_key_exists('items', $props);
                            $before    = $props['items_before'] ?? $props['items'] ?? [];
                            $after     = array_key_exists('items_after', $props) ? $props['items_after'] : null;
                        @endphp
                        @if($hasItems)
                        <div class="grid gap-4 {{ $after !== null ? 'md:grid-cols-2' : '' }}">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide mb-2 {{ $after !== null ? 'text-danger' : 'text-gray-500' }}">
                                    {{ $after !== null ? 'Line Items — Before' : 'Line Items' }}
                                </p>
                                @include('budget.logs._items_table', ['rows' => $before])
                            </div>
                            @if($after !== null)
                            <div>
                                <p class="text-xs font-semibold text-success uppercase tracking-wide mb-2">Line Items — After</p>
                                @include('budget.logs._items_table', ['rows' => $after])
                            </div>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                        <i class="mgc_history_line text-3xl block mb-2 opacity-40"></i>
                        No log entries found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection

@section('script')
<script>
document.querySelectorAll('.toggle-details').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.getElementById(this.dataset.target);
        if (!target) return;
        var hidden = target.classList.toggle('hidden');
        var icon   = this.querySelector('i');
        icon.className = hidden ? 'mgc_information_line' : 'mgc_close_circle_line';
    });
});
</script>
@endsection
