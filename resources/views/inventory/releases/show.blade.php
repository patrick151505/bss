@extends('layouts.vertical', [
    'title'         => 'Release #' . $release->id,
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => "Created ". $release->created_at->format('M d, Y g:i A'),
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
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('inventory.releases.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-1">
        <i class="mgc_arrow_left_line"></i> Back to Releases
    </a>
    <a href="{{ route('inventory.releases.print', $release) }}" target="_blank"
       class="btn bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 gap-2 text-sm">
        <i class="mgc_print_line"></i> Print Slip
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main content --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Citizen card --}}
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Citizen</h3>
            <div class="flex items-center gap-4">
                @php
                    $c = $release->citizen;
                    $profile = $c?->profile ? asset(str_replace('public/', 'storage/', $c->profile)) : null;
                @endphp
                <div class="w-14 h-14 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center shrink-0">
                    @if($profile)
                        <img src="{{ $profile }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-xl font-bold text-primary">{{ strtoupper(substr($c?->fname ?? '?', 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100 text-base">{{ $c?->full_name ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-400">{{ $c?->complete_address ?? '—' }}</p>
                    @if($c?->contact)
                    <p class="text-sm text-gray-400">📞 {{ $c->contact }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items table --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Items Released</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Category</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Qty</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalQty = 0; @endphp
                        @foreach($release->items as $ri)
                        @php $totalQty += $ri->quantity; @endphp
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $ri->item->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $ri->item->category->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-center font-semibold">{{ number_format($ri->quantity) }}</td>
                            <td class="px-5 py-3 text-center text-gray-400">{{ $ri->item->unit ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                        <tr>
                            <td class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase">
                                {{ $release->items->count() }} item type(s)
                            </td>
                            <td></td>
                            <td class="px-5 py-3 text-center font-bold text-gray-800 dark:text-gray-100">
                                {{ number_format($totalQty) }}
                            </td>
                            <td class="px-5 py-3 text-center text-xs text-gray-400">total qty</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($release->purpose)
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Purpose</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $release->purpose }}</p>
        </div>
        @endif

        @if($release->remarks)
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Remarks</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $release->remarks }}</p>
        </div>
        @endif

    </div>

    {{-- Sidebar: status + actions --}}
    <div class="space-y-5">

        {{-- Status card --}}
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Status</h3>
            @php
                $statusStyles = [
                    'pending'  => 'bg-yellow-100 text-yellow-800 [&>span]:bg-yellow-400',
                    'approved' => 'bg-primary/25 text-sky-800 [&>span]:bg-sky-400',
                    'released' => 'bg-green-100 text-green-800 [&>span]:bg-green-400',
                    'rejected' => 'bg-red-100 text-red-800 [&>span]:bg-red-400',
                ];
                $cls = $statusStyles[$release->status] ?? 'bg-gray-100 text-gray-800 [&>span]:bg-gray-400';
            @endphp
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $cls }}">
                <span class="w-1.5 h-1.5 inline-block rounded-full"></span>
                {{ ucfirst($release->status) }}
            </span>

            <div class="mt-4 space-y-2 text-sm text-gray-500">
                @if($release->approvedBy)
                <p><span class="font-medium">Approved by:</span> {{ $release->approvedBy->name }}</p>
                <p><span class="font-medium">Approved at:</span> {{ $release->approved_at?->format('M d, Y g:i A') }}</p>
                @endif
                @if($release->isReleased())
                <p><span class="font-medium">Released at:</span> {{ $release->released_at?->format('M d, Y g:i A') }}</p>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        @if($release->isPending())
        <div class="card p-5 space-y-3">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Actions</h3>
            <form action="{{ route('inventory.releases.approve', $release) }}" method="POST">
                @csrf @method('PATCH')
                <button class="btn bg-primary text-white w-full gap-2">
                    <i class="mgc_check_line"></i> Approve Release
                </button>
            </form>

            <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                    class="btn btn-soft-danger w-full gap-2">
                <i class="mgc_close_circle_line"></i> Reject
            </button>
            <form id="reject-form" action="{{ route('inventory.releases.reject', $release) }}" method="POST" class="hidden mt-2">
                @csrf @method('PATCH')
                <textarea name="remarks" rows="2" class="form-input w-full text-sm mb-2" placeholder="Reason for rejection…" required></textarea>
                <button class="btn btn-danger w-full">Confirm Reject</button>
            </form>
        </div>
        @endif

        @if($release->isApproved())
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Actions</h3>
            <form action="{{ route('inventory.releases.release', $release) }}" method="POST" id="release-confirm-form">
                @csrf @method('PATCH')
                <button type="button" class="btn bg-primary text-white w-full gap-2"
                        onclick="swalConfirmRelease()">
                    <i class="mgc_send_line"></i> Mark as Released
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

@endsection

@push('inline-scripts')
<script>
function swalConfirmRelease() {
    Swal.fire({
        title: 'Mark as Released?',
        text: 'This will deduct stock immediately and cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, release it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4361ee',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('release-confirm-form').submit();
    });
}
</script>
@endpush
