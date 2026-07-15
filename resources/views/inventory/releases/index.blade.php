@extends('layouts.vertical', [
    'title'         => 'Inventory Releases',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => 'Releases',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @foreach([['pending','warning','mgc_time_line','Pending'],['approved','primary','mgc_check_line','Approved'],['released','success','mgc_send_line','Released'],['rejected','danger','mgc_close_circle_line','Rejected']] as [$key,$color,$icon,$label])
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-{{ $color }}/10 flex items-center justify-center shrink-0">
            <i class="{{ $icon }} text-{{ $color }} text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats[$key]) }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="flex items-center justify-between mb-5">
    <form method="GET" class="flex gap-3 flex-wrap">
        <select name="status" class="form-select w-36" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['pending','approved','released','rejected'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search citizenâ€¦" class="form-input w-52">
        <button class="btn bg-primary text-white">Search</button>
        @if(request()->hasAny(['status','search']))
            <a href="{{ route('inventory.releases.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white text-sm">Clear</a>
        @endif
    </form>
    <a href="{{ route('inventory.releases.create') }}" class="btn bg-primary text-white gap-2">
        <i class="mgc_add_line"></i> New Release
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Release Requests</h5>
    </div>
    <div class="overflow-x-auto">
        <table class="table min-w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Citizen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Purpose</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Items</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($releases as $rel)
                <tr>
                    <td class="px-5 py-3 text-gray-400 text-sm">{{ $rel->id }}</td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800 dark:text-gray-100">
                            {{ $rel->citizen->fname ?? 'â€”' }} {{ $rel->citizen->lname ?? '' }}
                        </p>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500">{{ Str::limit($rel->purpose, 40) ?: 'â€”' }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500">{{ $rel->items->count() }} item(s)</td>
                    <td class="px-5 py-3 text-center">
                        @php
                            $statusStyles = [
                                'pending'  => 'bg-yellow-100 text-yellow-800 [&>span]:bg-yellow-400',
                                'approved' => 'bg-primary/25 text-sky-800 [&>span]:bg-sky-400',
                                'released' => 'bg-green-100 text-green-800 [&>span]:bg-green-400',
                                'rejected' => 'bg-red-100 text-red-800 [&>span]:bg-red-400',
                            ];
                            $cls = $statusStyles[$rel->status] ?? 'bg-gray-100 text-gray-800 [&>span]:bg-gray-400';
                        @endphp
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $cls }}">
                            <span class="w-1.5 h-1.5 inline-block rounded-full"></span>
                            {{ ucfirst($rel->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-400">{{ $rel->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('inventory.releases.show', $rel) }}" class="btn btn-sm bg-primary text-white">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-400">
                        <i class="mgc_send_line text-4xl mb-2 block opacity-30"></i>
                        No releases found. <a href="{{ route('inventory.releases.create') }}" class="text-primary underline">Create one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($releases->hasPages())
    <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700">{{ $releases->links() }}</div>
    @endif
</div>

@endsection

