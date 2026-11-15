@php
    $totalPages = (int) ceil($total / $limit);
    $from       = ($page - 1) * $limit + 1;
    $to         = min($page * $limit, $total);
    $selectFn   = $callback ?? 'selectCitizen';
    $isWizard   = ($selectFn === 'selectCitizenForWizard');

    // Show a WINDOW of pages around the current one. Looping over every page
    // rendered ~2,500 buttons for the full citizen list, which overflowed the
    // modal and bloated every response.
    $window = 2;                                   // pages either side of current
    $start  = max(1, $page - $window);
    $end    = min($totalPages, $page + $window);
    // Keep the strip a stable width when near either end.
    if ($end - $start < $window * 2) {
        $start = max(1, min($start, $end - $window * 2));
        $end   = min($totalPages, max($end, $start + $window * 2));
    }
@endphp

@if($citizens->isEmpty())
    <div class="py-12 text-center text-gray-400">
        <i class="mgc_user_3_line text-4xl mb-2 block"></i>
        <p class="text-sm">No citizens found{{ $search ? ' for "' . $search . '"' : '' }}.</p>
    </div>
@else
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach($citizens as $citizen)
        @php
            $assigned = !is_null($citizen->familyId);
            $photo    = $citizen->profile
                ? asset(str_replace('public/', 'storage/', $citizen->profile))
                : null;
            $address  = $citizen->addressZone?->description ?? '—';
        @endphp
        <div class="flex items-center gap-3 px-5 py-3 {{ $assigned ? 'opacity-50' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer' }} transition-colors"
             onclick="{{ $selectFn }}({{ $citizen->id }}, '{{ addslashes($citizen->full_name) }}', {{ $photo ? "'$photo'" : 'null' }}, {{ $assigned ? 'true' : 'false' }})">

            <div class="w-9 h-9 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center shrink-0">
                @if($photo)
                    <img src="{{ $photo }}" class="w-full h-full object-cover">
                @else
                    <span class="text-sm font-bold text-primary">{{ strtoupper(substr($citizen->fname, 0, 1)) }}</span>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $citizen->full_name }}</p>
                <p class="text-xs text-gray-400 truncate">
                    {{ $citizen->bday ? $citizen->bday->format('M d, Y') . ' · Age ' . $citizen->age : '—' }}
                    · {{ $citizen->gender === 1 ? 'Male' : 'Female' }}
                    · {{ $address }}
                </p>
            </div>

            @if($assigned)
                <span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 bg-warning/10 text-warning shrink-0">
                    <i class="mgc_home_3_line"></i> Assigned
                </span>
            @else
                <i class="mgc_right_line text-gray-300 shrink-0"></i>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($totalPages > 1)
    @php
        // One place to build the click handler, so the wizard and the picker
        // stay in step instead of repeating the @if on every button.
        $go = fn ($p) => $isWizard
            ? "loadStep1Citizens(document.getElementById('step1-search').value, {$p})"
            : "loadCitizenPicker({$p})";
        $btn     = 'min-w-[28px] h-7 px-1.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 disabled:pointer-events-none';
        $btnOn   = 'min-w-[28px] h-7 px-1.5 rounded text-xs font-medium bg-primary text-white';
    @endphp
    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3 flex-wrap">
        <p class="text-xs text-gray-400 whitespace-nowrap">
            {{ number_format($from) }}–{{ number_format($to) }} of {{ number_format($total) }}
        </p>
        <div class="flex items-center gap-1">
            {{-- Prev --}}
            <button type="button" class="{{ $btn }}" {{ $page <= 1 ? 'disabled' : '' }}
                    onclick="{{ $go($page - 1) }}" title="Previous">
                <i class="mgc_left_line"></i>
            </button>

            {{-- Jump to first when it is outside the window --}}
            @if($start > 1)
            <button type="button" class="{{ $btn }}" onclick="{{ $go(1) }}">1</button>
            @if($start > 2)<span class="text-xs text-gray-400 px-0.5">…</span>@endif
            @endif

            @for($i = $start; $i <= $end; $i++)
            <button type="button" onclick="{{ $go($i) }}"
                    class="{{ $i === $page ? $btnOn : $btn }}">{{ $i }}</button>
            @endfor

            {{-- Jump to last when it is outside the window --}}
            @if($end < $totalPages)
            @if($end < $totalPages - 1)<span class="text-xs text-gray-400 px-0.5">…</span>@endif
            <button type="button" class="{{ $btn }}" onclick="{{ $go($totalPages) }}">{{ number_format($totalPages) }}</button>
            @endif

            {{-- Next --}}
            <button type="button" class="{{ $btn }}" {{ $page >= $totalPages ? 'disabled' : '' }}
                    onclick="{{ $go($page + 1) }}" title="Next">
                <i class="mgc_right_line"></i>
            </button>
        </div>
    </div>
    @endif
@endif
