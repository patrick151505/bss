@extends('layouts.vertical', [
    'title'       => $legislation->number,
    'tagline'     => $legislation->reference() . ', Series of ' . $legislation->series_year,
    'breadcrumbs' => [
        ['label' => 'Ordinances & Resolutions', 'url' => route('legislation.index')],
        ['label' => $legislation->number,       'url' => ''],
    ],
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@php
    use App\Models\LegislationEvent;
    $L = $legislation;
    $next = $L->nextStatus();
    $ext = $L->file_path ? strtolower(pathinfo($L->file_path, PATHINFO_EXTENSION)) : null;
    $votes = collect(['Yes' => $L->votes_yes, 'No' => $L->votes_no, 'Abstain' => $L->votes_abstain])->filter(fn($v) => $v !== null);
@endphp

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
    <div class="text-sm text-danger font-medium">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
</div>
@endif

@if($L->status === 'repealed')
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_close_circle_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">
        No longer in force.
        @if($L->repealedBy)
        Repealed by <a href="{{ route('legislation.show', $L->repealedBy) }}" class="underline">{{ $L->repealedBy->reference() }}</a>.
        @endif
    </p>
</div>
@elseif($L->repealedBy)
<div class="mb-4 p-4 rounded-lg bg-warning/10 border border-warning/30 flex gap-3">
    <i class="mgc_alert_line text-warning text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-warning font-medium">
        <a href="{{ route('legislation.show', $L->repealedBy) }}" class="underline">{{ $L->repealedBy->reference() }}</a>
        will repeal this once it is approved.
    </p>
</div>
@endif

{{-- ── Header ── --}}
<div class="card p-6 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wide {{ $L->type === 'ordinance' ? 'text-primary' : 'text-info' }}">{{ $L->typeLabel() }} No. {{ $L->number }}</span>
                @include('legislation._status-badge', ['measure' => $L])
                @if($L->category)
                <span class="text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md px-2 py-1">{{ $L->category }}</span>
                @endif
            </div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 leading-snug">{{ $L->title }}</h2>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            @can('legislation.approve')
                @if($next)
                <button type="button" data-fc-type="modal" data-fc-target="advance-modal"
                        onclick="setAdvance('{{ $next }}', @js($L->labelFor($next)))"
                        class="btn bg-primary text-white">
                    <i class="mgc_arrow_right_line me-1"></i>{{ $L->labelFor($next) }}
                </button>
                @endif
                @if($L->canBeVetoed())
                <button type="button" data-fc-type="modal" data-fc-target="advance-modal"
                        onclick="setAdvance('vetoed', 'Vetoed')"
                        class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white">
                    <i class="mgc_forbid_circle_line me-1"></i>Veto
                </button>
                @endif
            @endcan
            @can('legislation.edit')
            <a href="{{ route('legislation.edit', $L) }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                <i class="mgc_edit_line me-1"></i>Edit
            </a>
            @endcan
            @can('legislation.delete')
            <button type="button" onclick="confirmDelete()" class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white" title="Delete">
                <i class="mgc_delete_line"></i>
            </button>
            <form id="delete-form" method="POST" action="{{ route('legislation.destroy', $L) }}" class="hidden">@csrf @method('DELETE')</form>
            @endcan
        </div>
    </div>

    {{-- Progress along the normal path --}}
    @php
        $flow = \App\Models\Legislation::FLOW[$L->type];
        $pos  = array_search($L->status, $flow, true);
        if ($L->status === 'repealed') $pos = count($flow) - 1;
    @endphp
    <div class="mt-5 flex items-center gap-1 overflow-x-auto pb-1">
        @foreach($flow as $i => $step)
        @php $done = $pos !== false && $i <= $pos; @endphp
        <div class="flex items-center gap-1 shrink-0">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold
                         {{ $done ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                @if($done)<i class="mgc_check_line"></i>@else{{ $i + 1 }}@endif
            </span>
            <span class="text-xs {{ $done ? 'text-gray-800 dark:text-gray-100 font-medium' : 'text-gray-400' }}">{{ $L->labelFor($step) }}</span>
        </div>
        @if(!$loop->last)<span class="w-6 h-px shrink-0 {{ $pos !== false && $i < $pos ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-600' }}"></span>@endif
        @endforeach
        @if($L->status === 'vetoed')
        <span class="ms-2 text-xs font-medium text-danger">Stopped: vetoed</span>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- ── Left ── --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6">
            <h4 class="card-title mb-4">Details</h4>
            @if($L->summary)
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line mb-5">{{ $L->summary }}</p>
            @endif
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Authors</dt>
                    <dd class="text-gray-800 dark:text-gray-200">
                        @forelse($L->authors as $a)
                        <p>{{ $a->name }} <span class="text-xs text-gray-400">{{ $a->role === 'author' ? '' : '· ' . $a->roleLabel() }}</span></p>
                        @empty
                        <span class="text-gray-400">—</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Votes</dt>
                    <dd class="text-gray-800 dark:text-gray-200">
                        @if($votes->isNotEmpty())
                        <div class="flex gap-4">
                            @foreach($votes as $label => $n)
                            <span><span class="font-semibold">{{ $n }}</span> <span class="text-xs text-gray-400">{{ $label }}</span></span>
                            @endforeach
                        </div>
                        @else
                        <span class="text-gray-400">Not recorded</span>
                        @endif
                    </dd>
                </div>
                @foreach(['Introduced' => $L->date_introduced, ($L->type === 'resolution' ? 'Adopted' : 'Approved') => $L->date_approved, 'Effective' => $L->date_effective] as $label => $d)
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ $label }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ $d?->format('F d, Y') ?? '—' }}</dd>
                </div>
                @endforeach
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Recorded by</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ $L->creator?->name ?? '—' }} <span class="text-xs text-gray-400">{{ $L->created_at->format('M d, Y g:i A') }}</span></dd>
                </div>
            </dl>

            @if($L->amends || $L->amendments->isNotEmpty() || $L->repeals->isNotEmpty())
            <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 space-y-2 text-sm">
                @if($L->amends)
                <p class="text-gray-700 dark:text-gray-300"><i class="mgc_edit_line text-warning me-1"></i> Amends
                    <a href="{{ route('legislation.show', $L->amends) }}" class="text-primary hover:underline">{{ $L->amends->reference() }}</a></p>
                @endif
                @foreach($L->amendments as $m)
                <p class="text-gray-700 dark:text-gray-300"><i class="mgc_edit_line text-warning me-1"></i> Amended by
                    <a href="{{ route('legislation.show', $m) }}" class="text-primary hover:underline">{{ $m->reference() }}</a>
                    <span class="text-xs text-gray-400">({{ $m->statusLabel() }})</span></p>
                @endforeach
                @foreach($L->repeals as $r)
                <p class="text-gray-700 dark:text-gray-300"><i class="mgc_close_circle_line text-danger me-1"></i> {{ $L->hasPassed() ? 'Repeals' : 'Will repeal' }}
                    <a href="{{ route('legislation.show', $r) }}" class="text-primary hover:underline">{{ $r->reference() }}</a></p>
                @endforeach
            </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h4 class="card-title">Signed copy</h4>
                    @if($L->file_path)
                    <div class="flex gap-2">
                        <a href="{{ route('legislation.file', $L) }}" target="_blank" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white">
                            <i class="mgc_external_link_line me-1"></i>Open
                        </a>
                        <a href="{{ route('legislation.file', [$L, 'download' => 1]) }}" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white">
                            <i class="mgc_download_2_line me-1"></i>Download
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            <div class="p-6">
                @if(!$L->file_path)
                <div class="text-center py-8 text-gray-400">
                    <i class="mgc_pdf_line text-4xl mb-2 block opacity-30"></i>
                    No signed copy uploaded.
                    @can('legislation.edit')<a href="{{ route('legislation.edit', $L) }}" class="text-primary hover:underline">Upload one</a>@endcan
                </div>
                @elseif($ext === 'pdf')
                <iframe src="{{ route('legislation.file', $L) }}" class="w-full rounded-md border border-gray-200 dark:border-gray-700" style="height: 640px"></iframe>
                @else
                <img src="{{ route('legislation.file', $L) }}" alt="Signed copy" class="max-w-full rounded-md border border-gray-200 dark:border-gray-700">
                @endif
            </div>
        </div>
    </div>

    {{-- ── Right: timeline ── --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h4 class="card-title">Timeline</h4>
                    @can('legislation.approve')
                    <button type="button" data-fc-type="modal" data-fc-target="event-modal" class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white">
                        <i class="mgc_add_line me-1"></i>Add
                    </button>
                    @endcan
                </div>
            </div>
            <div class="p-6">
                @forelse($L->events as $ev)
                @php [$evLabel, $evIcon, $evBg, $evFg] = $ev->meta(); @endphp
                <div class="relative flex gap-3 {{ $loop->last ? '' : 'pb-5' }}">
                    @unless($loop->last)<span class="absolute left-4 top-9 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></span>@endunless
                    <span class="w-8 h-8 rounded-full {{ $evBg }} flex items-center justify-center shrink-0">
                        <i class="{{ $evIcon }} {{ $evFg }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex justify-between gap-2">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $ev->event === 'approved' ? $L->labelFor('approved') : $evLabel }}</p>
                            @if(in_array($ev->event, LegislationEvent::MANUAL, true))
                            @can('legislation.approve')
                            <button type="button" onclick="confirmRemoveEvent({{ $ev->id }})" class="text-gray-400 hover:text-danger" title="Remove">
                                <i class="mgc_close_line"></i>
                            </button>
                            <form id="event-del-{{ $ev->id }}" method="POST" action="{{ route('legislation.events.destroy', [$L, $ev]) }}" class="hidden">@csrf @method('DELETE')</form>
                            @endcan
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ev->event_date->format('M d, Y') }}@if($ev->user) · {{ $ev->user->name }}@endif</p>
                        @if($ev->remarks)
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 whitespace-pre-line">{{ $ev->remarks }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-center py-6 text-sm text-gray-400">Nothing on the timeline yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@can('legislation.approve')
{{-- Next step / veto --}}
<div id="advance-modal" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
    <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <form method="POST" action="{{ route('legislation.advance', $L) }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            @csrf
            <input type="hidden" name="to" id="adv-to">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Mark as <span id="adv-label"></span></h3>
                <button type="button" data-fc-dismiss="modal"><i class="mgc_close_line text-xl text-gray-500 dark:text-gray-400"></i></button>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" required class="form-input w-full">
                </div>
                <div id="adv-votes" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Votes <span class="text-xs font-normal text-gray-400">optional</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach(['votes_yes' => 'Yes', 'votes_no' => 'No', 'votes_abstain' => 'Abstain'] as $f => $label)
                        <div>
                            <input type="number" name="{{ $f }}" value="{{ $L->$f }}" min="0" max="99" class="form-input w-full text-center" placeholder="—">
                            <p class="text-[11px] text-center text-gray-400 mt-0.5">{{ $label }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" class="form-input w-full" placeholder="Session no., minutes reference…"></textarea>
                </div>
                <p id="adv-repeal-note" class="hidden text-xs text-warning">
                    This will also mark {{ $L->repeals->pluck('number')->implode(', ') }} as Repealed.
                </p>
            </div>
            <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
                <button type="button" data-fc-dismiss="modal" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" id="adv-submit" class="btn bg-primary text-white">Confirm</button>
            </div>
        </form>
    </div>
</div>

{{-- Add to timeline --}}
<div id="event-modal" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
    <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <form method="POST" action="{{ route('legislation.events.store', $L) }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            @csrf
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Add to timeline</h3>
                <button type="button" data-fc-dismiss="modal"><i class="mgc_close_line text-xl text-gray-500 dark:text-gray-400"></i></button>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entry</label>
                        <select name="event" class="form-select w-full">
                            <option value="posted">Posted / published</option>
                            <option value="note">Note</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" required class="form-input w-full">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                    <textarea name="remarks" rows="3" class="form-input w-full" placeholder="Where it was posted, or the note"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
                <button type="button" data-fc-dismiss="modal" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" class="btn bg-primary text-white">Add</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('inline-scripts')
<script>
const HAS_REPEALS = {{ $L->repeals->isNotEmpty() ? 'true' : 'false' }};
function setAdvance(to, label) {
    document.getElementById('adv-to').value = to;
    document.getElementById('adv-label').textContent = label;
    // Votes are taken on final passage (third reading / approval).
    document.getElementById('adv-votes').classList.toggle('hidden', !['third_reading', 'approved', 'vetoed'].includes(to));
    document.getElementById('adv-repeal-note').classList.toggle('hidden', !(HAS_REPEALS && to === 'approved'));
    const btn = document.getElementById('adv-submit');
    btn.classList.toggle('bg-primary', to !== 'vetoed');
    btn.classList.toggle('bg-danger', to === 'vetoed');
}

function swalColor(color) {
    return () => document.querySelector('.swal2-confirm').style.setProperty('background-color', color, 'important');
}

function confirmDelete() {
    Swal.fire({
        title: 'Delete {{ $L->reference() }}?',
        text: @js($L->repeals->isNotEmpty() ? 'The measures it repealed will be back in force.' : 'It will be removed from the list.'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        reverseButtons: true,
        didOpen: swalColor('#fa5c7c'),
    }).then(r => { if (r.isConfirmed) document.getElementById('delete-form').submit(); });
}

function confirmRemoveEvent(id) {
    Swal.fire({
        title: 'Remove this entry?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        reverseButtons: true,
        didOpen: swalColor('#fa5c7c'),
    }).then(r => { if (r.isConfirmed) document.getElementById('event-del-' + id).submit(); });
}
</script>
@endpush
