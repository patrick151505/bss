@extends('layouts.vertical', [
    'title'       => $r->full_name,
    'tagline'     => 'Online registration #' . $r->id . ' — submitted ' . $r->created_at->format('M d, Y g:i A') . ($r->token ? ' through "' . $r->token->label . '"' : ''),
    'breadcrumbs' => [
        ['label' => 'Citizens',      'url' => route('citizens.index')],
        ['label' => 'Registrations', 'url' => route('citizens.registrations.index')],
        ['label' => $r->full_name,   'url' => ''],
    ],
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@php
    $d = $r->data;
    $yes = fn($v) => ($v ?? 0) == 1 ? 'Yes' : 'No';
    // A group whose ID staff marked Not valid was NOT given to the citizen.
    $groupText = fn(string $flag) => match ($r->groupState($flag)) {
        'yes'     => 'Yes',
        'invalid' => 'No — ID marked Not valid (claimed by the resident)',
        default   => 'No',
    };
    $addressLine = collect([
        !empty($d['no']) ? 'No. ' . $d['no'] : null,
        $d['street'] ?? null,
        !empty($d['blk']) ? 'Blk ' . $d['blk'] : null,
        !empty($d['lot']) ? 'Lot ' . $d['lot'] : null,
        !empty($d['phase']) ? 'Phase ' . $d['phase'] : null,
        $d['complete_address'] ?? null,
        $zone?->description,
    ])->filter()->implode(', ');
    $rows = [
        'Personal' => [
            'Full name'    => $r->full_name,
            'Gender'       => isset($d['gender']) ? ((int) $d['gender'] === 1 ? 'Male' : 'Female') : '—',
            'Civil status' => $civil?->description ?? '—',
            'Birthday'     => $r->bday->format('F d, Y') . ' (age ' . $r->bday->age . ')',
            'Birthplace'   => $d['bplace'] ?? '—',
            'Citizenship'  => $d['citizenship'] ?? '—',
            'Occupation'   => $d['occupation'] ?? '—',
        ],
        'Address & residency' => [
            'Address'          => $addressLine ?: '—',
            'Living here since' => !empty($d['year_stay']) ? \Carbon\Carbon::parse($d['year_stay'] . '-01')->format('F Y') : '—',
            'Home ownership'   => isset($d['owner_status']) ? ((int) $d['owner_status'] === 1 ? 'Owner' : 'Renter / Others') : '—',
            'House owner'      => isset($d['owner_status']) && (int) $d['owner_status'] === 0 ? ($d['house_owner_name'] ?? '' ?: '—') : '—',
        ],
        'Contact & groups' => [
            'Mobile'      => $d['contact'] ?? '—',
            'Email'       => $d['email'] ?? '—',
            'Senior citizen' => $groupText('is_senior'),
            'PWD'         => $groupText('is_pwd'),
            'Solo parent' => $groupText('is_soloparents'),
            'Voter'       => $yes($d['voters'] ?? 0) . (($d['voters'] ?? 0) == 1 && !empty($d['pricinct_no']) ? ' — precinct ' . $d['pricinct_no'] : ''),
        ],
        'Emergency contact' => [
            'Name'         => $d['ic_fullname'] ?? '—',
            'Relationship' => $d['ic_relationship'] ?? '—',
            'Mobile'       => $d['ic_contact'] ?? '—',
            'Email'        => $d['ic_email'] ?? '—',
            'Address'      => $d['ic_address'] ?? '—',
        ],
    ];
@endphp

@section('content')

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <ul class="text-sm text-danger space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

@php $pending = $r->status === 'pending'; @endphp
@if($pending)
<form method="POST" action="{{ route('citizens.registrations.approve', $r) }}" id="review-form">
    @csrf
@endif
<div class="grid lg:grid-cols-3 gap-6">

    {{-- Details --}}
    <div class="lg:col-span-2 space-y-6">
        @if($pending)
        <div class="p-3 rounded-lg bg-info/10 border border-info/30 text-sm text-gray-800 dark:text-gray-100 flex gap-2">
            <i class="mgc_edit_line text-info text-lg shrink-0"></i>
            <span>Correct anything the resident typed wrong (for example the precinct number), then <strong>Approve</strong>. Your corrections are recorded in the activity log.</span>
        </div>
        @include('citizens.registrations._edit-fields')
        @else
        @foreach($rows as $section => $fields)
        <div class="card">
            <div class="card-header"><h4 class="card-title">{{ $section }}</h4></div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($fields as $label => $value)
                    <tr>
                        <th class="px-5 py-2.5 text-left w-2/5 text-gray-500 dark:text-gray-400 font-medium">{{ $label }}</th>
                        <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $value !== '' && $value !== null ? $value : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
        @endif
    </div>

    {{-- Photo + decision --}}
    <div class="space-y-6">
        @php
            $proofLabels = ['senior' => 'Senior Citizen ID', 'pwd' => 'PWD ID', 'solo' => 'Solo Parent ID'];
            $proofs = $d['proofs'] ?? [];
        @endphp
        @if($proofs)
        <div class="card p-5">
            <h4 class="card-title mb-1">ID proof</h4>
            <p class="text-xs text-gray-400 mb-3">Open each picture and check the ID is real and its name and birthday match. Then mark it.</p>
            <div class="space-y-5">
                @foreach($proofs as $group => $sides)
                @php
                    $ver = $d['verification'][$group] ?? null;
                    $sel = old("verify_{$group}", $ver['status'] ?? '');
                @endphp
                <div class="proof-box" data-key="{{ $group }}">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ $proofLabels[$group] ?? ucfirst($group) }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['front' => 'Front', 'back' => 'Back'] as $side => $sideLabel)
                        @if(!empty($sides[$side]))
                        @php $u = route('citizens.registrations.proof', [$r, $group, $side]); @endphp
                        <a href="{{ $u }}" target="_blank" class="block rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white" title="Open full size">
                            <img src="{{ $u }}" alt="{{ $sideLabel }}" class="w-full h-24 object-contain">
                            <span class="block text-center text-xs text-gray-500 py-1 bg-gray-50 dark:bg-gray-700 dark:text-gray-300">{{ $sideLabel }}</span>
                        </a>
                        @endif
                        @endforeach
                    </div>

                    @if($pending)
                    <div class="verify mt-2 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border text-sm cursor-pointer {{ $sel === 'verified' ? 'border-success bg-success/10' : 'border-gray-200 dark:border-gray-700' }}">
                                <input type="radio" name="verify_{{ $group }}" value="verified" class="form-radio text-success" @checked($sel === 'verified')>
                                <span class="text-success font-medium"><i class="mgc_check_circle_line"></i> Verified</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border text-sm cursor-pointer {{ $sel === 'invalid' ? 'border-danger bg-danger/10' : 'border-gray-200 dark:border-gray-700' }}">
                                <input type="radio" name="verify_{{ $group }}" value="invalid" class="form-radio text-danger" @checked($sel === 'invalid')>
                                <span class="text-danger font-medium"><i class="mgc_close_circle_line"></i> Not valid</span>
                            </label>
                        </div>
                        <input type="text" name="verify_{{ $group }}_note" maxlength="300" value="{{ old("verify_{$group}_note", $ver['note'] ?? '') }}"
                               placeholder="Note (e.g. ID no., expired, name doesn't match)" class="form-input w-full text-sm">
                        @if($errors->has("verify_{$group}"))<p class="text-xs text-danger">{{ $errors->first("verify_{$group}") }}</p>@endif
                        <p class="text-[11px] text-gray-400">Not valid: the citizen is added <strong>without</strong> this group.</p>
                    </div>
                    <p class="unticked hidden mt-2 text-xs text-gray-400">This group is unticked in the details, so no check is needed.</p>
                    @elseif($ver)
                    <p class="mt-2 text-xs {{ $ver['status'] === 'verified' ? 'text-success' : 'text-danger' }}">
                        <i class="{{ $ver['status'] === 'verified' ? 'mgc_check_circle_line' : 'mgc_close_circle_line' }}"></i>
                        {{ $ver['status'] === 'verified' ? 'Verified' : 'Not valid' }} by {{ $ver['by_name'] ?? 'staff' }}
                        · {{ \Carbon\Carbon::parse($ver['at'])->format('M d, Y g:i A') }}{{ !empty($ver['note']) ? ' — ' . $ver['note'] : '' }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <div class="card p-5 text-center">
            <div class="w-40 h-40 mx-auto rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden flex items-center justify-center">
                @if($r->photo_url)
                <a href="{{ $r->photo_url }}" target="_blank"><img src="{{ $r->photo_url }}" class="w-full h-full object-cover" alt=""></a>
                @else
                <i class="mgc_user_3_line text-5xl text-gray-400"></i>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ $r->photo_url ? 'Photo sent with the registration' : ($r->status === 'rejected' ? 'Photo and ID copies were deleted when rejected' : 'No photo sent') }}</p>
        </div>

        @if($r->status === 'pending')
            @if($duplicates->isNotEmpty())
            <div class="p-4 rounded-lg bg-warning/10 border border-warning/30 text-sm text-gray-800 dark:text-gray-100">
                <p class="font-semibold flex items-center gap-1.5 mb-1"><i class="mgc_alert_line text-warning"></i> Possible duplicate</p>
                <p class="mb-2">A citizen with the same name and birthday already exists:</p>
                <ul class="space-y-1">
                    @foreach($duplicates as $dup)
                    <li>
                        <a href="{{ route('citizens.show', $dup->id) }}" target="_blank" class="text-primary hover:underline">
                            {{ $dup->lname }}, {{ $dup->fname }} — {{ \App\Models\Setting::instance()->formatCitizenId($dup->id) }}
                        </a>
                        @if($dup->is_walkin)<span class="text-xs text-gray-500">(walk-in)</span>@endif
                        @if(!$dup->is_active)<span class="text-xs text-gray-500">(inactive)</span>@endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="card p-5 space-y-3">
                <h4 class="card-title">Decision</h4>
                @if($duplicates->isNotEmpty() || $errors->has('duplicate'))
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="confirm_duplicate" value="1" class="form-checkbox rounded text-primary mt-0.5">
                    <span>I checked — this is a different person. Approve anyway.</span>
                </label>
                @endif
                <button type="button" onclick="confirmApprove()" class="btn bg-success text-white w-full justify-center">
                    <i class="mgc_check_line"></i> Approve &amp; add to citizens
                </button>
                <button type="submit" formaction="{{ route('citizens.registrations.update', $r) }}"
                        class="btn bg-primary/25 text-primary hover:bg-primary hover:text-white w-full justify-center">
                    <i class="mgc_save_line"></i> Save changes only
                </button>
                <button type="button" data-fc-type="modal" data-fc-target="reject-modal"
                        class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white w-full justify-center">
                    <i class="mgc_close_line"></i> Reject
                </button>
            </div>
        @else
            <div class="card p-5 text-sm space-y-2">
                <h4 class="card-title mb-1">Review</h4>
                <p class="text-gray-800 dark:text-gray-200">
                    <span class="font-semibold">{{ $r->status === 'approved' ? 'Approved' : 'Rejected' }}</span>
                    {{ $r->reviewed_at?->format('M d, Y g:i A') }}{{ $r->reviewer ? ' by ' . $r->reviewer->name : '' }}
                </p>
                @if($r->status === 'rejected')
                <p class="text-gray-600 dark:text-gray-300">Reason: {{ $r->reject_reason }}</p>
                @endif
                @if($r->status === 'approved' && $r->citizen_id)
                <a href="{{ route('citizens.show', $r->citizen_id) }}" class="btn btn-sm bg-primary text-white"><i class="mgc_user_3_line"></i> Open citizen record</a>
                @endif
            </div>
        @endif

        <a href="{{ route('citizens.registrations.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white w-full justify-center">
            <i class="mgc_arrow_left_line"></i> Back
        </a>
    </div>
