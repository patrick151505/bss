@extends('layouts.vertical', [
    'title'         => 'Online Registrations',
    'sub_title'     => 'Citizens',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'People who registered themselves through a registration link. Approve to add them to the citizens list.',
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

<div class="card">
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            {{-- Status tabs --}}
            <div class="flex flex-wrap gap-1">
                @foreach(\App\Models\CitizenRegistration::STATUSES as $key => $label)
                <a href="{{ route('citizens.registrations.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $status === $key ? 'bg-primary/10 text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    {{ $label }} <span class="text-xs opacity-70">({{ $counts[$key] ?? 0 }})</span>
                </a>
                @endforeach
            </div>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search name…" class="form-input text-sm">
                <button class="btn btn-sm bg-primary text-white"><i class="mgc_search_line"></i></button>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Birthday</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Link</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($registrations as $r)
                @php
                    [$bg, $dot] = match($r->status) {
                        'approved' => ['bg-green-100 text-green-800', 'bg-green-400'],
                        'rejected' => ['bg-red-100 text-red-800', 'bg-red-400'],
                        default    => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
                    };
                @endphp
                <tr>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden flex items-center justify-center shrink-0">
                                @if($r->photo_url)<img src="{{ $r->photo_url }}" class="w-full h-full object-cover" alt="">@else<i class="mgc_user_3_line text-gray-400"></i>@endif
                            </div>
                            <div>
                                <span class="font-medium">{{ $r->full_name }}</span>
                                @php
                                    $grp = collect(\App\Models\CitizenRegistration::GROUPS)
                                        ->map(fn($g, $flag) => ['label' => $g['label'], 'state' => $r->groupState($flag)])
                                        ->filter(fn($g) => $g['state'] !== 'no');
                                @endphp
                                @if($grp->isNotEmpty())
                                <div class="flex gap-1 mt-0.5">
                                    @foreach($grp as $g)
                                    @if($g['state'] === 'invalid')
                                    <span class="py-0.5 px-2 rounded-full text-[11px] font-medium bg-red-100 text-red-800 line-through" title="ID marked Not valid — not given">{{ $g['label'] }}</span>
                                    @else
                                    <span class="py-0.5 px-2 rounded-full text-[11px] font-medium bg-primary/25 text-sky-800">{{ $g['label'] }}</span>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $r->bday->format('M d, Y') }}</td>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200">{{ $r->field('contact') ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $r->token?->label ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $r->created_at->format('M d, Y g:i A') }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $bg }}">
                            <span class="w-1.5 h-1.5 inline-block {{ $dot }} rounded-full"></span>{{ \App\Models\CitizenRegistration::STATUSES[$r->status] ?? $r->status }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('citizens.registrations.show', $r) }}" class="btn btn-sm bg-primary text-white">
                            {{ $r->status === 'pending' ? 'Review' : 'View' }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-12 text-gray-400">
                    <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No {{ strtolower(\App\Models\CitizenRegistration::STATUSES[$status] ?? '') }} registrations.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($registrations->hasPages())
    <div class="p-4 border-t border-gray-100 dark:border-gray-700">{{ $registrations->links() }}</div>
    @endif
</div>

@endsection
