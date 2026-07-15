@extends('layouts.vertical', ['title' => 'Document Requests', 'sub_title' => 'Documents', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

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

{{-- Stat Strip --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
            <i class="mgc_inbox_line text-primary text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['total']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
            <i class="mgc_time_line text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['pending']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center shrink-0">
            <i class="mgc_shield_check_line text-info text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Approved</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['approved']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
            <i class="mgc_check_circle_line text-success text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Released</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['released']) }}</p>
        </div>
    </div>
</div>

{{-- Filter + Actions --}}
<div class="card mb-5">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('documents.requests.index') }}"
              class="flex flex-wrap items-end gap-3 flex-1">
            <div class="flex-1 min-w-[160px]">
                <label class="form-label text-xs mb-1">Search Citizen</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="First or last name…" class="form-input text-sm py-1.5">
            </div>
            <div class="w-48">
                <label class="form-label text-xs mb-1">Document Type</label>
                <select name="type" class="form-select text-sm py-1.5">
                    <option value="">All Types</option>
                    @foreach($types as $t)
                    <option value="{{ $t->id }}" {{ request('type') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="form-label text-xs mb-1">Status</label>
                <select name="status" class="form-select text-sm py-1.5">
                    <option value="">All</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="released" {{ request('status') === 'released' ? 'selected' : '' }}>Released</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn bg-primary text-white text-sm py-1.5 px-4">
                    <i class="mgc_search_line me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search','type','status']))
                <a href="{{ route('documents.requests.index') }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3">Clear</a>
                @endif
            </div>
        </form>
        <a href="{{ route('documents.requests.create') }}"
           class="btn bg-primary text-white flex items-center gap-2 shrink-0">
            <i class="mgc_add_line"></i> New Request
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-12">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Citizen</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Document</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-28">Fee</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-28">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-36">Requested</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($requests as $req)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ str_pad($req->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->citizen->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $req->citizen->complete_address ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->documentType->name ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($req->is_paid)
                            <div class="text-xs font-mono font-semibold text-warning">₱ {{ number_format($req->fee, 2) }}</div>
                            @if($req->fee_paid)
                                <span class="text-[10px] text-success">✓ Paid</span>
                                @if($req->or_number)
                                <span class="text-[10px] text-gray-400 block">OR# {{ $req->or_number }}</span>
                                @endif
                            @else
                                <span class="text-[10px] text-warning">Unpaid</span>
                            @endif
                        @else
                            <span class="text-xs font-semibold text-success">FREE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $colors = ['pending'=>'warning','approved'=>'info','released'=>'success','rejected'=>'danger'];
                            $color  = $colors[$req->status] ?? 'secondary';
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}/15 text-{{ $color }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $req->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('documents.requests.show', $req) }}"
                               class="text-primary hover:text-primary/70 text-sm" title="View / Print">
                                <i class="mgc_eye_2_line"></i>
                            </a>
                            <form action="{{ route('documents.requests.destroy', $req) }}" method="POST"
                                  onsubmit="return confirm('Delete this request?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-danger hover:text-danger/70 text-sm" title="Delete">
                                    <i class="mgc_delete_2_line"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                        <i class="mgc_inbox_line text-3xl block mb-2"></i>
                        No document requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
        {{ $requests->links() }}
    </div>
    @endif
</div>

@endsection
