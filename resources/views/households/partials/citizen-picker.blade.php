@php
    $totalPages = ceil($total / $limit);
    $from       = ($page - 1) * $limit + 1;
    $to         = min($page * $limit, $total);
    $selectFn   = $callback ?? 'selectCitizen';
    $isWizard   = ($selectFn === 'selectCitizenForWizard');
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
    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <p class="text-xs text-gray-400">{{ $from }}–{{ $to }} of {{ $total }}</p>
        <div class="flex gap-1">
            @for($i = 1; $i <= $totalPages; $i++)
            <button
                @if($isWizard)
                    onclick="loadStep1Citizens(document.getElementById('step1-search').value, {{ $i }})"
                @else
                    onclick="loadCitizenPicker({{ $i }})"
                @endif
                class="w-7 h-7 rounded text-xs font-medium {{ $i === $page ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                {{ $i }}
            </button>
            @endfor
        </div>
    </div>
    @endif
@endif
