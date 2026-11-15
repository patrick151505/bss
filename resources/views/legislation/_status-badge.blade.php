{{-- Dot-pill status badge for an ordinance/resolution. Usage: @include('legislation._status-badge', ['measure' => $l]) --}}
@php
    [$bg, $text, $dot] = match ($measure->status) {
        'first_reading', 'second_reading', 'third_reading'
                    => ['bg-primary/25',                        'text-sky-800 dark:text-sky-200',       'bg-primary'],
        'approved'  => ['bg-yellow-100 dark:bg-yellow-500/20',  'text-yellow-800 dark:text-yellow-200', 'bg-yellow-400'],
        'effective' => ['bg-green-100 dark:bg-green-500/20',    'text-green-800 dark:text-green-200',   'bg-green-400'],
        'vetoed', 'repealed'
                    => ['bg-red-100 dark:bg-red-500/20',        'text-red-800 dark:text-red-200',       'bg-red-400'],
        default     => ['bg-gray-100 dark:bg-gray-700',         'text-gray-700 dark:text-gray-200',     'bg-gray-400'],
    };
@endphp
<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium whitespace-nowrap {{ $bg }} {{ $text }}">
    <span class="w-1.5 h-1.5 inline-block rounded-full {{ $dot }}"></span>{{ $measure->statusLabel() }}
</span>
