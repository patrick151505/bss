@extends('layouts.vertical', ['title' => 'Activity Logs', 'sub_title' => 'System', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@push('skeleton')
{{-- Filter card --}}
<div class="card mb-4 p-4">
    <div class="flex flex-wrap gap-4">
        <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-36"></div>
        <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-44"></div>
        <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-40"></div>
        <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-36"></div>
        <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-36"></div>
        <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-24"></div>
    </div>
</div>
@include('partials._skeleton_table', ['rows' => 12, 'cols' => 6, 'hasBtn' => false])
@endpush

@section('content')

{{-- Page Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <h4 class="text-slate-900 dark:text-slate-100 text-lg font-semibold">
        <i class="mgc_history_line me-2 text-primary"></i>Activity Logs
    </h4>
    <span class="text-sm text-gray-400">{{ number_format($logs->total()) }} total entries</span>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title"><i class="mgc_filter_line me-2"></i>Filter</h5>
    </div>
    <form method="GET" action="{{ route('activity-logs.index') }}">
        <div class="flex flex-wrap items-end gap-4 p-6">

            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Action</label>
                <select name="action" class="form-select w-40">
                    <option value="">All Actions</option>
                    <option value="created"      {{ ($filters['action'] ?? '') === 'created'      ? 'selected' : '' }}>Created</option>
                    <option value="updated"      {{ ($filters['action'] ?? '') === 'updated'      ? 'selected' : '' }}>Updated</option>
                    <option value="deleted"      {{ ($filters['action'] ?? '') === 'deleted'      ? 'selected' : '' }}>Deleted</option>
                    <option value="exported"     {{ ($filters['action'] ?? '') === 'exported'     ? 'selected' : '' }}>Exported</option>
                    <option value="tags_updated" {{ ($filters['action'] ?? '') === 'tags_updated' ? 'selected' : '' }}>Tags Updated</option>
                </select>
            </div>

            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Module</label>
                <select name="subject_type" class="form-select w-48">
                    <option value="">All Modules</option>
                    <optgroup label="Barangay">
                        <option value="citizen"  {{ ($filters['subject_type'] ?? '') === 'citizen'  ? 'selected' : '' }}>Citizen</option>
                        <option value="tag"      {{ ($filters['subject_type'] ?? '') === 'tag'      ? 'selected' : '' }}>Tag</option>
                        <option value="blotter"  {{ ($filters['subject_type'] ?? '') === 'blotter'  ? 'selected' : '' }}>Blotter</option>
                        <option value="official" {{ ($filters['subject_type'] ?? '') === 'official' ? 'selected' : '' }}>Official</option>
                        <option value="settings" {{ ($filters['subject_type'] ?? '') === 'settings' ? 'selected' : '' }}>Settings</option>
                        <option value="export"   {{ ($filters['subject_type'] ?? '') === 'export'   ? 'selected' : '' }}>Export</option>
                    </optgroup>
                    <optgroup label="Budget">
                        <option value="budget_transaction" {{ ($filters['subject_type'] ?? '') === 'budget_transaction' ? 'selected' : '' }}>Transaction</option>
                        <option value="budget_allocation"  {{ ($filters['subject_type'] ?? '') === 'budget_allocation'  ? 'selected' : '' }}>Allocation</option>
                        <option value="budget_category"    {{ ($filters['subject_type'] ?? '') === 'budget_category'    ? 'selected' : '' }}>Category</option>
                        <option value="fiscal_year"        {{ ($filters['subject_type'] ?? '') === 'fiscal_year'        ? 'selected' : '' }}>Fiscal Year</option>
                    </optgroup>
                </select>
            </div>

            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">User</label>
                <select name="user_id" class="form-select w-44">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                    class="form-input w-40">
            </div>

            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Date To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                    class="form-input w-40">
            </div>

            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white">
                    <i class="mgc_search_line me-1"></i> Apply
                </button>
                <a href="{{ route('activity-logs.index') }}"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    <i class="mgc_close_line me-1"></i> Reset
                </a>
            </div>

        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="card">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-44">Date / Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-36">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-28">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-36">Details</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-32">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($logs as $log)
                @php
                    [$badgeClass, $badgeIcon] = match($log->action) {
                        'created'      => ['bg-success/15 text-success',   'mgc_add_circle_line'],
                        'updated'      => ['bg-primary/15 text-primary',   'mgc_edit_line'],
                        'deleted'      => ['bg-danger/15 text-danger',     'mgc_delete_2_line'],
                        'exported'     => ['bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400', 'mgc_file_download_line'],
                        'tags_updated' => ['bg-warning/15 text-warning',   'mgc_tag_line'],
                        default        => ['bg-gray-100 text-gray-500',    'mgc_information_line'],
                    };
                    $props = $log->properties ?? [];
                @endphp
                <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 dark:odd:bg-slate-800 dark:even:bg-slate-700 dark:hover:bg-slate-600">

                    {{-- Date / Time --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $log->created_at ? $log->created_at->format('M d, Y') : '—' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $log->created_at ? $log->created_at->format('g:i A') : '' }}
                        </p>
                    </td>

                    {{-- User --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $log->user->name ?? '—' }}
                        </p>
                    </td>

                    {{-- Action Badge --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold rounded-md px-2 py-1 {{ $badgeClass }}">
                            <i class="{{ $badgeIcon }}"></i>
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>

                    {{-- Description --}}
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            @if($log->subject_type === 'citizen' && $log->subject_id)
                                <a href="{{ route('citizens.show', $log->subject_id) }}"
                                    class="text-primary hover:underline font-medium">
                                    {{ \App\Models\Setting::instance()->formatCitizenId($log->subject_id) }}
                                </a>
                                —
                            @endif
                            {{ $log->description }}
                        </p>
                    </td>

                    {{-- Details from properties --}}
                    <td class="px-4 py-3 max-w-xs">
                        @if(($log->action === 'tags_updated') && isset($props['before'], $props['after']))
                            {{-- Before/after tag list --}}
                            <div class="space-y-1 text-xs">
                                <div class="flex items-start gap-1">
                                    <span class="text-danger shrink-0 mt-0.5">−</span>
                                    <span class="text-gray-500 line-through">
                                        {{ implode(', ', $props['before']) ?: 'none' }}
                                    </span>
                                </div>
                                <div class="flex items-start gap-1">
                                    <span class="text-success shrink-0 mt-0.5">+</span>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ implode(', ', $props['after']) ?: 'none' }}
                                    </span>
                                </div>
                            </div>
                        @elseif($log->action === 'updated' && isset($props['before'], $props['after']))
                            {{-- Tag updated: before/after full object --}}
                            <div class="space-y-1 text-xs">
                                @foreach($props['before'] as $field => $oldVal)
                                @php $newVal = $props['after'][$field] ?? null; @endphp
                                @if($oldVal !== $newVal)
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-300 capitalize">{{ $field }}</span>:
                                    <span class="text-danger/80 line-through">{{ Str::limit((string)$oldVal, 25) ?: 'empty' }}</span>
                                    <span class="text-gray-400 mx-0.5">→</span>
                                    <span class="text-success/90">{{ Str::limit((string)$newVal, 25) ?: 'empty' }}</span>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        @elseif($log->action === 'updated' && !empty($props['changed']))
                            @php
                                $changed  = $props['changed'];
                                $isKeyed  = is_array(reset($changed));
                                $preview  = array_slice($changed, 0, 3, true);
                                $overflow = count($changed) - 3;
                            @endphp
                            <div class="space-y-1">
                                @if($isKeyed)
                                    @foreach($preview as $field => $diff)
                                        <div class="text-xs">
                                            <span class="font-medium text-gray-600 dark:text-gray-300 capitalize">{{ $field }}</span>:
                                            <span class="text-danger/80 line-through">{{ Str::limit($diff['from'], 30) }}</span>
                                            <span class="text-gray-400 mx-0.5">→</span>
                                            <span class="text-success/90">{{ Str::limit($diff['to'], 30) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-xs text-gray-500">
                                        {{ implode(', ', array_slice((array) $changed, 0, 3)) }}
                                    </p>
                                @endif
                                @if($overflow > 0)
                                    <p class="text-xs text-gray-400">+{{ $overflow }} more field(s)</p>
                                @endif
                            </div>
                        @elseif($log->action === 'created' && $log->subject_type === 'tag' && !empty($props['name']))
                            {{-- New tag created --}}
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                                      style="background:{{ $props['color'] ?? '#6366f1' }}">{{ $props['name'] }}</span>
                            </div>
                            @if(!empty($props['description']))
                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($props['description'], 50) }}</p>
                            @endif
                        @elseif($log->action === 'exported')
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $props['record_count'] ?? '?' }} records
                                • {{ strtoupper($props['format'] ?? '') }}
                                @isset($props['fields_selected'])
                                    • {{ $props['fields_selected'] }} fields
                                @endisset
                                @isset($props['month'])
                                    • {{ \Carbon\Carbon::create()->month($props['month'])->format('F') }}
                                @endisset
                            </p>
                            @if(!empty($props['filters']))
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ count($props['filters']) }} filter(s) applied
                                </p>
                            @endif
                        @elseif($log->action === 'created' && !empty($props['qrcode']))
                            <p class="text-xs text-gray-400 font-mono">{{ $props['qrcode'] }}</p>
                        @endif
                    </td>

                    {{-- IP --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</span>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-2 text-gray-400">
                            <i class="mgc_history_line text-5xl"></i>
                            <p class="text-sm font-medium">No activity logs found</p>
                            <p class="text-xs">Logs will appear here as actions are performed.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
            &nbsp;·&nbsp; {{ number_format($logs->total()) }} entries
        </p>
        <div>
            {{ $logs->appends(request()->query())->links('vendor.pagination.konrix') }}
        </div>
    </div>
    @endif
</div>

@endsection
