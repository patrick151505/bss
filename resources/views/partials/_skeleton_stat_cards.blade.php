{{--
    Stat cards skeleton.
      $count - number of cards (default 4)
--}}
@php $count = $count ?? 4; @endphp

<div class="grid grid-cols-2 lg:grid-cols-{{ $count }} gap-4 mb-6">
    @for($i = 0; $i < $count; $i++)
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0"></div>
        <div class="flex-1 space-y-2">
            <div class="h-3 bg-gray-200 rounded dark:bg-gray-700 w-3/4"></div>
            <div class="h-5 bg-gray-200 rounded dark:bg-gray-700 w-1/2"></div>
        </div>
    </div>
    @endfor
</div>
