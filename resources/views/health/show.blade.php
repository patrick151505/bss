@extends('layouts.vertical', [
    'title'       => 'Check-up',
    'tagline'     => $checkup->patientName() . ' · ' . $checkup->checked_at->format('M d, Y g:i A'),
    'breadcrumbs' => [
        ['label' => 'Health',                'url' => route('health.index')],
        ['label' => $checkup->patientName(), 'url' => ''],
    ],
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@php
    $C = $checkup;
    $fmt = fn($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $vitals = [
        ['Blood pressure',    $C->bloodPressure(), 'mmHg',        $C->bpFlag(),    'mgc_heartbeat_line'],
        ['Temperature',       $fmt($C->temperature), '°C',        $C->tempFlag(),  'mgc_thermometer_line'],
        ['Pulse',             $C->pulse, 'bpm',                   $C->pulseFlag(), 'mgc_heart_line'],
        ['Respiratory rate',  $C->resp_rate, '/min',              null,            'mgc_walk_line'],
        ['Oxygen saturation', $C->o2_sat, '%',                    $C->o2Flag(),    'mgc_drop_line'],
        ['Blood sugar',       $fmt($C->blood_sugar), 'mg/dL' . ($C->blood_sugar ? ($C->sugar_fasting ? ' · fasting' : ' · random') : ''), $C->sugarFlag(), 'mgc_drop_line'],
        ['Weight',            $fmt($C->weight_kg), 'kg',          null,            'mgc_scale_line'],
        ['Height',            $fmt($C->height_cm), 'cm',          null,            'mgc_scale_line'],
        ['BMI',               $C->bmi(), '',                      $C->bmiFlag(),   'mgc_scale_line'],
    ];
    $measured = collect($vitals)->filter(fn($v) => $v[1] !== null && $v[1] !== '');
@endphp

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

{{-- ── Patient header ── --}}
<div class="card p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            @if($C->citizen?->profile_url)
            <img src="{{ $C->citizen->profile_url }}" class="w-14 h-14 rounded-full object-cover" alt="">
            @else
            <div class="w-14 h-14 rounded-full bg-primary/10 text-primary font-bold text-lg flex items-center justify-center shrink-0">
                {{ collect(explode(' ', $C->patientName()))->filter()->map(fn($s) => mb_substr($s, 0, 1))->take(2)->implode('') }}
            </div>
            @endif
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ $C->patientName() }}
                    @if($C->isWalkIn())
                    <span class="ms-1 align-middle inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>Walk-in
                    </span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ collect([$C->patientAge() !== null ? $C->patientAge() . ' yrs old' : null, $C->patientSex(), $C->citizen?->full_address])->filter()->implode(' · ') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $C->checked_at->format('M d, Y g:i A') }}@if($C->attended_by) · Attended by {{ $C->attended_by }}@endif
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($C->citizen && auth()->user()->can('citizens.view'))
            <a href="{{ route('citizens.show', $C->citizen) }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                <i class="mgc_user_3_line me-1"></i>Profile
            </a>
            @endif
            @can('health.create')
            @if($C->citizen_id)
            <a href="{{ route('health.create', ['citizen' => $C->citizen_id]) }}" class="btn bg-success/25 text-success hover:bg-success hover:text-white">
                <i class="mgc_add_line me-1"></i>New check-up
            </a>
            @endif
            @endcan
            @can('health.edit')
            <a href="{{ route('health.edit', $C) }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                <i class="mgc_edit_line me-1"></i>Edit
            </a>
            @endcan
            @can('health.delete')
            <button type="button" onclick="confirmDelete()" class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white" title="Delete"><i class="mgc_delete_line"></i></button>
            <form id="delete-form" method="POST" action="{{ route('health.destroy', $C) }}" class="hidden">@csrf @method('DELETE')</form>
            @endcan
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Vital signs --}}
        <div class="card p-6">
            <h4 class="card-title mb-4">Vital signs</h4>
            @if($measured->isEmpty())
            <p class="text-sm text-gray-400">No vital signs recorded.</p>
            @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($measured as [$label, $value, $unit, $flag, $icon])
                <div class="p-4 rounded-lg border border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-1.5"><i class="{{ $icon }}"></i>{{ $label }}</p>
                    <p class="mt-1">
                        <span class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $value }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $unit }}</span>
                    </p>
                    @if($flag)<div class="mt-1.5">@include('health._flag', ['flag' => $flag])</div>@endif
                </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-3">Flags are screening guides for adults, not a diagnosis.</p>
            @endif
        </div>

        {{-- Consultation --}}
        <div class="card p-6">
            <h4 class="card-title mb-4">Consultation</h4>
            <dl class="space-y-4 text-sm">
                @foreach(['Complaint' => $C->complaint, 'Findings / assessment' => $C->findings, 'Advice / treatment' => $C->advice] as $label => $text)
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ $label }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $text ?: '—' }}</dd>
                </div>
                @endforeach
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Referred to</dt>
                        <dd class="text-gray-800 dark:text-gray-200">{{ $C->referred_to ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Follow-up</dt>
                        <dd class="text-gray-800 dark:text-gray-200">
                            @if($C->follow_up_on)
                            {{ $C->follow_up_on->format('F d, Y') }}
                            @if($C->follow_up_on->isToday())<span class="text-xs text-warning font-medium ms-1">today</span>
                            @elseif($C->follow_up_on->isPast())<span class="text-xs text-gray-400 ms-1">passed</span>@endif
                            @else — @endif
                        </dd>
                    </div>
                </div>
            </dl>
        </div>

        {{-- Previous visits --}}
        @if($history->isNotEmpty())
        <div class="card">
            <div class="card-header"><h4 class="card-title">Other check-ups of {{ $C->citizen?->fname ?? 'this patient' }}</h4></div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            @foreach(['Date', 'BP', 'Temp', 'Weight', 'Sugar', 'Complaint'] as $h)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($history as $h)
                        @php $bpTone = $h->bpFlag()[1] ?? null; @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 cursor-pointer" onclick="location.href='{{ route('health.show', $h) }}'">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $h->checked_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3 whitespace-nowrap font-medium {{ $bpTone === 'danger' ? 'text-danger' : ($bpTone === 'warning' ? 'text-warning' : 'text-gray-800 dark:text-gray-100') }}">{{ $h->bloodPressure() ?? '—' }}</td>
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $h->temperature ? $fmt($h->temperature) . ' °C' : '—' }}</td>
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $h->weight_kg ? $fmt($h->weight_kg) . ' kg' : '—' }}</td>
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $h->blood_sugar ? $fmt($h->blood_sugar) : '—' }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $h->complaint ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Right ── --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h4 class="card-title">Medicines given</h4>
                    @if($C->release && auth()->user()->can('inventory_releases.view'))
                    <a href="{{ route('inventory.releases.show', $C->release) }}" class="text-xs text-primary hover:underline">Release #{{ $C->release->id }}</a>
                    @endif
                </div>
            </div>
            <div class="p-6">
                @forelse($C->medicines as $m)
                <div class="py-2 {{ $loop->last ? '' : 'border-b border-gray-100 dark:border-gray-700' }}">
                    <div class="flex justify-between gap-2 text-sm">
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $m->name }}</span>
                        <span class="text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $m->quantity }} {{ $m->unit }}</span>
                    </div>
                    @if($m->instructions)<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $m->instructions }}</p>@endif
                </div>
                @empty
                <p class="text-sm text-gray-400">No medicines given from stock.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-6 text-sm space-y-2">
            <p class="text-gray-500 dark:text-gray-400">Recorded by <span class="text-gray-800 dark:text-gray-200">{{ $C->creator?->name ?? '—' }}</span></p>
            <p class="text-gray-500 dark:text-gray-400">on {{ $C->created_at->format('M d, Y g:i A') }}</p>
            @if($C->updated_at && $C->updated_at->gt($C->created_at->copy()->addMinute()))
            <p class="text-gray-500 dark:text-gray-400">Last edited {{ $C->updated_at->format('M d, Y g:i A') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('inline-scripts')
<script>
function confirmDelete() {
    Swal.fire({
        title: 'Delete this check-up?',
        text: @js($C->medicines->isNotEmpty() ? 'The medicines given stay deducted from Inventory.' : 'It will be removed from the records.'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        reverseButtons: true,
        didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important'),
    }).then(r => { if (r.isConfirmed) document.getElementById('delete-form').submit(); });
}
</script>
@endpush
