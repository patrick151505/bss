{{--
    Table skeleton. Params:
      $rows   - number of data rows (default 8)
      $cols   - number of columns (default 5)
      $hasBtn - show top-right button placeholder (default true)
--}}
@php
    $rows   = $rows   ?? 8;
    $cols   = $cols   ?? 5;
    $hasBtn = $hasBtn ?? true;
@endphp

{{-- Page header bar --}}
<div class="flex items-center justify-between mb-6">
    <div class="h-5 bg-gray-200 rounded-md dark:bg-gray-700 w-48"></div>
    @if($hasBtn)
    <div class="h-8 bg-gray-200 rounded-md dark:bg-gray-700 w-32"></div>
    @endif
</div>

{{-- Card / table --}}
<div class="card">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="h-4 bg-gray-200 rounded-md dark:bg-gray-700 w-1/3"></div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    @for($c = 0; $c < $cols; $c++)
                    <th class="px-4 py-3">
                        <div class="h-3 bg-gray-200 rounded dark:bg-gray-700 {{ $c === 0 ? 'w-24' : ($c === $cols - 1 ? 'w-16 ms-auto' : 'w-20') }}"></div>
                    </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @for($r = 0; $r < $rows; $r++)
                <tr>
                    @for($c = 0; $c < $cols; $c++)
                    <td class="px-4 py-3">
                        <div class="h-4 bg-gray-200 rounded-md dark:bg-gray-700"
                            style="width: {{ [70, 55, 80, 45, 60, 75, 50][$c % 7] }}%"></div>
                    </td>
                    @endfor
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
