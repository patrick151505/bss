@extends('layouts.vertical', [
    'title'         => 'Archive',
    'sub_title'     => 'Citizen Management',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Archived citizens — restore one to return it to the active list',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

<div class="card">
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h4 class="card-title">
                Archived Citizens
                <span class="ms-2 text-sm font-normal text-gray-400">{{ number_format($citizens->total()) }} total</span>
            </h4>

            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('citizens.inactive') }}" class="flex items-center gap-2">
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}"
                               class="form-input ps-9 w-56" placeholder="Search name…" autocomplete="off">
                        <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button type="submit" class="btn bg-primary text-white">Search</button>
                    @if($search)
                    <a href="{{ route('citizens.inactive') }}" class="btn bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20">
                        <i class="mgc_close_line"></i>
                    </a>
                    @endif
                </form>

                <a href="{{ route('citizens.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_arrow_left_line"></i> Active Citizens
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Citizen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Age / Birthday</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Archived</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($citizens as $citizen)
                <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if($citizen->profile)
                                <img src="{{ $citizen->profile_url }}"
                                     class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-600 grayscale">
                            @else
                                <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 font-semibold text-sm">
                                    {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $citizen->lname }}, {{ $citizen->fname }}
                                    @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                                </p>
                                <p class="text-xs font-mono text-gray-400">
                                    {{ \App\Models\Setting::instance()->formatCitizenId($citizen->id) }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                        @if($citizen->bday)
                            {{ $citizen->age }}
                            <span class="block text-xs text-gray-400">{{ $citizen->bday->format('M d, Y') }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300 max-w-xs">
                        @if($citizen->archive_reason)
                            {{ $citizen->archive_reason }}
                        @else
                            <span class="text-gray-400 italic text-xs">No reason recorded</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        @php $when = $citizen->archived_at ?? $citizen->date_last_updated; @endphp
                        {{ $when?->format('M d, Y') ?? '—' }}
                        @if($when)
                        <span class="block text-xs text-gray-400">{{ $when->diffForHumans() }}</span>
                        @endif
                        @if($citizen->archivedBy)
                        <span class="block text-xs text-gray-400">by {{ $citizen->archivedBy->name }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        {{-- No profile link: archived citizens are hidden
                             everywhere else, so restore them first to view. --}}
                        @can('citizens.restore')
                        <form id="restore-{{ $citizen->id }}" method="POST"
                              action="{{ route('citizens.restore', $citizen->id) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="button"
                                    class="btn btn-sm bg-success/25 text-success hover:bg-success hover:text-white btn-restore"
                                    data-id="{{ $citizen->id }}"
                                    data-name="{{ $citizen->lname }}, {{ $citizen->fname }}">
                                <i class="mgc_refresh_1_line"></i> Restore
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-16 text-gray-400">
                        <i class="mgc_inbox_line text-4xl mb-2 block opacity-30"></i>
                        <p class="text-sm font-medium">Archive is empty</p>
                        <p class="text-xs">
                            {{ $search ? 'Nothing matched "' . $search . '".' : 'Archived citizens will appear here.' }}
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($citizens->hasPages())
    <div class="p-4 border-t dark:border-gray-700">
        {{ $citizens->appends(request()->query())->links('vendor.pagination.konrix') }}
    </div>
    @endif
</div>

@endsection

@push('inline-scripts')
<script>
document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-restore');
    if (!btn) return;

    Swal.fire({
        title: 'Restore Citizen?',
        text: `"${btn.dataset.name}" will be returned to the active citizen list.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, restore',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById('restore-' + btn.dataset.id).submit();
    });
});
</script>
@endpush
