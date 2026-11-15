@extends('layouts.vertical', [
    'title'         => 'Rentals',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.items.index'),
    'tagline'       => 'Items lent out to citizens and expected back',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    @foreach([
        ['scheduled', 'Scheduled',     $stats['scheduled'], 'mgc_calendar_line',       'info'],
        ['out',       'Currently Out', $stats['out'],       'mgc_arrow_right_up_line', 'primary'],
        ['overdue',   'Overdue',       $stats['overdue'],   'mgc_alert_line',          'danger'],
        ['returned',  'Returned',      $stats['returned'],  'mgc_check_circle_line',   'success'],
    ] as [$key, $label, $count, $icon, $color])
    <a href="{{ route('inventory.rentals.index', ['status' => $key]) }}" class="card p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-{{ $color }}/10 flex items-center justify-center shrink-0">
                <i class="{{ $icon }} text-{{ $color }} text-2xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $label }}</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $count }}</p>
            </div>
        </div>
    </a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div class="flex flex-wrap items-center gap-2">
                @foreach(['active' => 'Active', 'scheduled' => 'Scheduled', 'out' => 'Out', 'overdue' => 'Overdue', 'returned' => 'Returned', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
                <a href="{{ route('inventory.rentals.index', ['status' => $key]) }}"
                   class="btn btn-sm {{ $filter === $key ? 'bg-primary text-white' : 'bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            @can('inventory_rentals.create')
            <a href="{{ route('inventory.rentals.create') }}" class="btn bg-primary text-white">
                <i class="mgc_add_line"></i> New Rental
            </a>
            @endcan
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Borrower</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Borrowed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Due</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                        <p class="font-medium">{{ $rental->citizen?->full_name ?? '—' }}</p>
                        @if($rental->purpose)
                        <p class="text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($rental->purpose, 40) }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                        @foreach($rental->items as $line)
                        <div class="text-xs">
                            {{ $line->quantity }} × {{ $line->item?->name ?? '—' }}
                            @if($rental->isOut() && $line->quantity_returned > 0)
                                <span class="text-warning">({{ $line->quantity_returned }} back)</span>
                            @endif
                        </div>
                        @endforeach
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        {{ $rental->borrowed_on?->format('M d, Y') }}
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <span class="{{ $rental->isOverdue() ? 'text-danger font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                            {{ $rental->due_on?->format('M d, Y') }}
                        </span>
                        @if($rental->isOverdue())
                        <p class="text-xs text-danger">{{ $rental->daysOverdue() }} {{ \Illuminate\Support\Str::plural('day', $rental->daysOverdue()) }} late</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($rental->isReturned())
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Returned
                            </span>
                        @elseif($rental->isCancelled())
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>Cancelled
                            </span>
                        @elseif($rental->isOverdue())
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Overdue
                            </span>
                        @elseif($rental->isScheduled())
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                <span class="w-1.5 h-1.5 inline-block bg-cyan-400 rounded-full"></span>Scheduled
                            </span>
                            @if($rental->isMissedPickup())
                            <p class="text-xs text-warning mt-0.5">Not collected</p>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-sky-800">
                                <span class="w-1.5 h-1.5 inline-block bg-sky-400 rounded-full"></span>Out
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('inventory.rentals.show', $rental) }}" class="btn btn-sm bg-primary text-white">
                            @if($rental->isScheduled())
                                <i class="mgc_arrow_right_line"></i> Release
                            @elseif($rental->isOut())
                                <i class="mgc_inbox_line"></i> Return
                            @else
                                <i class="mgc_eye_line"></i>
                            @endif
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-12 text-gray-400">
                    <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No rentals found.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rentals->hasPages())
    <div class="p-4 border-t dark:border-gray-700">{{ $rentals->links() }}</div>
    @endif
</div>

@endsection
