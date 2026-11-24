@php
    [$stLabel, $stPill, $stDot] = \App\Models\Pet::STATUSES[$pet->status] ?? \App\Models\Pet::STATUSES['active'];
    $vac  = $pet->vaccineCurrent();
    $icon = \App\Models\PetCategory::iconFor($pet->category?->name);
@endphp
@extends('layouts.vertical', [
    'title'       => $pet->name,
    'breadcrumbs' => [
        ['label' => 'Pets', 'url' => route('pets.index')],
        ['label' => $pet->name, 'url' => ''],
    ],
    'tagline'     => collect([$pet->category?->name, $pet->breed])->filter()->implode(' · ') ?: 'Pet',
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

<div class="grid grid-cols-12 gap-6">

    {{-- ── Photo + quick facts ── --}}
    <div class="col-span-12 lg:col-span-4 space-y-6">
        <div class="card overflow-hidden">
            <div class="relative bg-gray-100 dark:bg-gray-700" style="aspect-ratio:1/1">
                @if($pet->photo_url)
                <a href="{{ $pet->photo_url }}" target="_blank" rel="noopener" title="Open full photo">
                    <img src="{{ $pet->photo_url }}" alt="{{ $pet->name }}" class="absolute inset-0 w-full h-full object-cover {{ $pet->status === 'deceased' ? 'grayscale' : '' }}">
                </a>
                @else
                <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-300 dark:text-gray-500">
                    <i class="{{ $icon }} text-7xl"></i>
                    <span class="text-xs mt-2 text-gray-400">No photo</span>
                </div>
                @endif
            </div>
            <div class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $pet->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><i class="{{ $icon }}"></i> {{ $pet->category?->name ?? 'Pet' }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $stPill }}">
                        <span class="w-1.5 h-1.5 inline-block {{ $stDot }} rounded-full"></span>{{ $stLabel }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-2 mt-5">
                    @can('pets.edit')
                    <a href="{{ route('pets.edit', $pet) }}" class="btn btn-sm bg-primary text-white"><i class="mgc_edit_line"></i> Edit</a>
                    @endcan
                    @can('pets.delete')
                    <form action="{{ route('pets.destroy', $pet) }}" method="POST" id="delete-pet">
                        @csrf @method('DELETE')
                        <button type="button" onclick="confirmDeletePet()" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"><i class="mgc_delete_2_line"></i> Remove</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- ── Details ── --}}
    <div class="col-span-12 lg:col-span-8 space-y-6">

        {{-- Anti-rabies --}}
        @if($pet->status === 'active')
        <div class="p-4 rounded-lg flex gap-3 border
            {{ $vac === true ? 'bg-success/10 border-success/30' : ($vac === false ? 'bg-danger/10 border-danger/30' : 'bg-warning/10 border-warning/30') }}">
            <i class="{{ $vac === true ? 'mgc_shield_line text-success' : 'mgc_alert_line ' . ($vac === false ? 'text-danger' : 'text-warning') }} text-xl mt-0.5 shrink-0"></i>
            <div class="text-sm">
                @if($vac === true)
                <p class="font-medium text-success">Anti-rabies up to date</p>
                <p class="text-gray-600 dark:text-gray-300">Last shot {{ $pet->last_vaccinated_on->format('M d, Y') }} · next due {{ $pet->last_vaccinated_on->copy()->addDays(\App\Models\Pet::VACCINE_VALID_DAYS)->format('M d, Y') }}</p>
                @elseif($vac === false)
                <p class="font-medium text-danger">Anti-rabies due</p>
                <p class="text-gray-600 dark:text-gray-300">Last shot {{ $pet->last_vaccinated_on->format('M d, Y') }} ({{ $pet->last_vaccinated_on->diffForHumans() }}). Shots are yearly.</p>
                @else
                <p class="font-medium text-warning">No anti-rabies record</p>
                <p class="text-gray-600 dark:text-gray-300">Add the date of the last shot when known.</p>
                @endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><h4 class="card-title">Pet details</h4></div>
            <div class="p-6 grid sm:grid-cols-2 gap-x-8 gap-y-4">
                @foreach([
                    ['Kind of pet',       $pet->category?->name],
                    ['Breed',             $pet->breed],
                    ['Sex',               $pet->sexLabel()],
                    ['Color / markings',  $pet->color],
                    ['Birth date',        $pet->birth_date ? $pet->birth_date->format('M d, Y') . ' (' . $pet->ageText() . ')' : null],
                    ['Spayed / neutered', $pet->is_neutered ? 'Yes' : 'No'],
                    ['Last anti-rabies',  $pet->last_vaccinated_on?->format('M d, Y')],
                    ['Status',            $stLabel],
                ] as [$label, $value])
                <div>
                    <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">{{ $label }}</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $value ?: '—' }}</p>
                </div>
                @endforeach
                @if($pet->notes)
                <div class="sm:col-span-2">
                    <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Notes</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $pet->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Owner --}}
        <div class="card">
            <div class="card-header"><h4 class="card-title">Owner</h4></div>
            <div class="p-6">
                @if($pet->citizen)
                <a href="{{ route('citizens.show', $pet->citizen) }}" class="flex items-center gap-4 group">
                    <div class="w-14 h-14 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0 flex items-center justify-center text-gray-500 font-semibold">
                        @if($pet->citizen->profile_url)<img src="{{ $pet->citizen->profile_url }}" class="w-full h-full object-cover" alt="">@else{{ mb_substr($pet->citizen->fname, 0, 1) }}@endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-800 dark:text-gray-100 group-hover:text-primary">{{ $pet->citizen->full_name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pet->citizen->full_address ?: '—' }}</p>
                    </div>
                    <i class="mgc_right_line text-gray-400 ms-auto"></i>
                </a>
                @else
                <p class="text-sm text-gray-400">Owner record not available.</p>
                @endif

                @if($others->isNotEmpty())
                <p class="text-xs text-gray-400 uppercase font-medium mt-6 mb-2">Other pets of this owner</p>
                <div class="flex flex-wrap gap-3">
                    @foreach($others as $o)
                    <a href="{{ route('pets.show', $o) }}" class="flex items-center gap-2 py-1.5 pl-1.5 pr-3 rounded-full border border-gray-200 dark:border-gray-700 hover:border-primary text-sm text-gray-700 dark:text-gray-200">
                        <span class="w-7 h-7 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400">
                            @if($o->photo_url)<img src="{{ $o->photo_url }}" class="w-full h-full object-cover" alt="">@else<i class="{{ \App\Models\PetCategory::iconFor($o->category?->name) }}"></i>@endif
                        </span>
                        {{ $o->name }}
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <p class="text-xs text-gray-400">Added {{ $pet->created_at->format('M d, Y g:i A') }} · last updated {{ $pet->updated_at->format('M d, Y g:i A') }}</p>
    </div>
</div>
@endsection

@push('inline-scripts')
<script>
function confirmDeletePet() {
    Swal.fire({
        title: 'Remove ' + @js($pet->name) + '?',
        text: 'The pet and its photo will be deleted. To keep the record, set the status to Deceased or Lost instead.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important');
        },
    }).then(r => { if (r.isConfirmed) document.getElementById('delete-pet').submit(); });
}
</script>
@endpush
