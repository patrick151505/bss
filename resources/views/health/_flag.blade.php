{{-- Small dot-pill for a vital-sign flag. Usage: @include('health._flag', ['flag' => $checkup->bpFlag()]) --}}
@if(!empty($flag))
@php
    [$bg, $dot] = match ($flag[1]) {
        'danger'  => ['bg-red-100 text-red-800',       'bg-red-400'],
        'warning' => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
        default   => ['bg-green-100 text-green-800',   'bg-green-400'],
    };
@endphp
<span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium whitespace-nowrap {{ $bg }}">
    <span class="w-1.5 h-1.5 inline-block rounded-full {{ $dot }}"></span>{{ $flag[0] }}
</span>
@endif
