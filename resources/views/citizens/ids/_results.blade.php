@php $idSetting = \App\Models\Setting::instance(); @endphp
{{-- ── List view (table) ── --}}
<div id="ids-list-view" class="{{ ($idsView ?? 'list') === 'grid' ? 'hidden' : '' }} overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Citizen</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Address</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Issued By</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-32">Date Issued</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-36">Valid Until</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-20">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($ids as $record)
            @php $c = $record->citizen; $expired = $record->valid_until && $record->valid_until->isPast(); @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full shrink-0 overflow-hidden bg-primary/10 flex items-center justify-center">
                            @if($c?->profile)
                            <img src="{{ asset(str_replace('public/', 'storage/', $c->profile)) }}" class="w-full h-full object-cover" alt="">
                            @else
                            <i class="mgc_user_3_line text-primary text-sm"></i>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $c?->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ $idSetting->formatCitizenId($c?->id) }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c?->addressZone?->description ?? $c?->complete_address ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $record->generatedBy?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $record->created_at?->format('M d, Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    @if($record->valid_until)
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium {{ $expired ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        <span class="w-1.5 h-1.5 inline-block {{ $expired ? 'bg-red-400' : 'bg-green-400' }} rounded-full"></span>
                        {{ $record->valid_until->format('M d, Y') }}{{ $expired ? ' (Expired)' : '' }}
                    </span>
                    @else
                    <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <a href="{{ route('citizens.ids.print', $record->id) }}" target="_blank"
                       class="text-primary hover:text-primary/70 text-base" title="Print ID">
                        <i class="mgc_print_line"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                    <i class="mgc_card_pay_line text-3xl block mb-2"></i>
                    No IDs found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Grid view (cards) ── --}}
<div id="ids-grid-view" class="{{ ($idsView ?? 'list') === 'grid' ? '' : 'hidden' }} p-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($ids as $record)
        @php $c = $record->citizen; $expired = $record->valid_until && $record->valid_until->isPast(); @endphp
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition flex flex-col gap-3">
            {{-- Header: ID no. + validity status --}}
            <div class="flex items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-mono font-semibold text-primary bg-primary/10 rounded px-2 py-1">
                    <i class="mgc_card_line text-[11px]"></i>{{ $idSetting->formatCitizenId($c?->id) }}
                </span>
                @if($record->valid_until)
                <span class="inline-flex items-center py-1 px-2.5 rounded-full text-[11px] font-medium whitespace-nowrap {{ $expired ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ $expired ? 'Expired' : 'Active' }}
                </span>
                @endif
            </div>

            {{-- Citizen identity --}}
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0 overflow-hidden">
                    @if($c?->profile)
                    <img src="{{ asset(str_replace('public/', 'storage/', $c->profile)) }}" class="w-full h-full object-cover" alt="">
                    @else
                    <i class="mgc_user_3_line text-primary"></i>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $c?->full_name ?? '—' }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $c?->addressZone?->description ?? $c?->complete_address ?? '' }}</p>
                </div>
            </div>

            <div class="text-xs space-y-1.5 border-t border-gray-100 dark:border-gray-800 pt-2.5">
                <div class="flex justify-between gap-3">
                    <span class="text-gray-400 shrink-0">Date Issued</span>
                    <span class="text-gray-700 dark:text-gray-200 text-right">{{ $record->created_at?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-400 shrink-0">Valid Until</span>
                    <span class="text-right font-medium {{ $expired ? 'text-danger' : 'text-gray-700 dark:text-gray-200' }}">{{ $record->valid_until?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-400 shrink-0">Issued By</span>
                    <span class="text-gray-500 text-right truncate">{{ $record->generatedBy?->name ?? '—' }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1 mt-auto">
                <a href="{{ route('citizens.ids.print', $record->id) }}" target="_blank"
                   class="btn btn-sm bg-primary/10 text-primary flex-1 flex items-center justify-center gap-1">
                    <i class="mgc_print_line"></i> Print ID
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full py-12 text-center text-gray-400 text-sm">
            <i class="mgc_card_pay_line text-3xl block mb-2"></i>
            No IDs found.
        </div>
        @endforelse
    </div>
</div>

@if($ids->total() > 0)
<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
    {{-- Per-page selector + range summary --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <span>Show</span>
        <select onchange="changePerPage(this.value)"
                class="form-select form-select-sm py-1 w-auto text-sm">
            @foreach([10, 20, 50, 100] as $n)
            <option value="{{ $n }}" {{ (int) request('show', 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
            @endforeach
        </select>
        <span>·&nbsp; Showing {{ $ids->firstItem() }}–{{ $ids->lastItem() }} of {{ number_format($ids->total()) }} {{ Str::plural('record', $ids->total()) }}</span>
    </div>
    {{-- Page links --}}
    @if($ids->hasPages())
    <div>{{ $ids->onEachSide(1)->links() }}</div>
    @endif
</div>
@endif
