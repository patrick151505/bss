{{--
    Form card skeleton.
      $fields - number of input rows (default 6)
--}}
@php $fields = $fields ?? 6; @endphp

<div class="flex items-center justify-between mb-6">
    <div class="h-5 bg-gray-200 rounded-md dark:bg-gray-700 w-40"></div>
</div>

<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 lg:col-span-8">
        <div class="card p-6 space-y-5">
            <div class="h-4 bg-gray-200 rounded dark:bg-gray-700 w-32 mb-2"></div>
            @for($i = 0; $i < $fields; $i++)
            <div>
                <div class="h-3 bg-gray-200 rounded dark:bg-gray-700 w-24 mb-2"></div>
                <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-full"></div>
            </div>
            @endfor
        </div>
    </div>
    <div class="col-span-12 lg:col-span-4">
        <div class="card p-5 space-y-3">
            <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-full"></div>
            <div class="h-9 bg-gray-200 rounded-lg dark:bg-gray-700 w-full"></div>
        </div>
    </div>
</div>
