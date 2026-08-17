@php $idSetting = \App\Models\Setting::instance(); @endphp

{{-- Table --}}
<div class="overflow-x-auto">
    <div class="min-w-full inline-block align-middle">
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Household</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Citizen</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Age</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gender</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Address / Zone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Civil Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tags</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Flags</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($citizens as $index => $citizen)
                    <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 dark:odd:bg-slate-800 dark:even:bg-slate-700 dark:hover:bg-slate-600">

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
                                @if($citizen->profile)
                                    <img src="{{ asset('storage/' . $citizen->profile) }}"
                                        class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-600"
                                        alt="{{ $citizen->fname }}">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center text-primary font-semibold text-sm">
                                        {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $citizen->lname }}, {{ $citizen->fname }}
                                        @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                                        @if($citizen->suffix) <span class="text-gray-400">{{ $citizen->suffix }}</span> @endif
                                    </p>
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
                                <span class="text-gray-400">{{ $citizen->complete_address ?? '—' }}</span>
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

                        {{-- Actions --}}
                        <td class="px-4 py-3 whitespace-nowrap text-end">
                            <div class="flex justify-end items-center gap-1">
                                <a href="{{ route('citizens.show', $citizen->id) }}"
                                    class="p-1.5 rounded text-gray-500 hover:text-primary hover:bg-primary/10 transition-colors"
                                    title="View">
                                    <i class="mgc_eye_line text-base"></i>
                                </a>
                                <a href="{{ route('citizens.edit', $citizen->id) }}"
                                    class="p-1.5 rounded text-gray-500 hover:text-warning hover:bg-warning/10 transition-colors"
                                    title="Edit">
                                    <i class="mgc_edit_line text-base"></i>
                                </a>
                                <button type="button"
                                    class="p-1.5 rounded text-gray-500 hover:text-danger hover:bg-danger/10 transition-colors btn-delete"
                                    data-id="{{ $citizen->id }}"
                                    data-name="{{ $citizen->lname }}, {{ $citizen->fname }}"
                                    title="Delete">
                                    <i class="mgc_delete_line text-base"></i>
                                </button>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-16 text-center">
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
