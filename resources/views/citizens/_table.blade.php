@php
    $idSetting = \App\Models\Setting::instance();

    // Sort state — set by the controller, absent on the AJAX partial's first
    // render, so default to "unsorted".
    $sort = $sort ?? null;
    $dir  = $dir  ?? 'desc';

    // "table" (default) or "grid" — sent by the view toggle with every request.
    $view = request('view') === 'grid' ? 'grid' : 'table';

    // Clicking a column sorts it in its natural first direction — dates
    // newest-first, names A→Z — then toggles.
    $sortLink = function (string $column) use ($sort, $dir) {
        $first = $column === 'name' ? 'asc' : 'desc';
        $other = $first === 'asc' ? 'desc' : 'asc';
        $next  = ($sort === $column && $dir === $first) ? $other : $first;
        return request()->fullUrlWithQuery(['sort' => $column, 'dir' => $next, 'page' => 1]);
    };

    $sortIcon = function (string $column) use ($sort, $dir) {
        if ($sort !== $column) {
            return 'mgc_selector_vertical_line text-gray-400 dark:text-gray-400';
        }
        return $dir === 'asc'
            ? 'mgc_sort_ascending_line text-primary'
            : 'mgc_sort_descending_line text-primary';
    };
@endphp

<style>
    /* Rows and grid cards are clickable to reveal the docked action bar. */
    tbody tr.selectable,
    .citizen-card.selectable { cursor: pointer; }
    tbody tr.row-selected,
    tbody tr.row-selected:hover { background-color: rgba(114,124,245,.12) !important; }
    .citizen-card.row-selected { box-shadow: 0 0 0 2px #727cf5; }
</style>

@if($view === 'grid')
{{-- Grid --}}
<div class="p-4">
    {{-- Sort bar — the table sorts from its headers; the grid needs its own --}}
    <div class="flex flex-wrap items-center gap-2 mb-4 text-xs">
        <span class="text-gray-500 dark:text-gray-400 font-medium uppercase">Sort:</span>
        @foreach(['name' => 'Name', 'date_created' => 'Registered', 'date_last_updated' => 'Last Updated'] as $col => $label)
            <a href="{{ $sortLink($col) }}" data-sort="{{ $col }}"
               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border transition-colors
                      {{ $sort === $col ? 'border-primary/40 bg-primary/10 text-primary' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-primary' }}">
                {{ $label }} <i class="{{ $sortIcon($col) }} text-sm leading-none"></i>
            </a>
        @endforeach
    </div>

    @if($citizens->isEmpty())
        <div class="flex flex-col items-center gap-2 text-gray-400 py-16">
            <i class="mgc_user_search_line text-5xl"></i>
            <p class="text-sm font-medium">No citizens found</p>
            <p class="text-xs">Try adjusting your filters.</p>
        </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($citizens as $citizen)
        @php
            $statusColor = match($citizen->approval_status) {
                1 => 'bg-success/15 text-success',
                2 => 'bg-warning/15 text-warning',
                default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            };
        @endphp
        {{-- Same data-* as a table row, so selection + action bar work unchanged --}}
        <div class="citizen-row citizen-card selectable flex flex-col rounded-xl border bg-white dark:bg-gray-800 transition hover:shadow-md
                    {{ $citizen->activeHold ? 'border-danger/40' : 'border-gray-200 dark:border-gray-700' }}"
             data-id="{{ $citizen->id }}"
             data-name="{{ $citizen->lname }}, {{ $citizen->fname }}"
             data-code="{{ $idSetting->formatCitizenId($citizen->id) }}"
             data-photo="{{ $citizen->profile_url }}">

            @if($citizen->activeHold)
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-t-xl bg-danger/10 text-danger text-xs font-semibold"
                     title="{{ $citizen->activeHold->reason }}">
                    <i class="mgc_lock_line"></i>
                    <span class="truncate">On Hold — {{ $citizen->activeHold->reason }}</span>
                </div>
            @endif

            <div class="p-4 flex flex-col items-center text-center gap-2 flex-1">
                <div class="relative">
                    @if($citizen->profile)
                        <img src="{{ $citizen->profile_url }}"
                            class="w-16 h-16 rounded-full object-cover border-2 shadow {{ $citizen->activeHold ? 'border-danger ring-2 ring-danger/40' : 'border-white dark:border-gray-700' }}"
                            alt="{{ $citizen->fname }}">
                    @else
                        <div class="w-16 h-16 rounded-full flex items-center justify-center font-bold text-lg shadow {{ $citizen->activeHold ? 'bg-danger/15 text-danger ring-2 ring-danger/40' : 'bg-primary/20 text-primary' }}">
                            {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                        </div>
                    @endif
                    @if($citizen->activeHold)
                        <span class="absolute -bottom-0.5 -end-0.5 w-6 h-6 rounded-full bg-danger text-white flex items-center justify-center ring-2 ring-white dark:ring-gray-800"
                              title="On hold">
                            <i class="mgc_lock_fill text-xs leading-none"></i>
                        </span>
                    @endif
                </div>

                <div class="min-w-0 w-full">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate" title="{{ $citizen->lname }}, {{ $citizen->fname }}">
                        {{ $citizen->lname }}, {{ $citizen->fname }}
                        @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                        @if($citizen->suffix) <span class="text-gray-400">{{ $citizen->suffix }}</span> @endif
                    </p>
                    <p class="text-xs font-mono text-gray-400">{{ $idSetting->formatCitizenId($citizen->id) }}</p>
                </div>

                <p class="text-xs text-gray-600 dark:text-gray-300">
                    @if($citizen->bday){{ $citizen->age }} yrs @else — @endif
                    ·
                    @if($citizen->gender == 1)
                        <span class="text-blue-600 dark:text-blue-400"><i class="mgc_male_line"></i> Male</span>
                    @else
                        <span class="text-pink-600 dark:text-pink-400"><i class="mgc_female_line"></i> Female</span>
                    @endif
                </p>

                <p class="text-xs text-gray-500 dark:text-gray-400 truncate w-full">
                    <i class="mgc_location_line"></i>
                    {{ $citizen->addressZone->description ?? ($citizen->complete_address ?: '—') }}
                </p>

                <div class="flex flex-wrap justify-center gap-1">
                    @if($citizen->voters)
                        <span class="text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 rounded px-1.5 py-0.5">Voter</span>
                    @endif
                    @if($citizen->age >= 60)
                        <span class="text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 rounded px-1.5 py-0.5">Senior</span>
                    @endif
                    @if($citizen->is_pwd)
                        <span class="text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded px-1.5 py-0.5">PWD</span>
                    @endif
                    @if($citizen->is_soloparents)
                        <span class="text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 rounded px-1.5 py-0.5">Solo Parent</span>
                    @endif
                    @foreach($citizen->tags as $tag)
                        <span class="text-xs font-semibold text-white rounded px-1.5 py-0.5" style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 px-3 py-2 border-t border-gray-100 dark:border-gray-700">
                <span class="text-xs font-medium rounded-md px-2 py-0.5 {{ $statusColor }}">
                    {{ $citizen->approvalStatus->description ?? '—' }}
                </span>
                <div class="flex items-center gap-1">
                    <a href="{{ route('citizens.show', $citizen->id) }}"
                        class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-primary hover:bg-primary/10 transition" title="View profile">
                        <i class="mgc_eye_line text-base"></i>
                    </a>
                    @can('citizens.edit')
                    <a href="{{ route('citizens.edit', $citizen->id) }}"
                        class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-warning hover:bg-warning/10 transition" title="Edit">
                        <i class="mgc_edit_line text-base"></i>
                    </a>
                    @endcan
                    @can('citizens.archive')
                    <button type="button"
                        class="btn-delete p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-warning hover:bg-warning/10 transition"
                        data-id="{{ $citizen->id }}"
                        data-name="{{ $citizen->lname }}, {{ $citizen->fname }}"
                        title="Set inactive">
                        <i class="mgc_archive_line text-base"></i>
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@else
{{-- Table --}}
<div class="overflow-x-auto">
    <div class="min-w-full inline-block align-middle">
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Household</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            <a href="{{ $sortLink('name') }}" data-sort="name"
                               class="inline-flex items-center gap-1 hover:text-primary transition-colors"
                               title="Sort by name (surname, first name)">
                                Citizen <i class="{{ $sortIcon('name') }} text-base leading-none"></i>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Age</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gender</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Address / Zone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Civil Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tags</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Flags</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            <a href="{{ $sortLink('date_created') }}" data-sort="date_created"
                               class="inline-flex items-center gap-1 hover:text-primary transition-colors"
                               title="Sort by date registered">
                                Registered <i class="{{ $sortIcon('date_created') }} text-base leading-none"></i>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            <a href="{{ $sortLink('date_last_updated') }}" data-sort="date_last_updated"
                               class="inline-flex items-center gap-1 hover:text-primary transition-colors"
                               title="Sort by date last updated">
                                Last Updated <i class="{{ $sortIcon('date_last_updated') }} text-base leading-none"></i>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($citizens as $index => $citizen)
                    <tr class="citizen-row selectable group odd:bg-white even:bg-gray-50 hover:bg-gray-100 dark:odd:bg-slate-800 dark:even:bg-slate-700 dark:hover:bg-slate-600"
                        data-id="{{ $citizen->id }}"
                        data-name="{{ $citizen->lname }}, {{ $citizen->fname }}"
                        data-code="{{ $idSetting->formatCitizenId($citizen->id) }}"
                        data-photo="{{ $citizen->profile_url }}">

                        {{-- Row Number --}}
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $citizens->firstItem() + $index }}
                        </td>

                        {{-- Household --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($citizen->family?->household)
                                @php
                                    $hh = $citizen->family->household;
                                    $hhRoleColor = match((int)$citizen->isHead) {
                                        2 => ['icon' => 'text-success'],
                                        1 => ['icon' => 'text-primary'],
                                        default => ['icon' => 'text-warning'],
                                    };
                                    $hhRoleIcon = match((int)$citizen->isHead) {
                                        2 => 'mgc_home_3_fill',
                                        1 => 'mgc_group_2_fill',
                                        default => 'mgc_user_3_fill',
                                    };
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <i class="{{ $hhRoleIcon }} text-sm {{ $hhRoleColor['icon'] }} shrink-0"></i>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 truncate max-w-[140px]">{{ $hh->formatted_id }}</p>
                                        <p class="text-[10px] text-gray-400 truncate max-w-[140px]">{{ $hh->full_address }}</p>
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>

                        {{-- Citizen Name + Photo --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                {{-- Lock badge on the photo when the citizen is on hold --}}
                                <div class="relative shrink-0">
                                    @if($citizen->profile)
                                        <img src="{{ $citizen->profile_url }}"
                                            class="w-9 h-9 rounded-full object-cover border {{ $citizen->activeHold ? 'border-danger ring-2 ring-danger/40' : 'border-gray-200 dark:border-gray-600' }}"
                                            alt="{{ $citizen->fname }}">
                                    @else
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-semibold text-sm {{ $citizen->activeHold ? 'bg-danger/15 text-danger ring-2 ring-danger/40' : 'bg-primary/20 text-primary' }}">
                                            {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                                        </div>
                                    @endif
                                    @if($citizen->activeHold)
                                        <span class="absolute -bottom-1 -end-1 w-4 h-4 rounded-full bg-danger text-white flex items-center justify-center ring-2 ring-white dark:ring-gray-800"
                                              title="On hold: {{ $citizen->activeHold->reason }}">
                                            <i class="mgc_lock_fill text-[9px] leading-none"></i>
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $citizen->lname }}, {{ $citizen->fname }}
                                        @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                                        @if($citizen->suffix) <span class="text-gray-400">{{ $citizen->suffix }}</span> @endif
                                    </p>
                                    @if($citizen->activeHold)
                                        <p class="text-xs font-semibold text-danger flex items-center gap-1" title="{{ $citizen->activeHold->reason }}">
                                            <i class="mgc_lock_line"></i> On Hold
                                        </p>
                                    @endif
                                    <p class="text-xs font-mono text-gray-400">{{ $idSetting->formatCitizenId($citizen->id) }}</p>
                                    @if($citizen->contact)
                                        <p class="text-xs text-gray-400">{{ $citizen->contact }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Age --}}
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            @if($citizen->bday)
                                {{ $citizen->age }}
                                <span class="text-xs text-gray-400 block">{{ $citizen->bday->format('M d, Y') }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Gender --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($citizen->gender == 1)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-400 rounded-full px-2 py-0.5">
                                    <i class="mgc_male_line"></i> Male
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-pink-600 bg-pink-50 dark:bg-pink-900/30 dark:text-pink-400 rounded-full px-2 py-0.5">
                                    <i class="mgc_female_line"></i> Female
                                </span>
                            @endif
                        </td>

                        {{-- Address --}}
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            @if($citizen->addressZone)
                                <span>{{ $citizen->addressZone->description }}</span>
                                @if($citizen->addressZone->is_subd && ($citizen->blk || $citizen->lot))
                                    <span class="text-xs text-gray-400 block">
                                        @if($citizen->blk) Blk {{ $citizen->blk }} @endif
                                        @if($citizen->lot) Lot {{ $citizen->lot }} @endif
                                        @if($citizen->phase) Ph {{ $citizen->phase }} @endif
                                    </span>
                                @endif
                                @if($citizen->street)
                                    <span class="text-xs text-gray-400 block">{{ $citizen->street }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">{{ $citizen->full_address ?: '—' }}</span>
                            @endif
                        </td>

                        {{-- Civil Status --}}
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->civilStatus->description ?? '—' }}
                        </td>

                        {{-- Tags --}}
                        <td class="px-4 py-3">
                            @if($citizen->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach($citizen->tags as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white whitespace-nowrap"
                                      style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Flags --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @if($citizen->activeHold)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 rounded px-1.5 py-0.5"
                                          title="On hold: {{ $citizen->activeHold->reason }}">
                                        <i class="mgc_lock_line"></i>On Hold
                                    </span>
                                @endif
                                @if($citizen->is_pwd)
                                    <span class="text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded px-1.5 py-0.5">PWD</span>
                                @endif
                                @if($citizen->voters)
                                    <span class="text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 rounded px-1.5 py-0.5">Voter</span>
                                @endif
                                @if($citizen->is_soloparents)
                                    <span class="text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 rounded px-1.5 py-0.5">Solo Parent</span>
                                @endif
                                @if($citizen->age >= 60)
                                    <span class="text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 rounded px-1.5 py-0.5">Senior</span>
                                @endif
                            </div>
                        </td>

                        {{-- Approval Status --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $statusColor = match($citizen->approval_status) {
                                    1 => 'bg-success/15 text-success',
                                    2 => 'bg-warning/15 text-warning',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                };
                            @endphp
                            <span class="text-xs font-medium rounded-md px-2 py-1 {{ $statusColor }}">
                                {{ $citizen->approvalStatus->description ?? '—' }}
                            </span>
                            @if(!$citizen->is_active)
                                <span class="text-xs font-medium bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 rounded-md px-2 py-1 ms-1">Inactive</span>
                            @endif
                        </td>

                        {{-- Registered --}}
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                            @if($citizen->date_created)
                                {{ $citizen->date_created->format('M d, Y') }}
                                <span class="block text-xs text-gray-400">{{ $citizen->date_created->format('g:i A') }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Last Updated --}}
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                            @if($citizen->date_last_updated)
                                {{ $citizen->date_last_updated->format('M d, Y') }}
                                <span class="block text-xs text-gray-400">{{ $citizen->date_last_updated->diffForHumans() }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Actions — pinned to the right edge so they are
                             reachable without scrolling the table sideways. --}}
                        <td class="px-4 py-3 whitespace-nowrap text-end">
                            <div class="flex justify-end items-center gap-1">
                                <a href="{{ route('citizens.show', $citizen->id) }}"
                                    class="p-1.5 rounded text-gray-500 hover:text-primary hover:bg-primary/10 transition"
                                    title="View profile">
                                    <i class="mgc_eye_line text-base"></i>
                                </a>
                                @can('citizens.edit')
                                <a href="{{ route('citizens.edit', $citizen->id) }}"
                                    class="p-1.5 rounded text-gray-500 hover:text-warning hover:bg-warning/10 transition"
                                    title="Edit">
                                    <i class="mgc_edit_line text-base"></i>
                                </a>
                                @endcan
                                @can('citizens.archive')
                                <button type="button"
                                    class="btn-delete p-1.5 rounded text-gray-500 hover:text-warning hover:bg-warning/10 transition"
                                    data-id="{{ $citizen->id }}"
                                    data-name="{{ $citizen->lname }}, {{ $citizen->fname }}"
                                    title="Set inactive">
                                    <i class="mgc_archive_line text-base"></i>
                                </button>
                                @endcan
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400">
                                <i class="mgc_user_search_line text-5xl"></i>
                                <p class="text-sm font-medium">No citizens found</p>
                                <p class="text-xs">Try adjusting your filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Pagination --}}
@if($citizens->hasPages())
<div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Page {{ $citizens->currentPage() }} of {{ $citizens->lastPage() }}
    </p>
    <div id="pagination-links">
        {{ $citizens->appends(request()->query())->links('vendor.pagination.konrix') }}
    </div>
</div>
@endif