</div>
@if($pending)
</form>
@endif

@if($r->status === 'pending')
<div id="reject-modal" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
    <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <form method="POST" action="{{ route('citizens.registrations.reject', $r) }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            @csrf
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Reject registration</h3>
                <button data-fc-dismiss="modal" type="button"><i class="mgc_close_line text-xl"></i></button>
            </div>
            <div class="p-4">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="reject_reason" rows="3" minlength="3" maxlength="500" required class="form-input w-full"
                          placeholder="e.g. Not a resident of this barangay, duplicate, incomplete details"></textarea>
            </div>
            <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
                <button data-fc-dismiss="modal" type="button" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" class="btn bg-danger text-white">Reject</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('inline-scripts')
<script>
function confirmApprove() {
    Swal.fire({
        title: 'Approve this registration?',
        text: @js($r->fname . ' ' . $r->lname) + ' will be added to the citizens list as an approved resident.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, approve',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#0acf97', 'important');
        },
    }).then(res => {
        if (!res.isConfirmed) return;
        const f = document.getElementById('review-form');
        if (f.reportValidity()) f.submit();
    });
}

(function () {
    const form = document.getElementById('review-form');
    if (!form) return;

    // Address parts follow the zone (same as the citizen form).
    const sel = document.getElementById('rv-address');
    const parts = ['blk', 'lot', 'phase', 'no', 'street', 'complete_address'];
    function toggleAddress() {
        const opt = sel.options[sel.selectedIndex];
        const subd = parseInt(opt?.dataset.subd || 0, 10);
        const rules = (opt?.dataset.rules || '').split(',').map(r => r.trim()).filter(Boolean);
        const show = subd === 1 ? ['blk', 'lot', 'phase'] : subd === 2 ? ['complete_address'] : ['no', 'street'];
        parts.forEach(f => {
            const box = document.getElementById('rv-f-' + f);
            box.classList.toggle('hidden', !show.includes(f));
            box.querySelector('input, textarea').required = show.includes(f) && rules.includes(f);
        });
    }
    sel.addEventListener('change', toggleAddress);
    toggleAddress();

    // Voter → precinct required.
    const voter = document.getElementById('rv-voters');
    const precinct = document.getElementById('rv-precinct');
    const syncVoter = () => { precinct.classList.toggle('hidden', !voter.checked); precinct.querySelector('input').required = voter.checked; };
    voter.addEventListener('change', syncVoter);
    syncVoter();

    // An ID check is only needed while its group is ticked.
    function syncChecks() {
        document.querySelectorAll('.rv-group').forEach(cb => {
            const box = document.querySelector(`.proof-box[data-key="${cb.dataset.key}"]`);
            if (!box) return;
            box.querySelector('.verify')?.classList.toggle('hidden', !cb.checked);
            box.querySelector('.unticked')?.classList.toggle('hidden', cb.checked);
            box.querySelectorAll('.verify input').forEach(i => i.disabled = !cb.checked);
        });
    }
    document.querySelectorAll('.rv-group').forEach(cb => cb.addEventListener('change', syncChecks));
    syncChecks();

    // Highlight the chosen Verified / Not valid option.
    document.querySelectorAll('.verify input[type=radio]').forEach(rd => rd.addEventListener('change', () => {
        rd.closest('.verify').querySelectorAll('label').forEach(l => {
            const on = l.querySelector('input').checked, ok = l.querySelector('input').value === 'verified';
            l.className = 'flex items-center gap-2 p-2 rounded-lg border text-sm cursor-pointer '
                + (on ? (ok ? 'border-success bg-success/10' : 'border-danger bg-danger/10') : 'border-gray-200 dark:border-gray-700');
        });
    }));
})();
</script>
@endpush
