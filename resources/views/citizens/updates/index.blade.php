@extends('layouts.vertical', [
    'title'         => 'Detail Updates',
    'sub_title'     => 'Citizens',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Changes residents asked for at the kiosk. Nothing on the record changes until you approve.',
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
@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger">{{ $errors->first() }}</p>
</div>
@endif

<div class="card mb-6">
    <div class="card-header">
        <div class="flex flex-wrap gap-1">
            @foreach(\App\Models\CitizenUpdateRequest::STATUSES as $key => $label)
            <a href="{{ route('citizens.updates.index', ['status' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $status === $key ? 'bg-primary/10 text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                {{ $label }} <span class="text-xs opacity-70">({{ $counts[$key] ?? 0 }})</span>
            </a>
            @endforeach
        </div>
    </div>
</div>

<div class="space-y-4">
    @forelse($requests as $u)
    @php
        $c = $u->citizen;
        [$bg, $dot] = match($u->status) {
            'approved' => ['bg-green-100 text-green-800', 'bg-green-400'],
            'rejected' => ['bg-red-100 text-red-800', 'bg-red-400'],
            default    => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
        };
    @endphp
    <div class="card">
        <div class="p-5 flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3 min-w-0">
                @if($c?->profile_url)
                <img src="{{ $c->profile_url }}" alt="" class="w-12 h-12 rounded-full object-cover shrink-0">
                @else
                <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0"><i class="mgc_user_3_line text-xl"></i></div>
                @endif
                <div class="min-w-0">
                    @if($c)
                    <a href="{{ route('citizens.show', $c->id) }}" class="font-semibold text-gray-800 dark:text-gray-100 hover:text-primary">{{ $c->full_name }}</a>
                    @else
                    <p class="font-semibold text-gray-500">Citizen #{{ $u->citizen_id }} (removed)</p>
                    @endif
                    <p class="text-xs text-gray-400">
                        Asked {{ $u->created_at->format('M d, Y g:i A') }}{{ $u->kiosk ? ' at ' . $u->kiosk->name : ' at the kiosk' }}
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $bg }}">
                <span class="w-1.5 h-1.5 inline-block {{ $dot }} rounded-full"></span>{{ \App\Models\CitizenUpdateRequest::STATUSES[$u->status] ?? $u->status }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="table min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-1/4">Detail</th>
                        <th class="px-5 py-2.5 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">On record</th>
                        <th class="px-5 py-2.5 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Change to</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($u->changes as $field => $ch)
                    <tr>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ \App\Models\CitizenUpdateRequest::FIELDS[$field] ?? $field }}</td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400 line-through decoration-gray-300">{{ \App\Models\CitizenUpdateRequest::display($field, $ch['from'] ?? null) }}</td>
                        <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-100">{{ \App\Models\CitizenUpdateRequest::display($field, $ch['to'] ?? null) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-5 flex flex-wrap items-center justify-between gap-3">
            @if($u->status === 'pending')
            <p class="text-xs text-gray-400 max-w-md">Check with the resident (e.g. a valid ID) before approving. Approving updates the citizen record and is written to its activity log.</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('citizens.updates.reject', $u) }}" id="rej-{{ $u->id }}">
                    @csrf
                    <input type="hidden" name="reject_reason">
                    <button type="button" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white" onclick="rejectUpdate({{ $u->id }})">
                        <i class="mgc_close_line"></i> Reject
                    </button>
                </form>
                <form method="POST" action="{{ route('citizens.updates.approve', $u) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm bg-success text-white"><i class="mgc_check_line"></i> Approve &amp; update record</button>
                </form>
            </div>
            @else
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $u->status === 'approved' ? 'Approved' : 'Rejected' }} by {{ $u->reviewer?->name ?? 'staff' }} · {{ $u->reviewed_at?->format('M d, Y g:i A') }}
                @if($u->reject_reason) — {{ $u->reject_reason }}@endif
            </p>
            @endif
        </div>
    </div>
    @empty
    <div class="card p-12 text-center text-gray-400">
        <i class="mgc_edit_line text-4xl mb-2 block opacity-30"></i>No {{ strtolower(\App\Models\CitizenUpdateRequest::STATUSES[$status] ?? '') }} detail updates.
    </div>
    @endforelse
</div>

<div class="mt-4">{{ $requests->links() }}</div>

@endsection

@push('inline-scripts')
<script>
function rejectUpdate(id) {
    Swal.fire({
        title: 'Reject this update?',
        input: 'text',
        inputLabel: 'Reason (shown in the history)',
        inputPlaceholder: 'e.g. Could not confirm the new address',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        reverseButtons: true,
        inputValidator: v => (!v || v.trim().length < 3) ? 'Please give a short reason.' : undefined,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important'),
    }).then(r => {
        if (!r.isConfirmed) return;
        const f = document.getElementById('rej-' + id);
        f.querySelector('[name=reject_reason]').value = r.value.trim();
        f.submit();
    });
}
</script>
@endpush
