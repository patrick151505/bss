@extends('layouts.vertical', ['title' => 'Birthday Celebrants', 'sub_title' => 'Citizen Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@push('skeleton')
@include('partials._skeleton_table', ['rows' => 10, 'cols' => 5])
@endpush

@section('content')

@php
    $monthNames = [
        1=>'January', 2=>'February', 3=>'March', 4=>'April',
        5=>'May', 6=>'June', 7=>'July', 8=>'August',
        9=>'September', 10=>'October', 11=>'November', 12=>'December',
    ];
    $selectedMonthName = $monthNames[$month];
@endphp

{{-- Page Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <div>
        <h4 class="text-slate-900 dark:text-slate-100 text-lg font-semibold">
            <i class="mgc_cake_line me-2 text-primary"></i>Birthday Celebrants
        </h4>
        <p class="text-sm text-gray-400 mt-0.5">{{ $selectedMonthName }} {{ date('Y') }}</p>
    </div>
    <button type="button" id="open-export-modal"
        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
        <i class="mgc_file_download_line me-1"></i> Export
    </button>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">

    <div class="card p-5 flex flex-col items-center gap-2 text-center">
        <div class="w-12 h-12 rounded-xl bg-primary/15 flex items-center justify-center">
            <i class="mgc_group_line text-2xl text-primary"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-500">Celebrants</p>
    </div>

    <div class="card p-5 flex flex-col items-center gap-2 text-center {{ $isCurrentMonth && $stats['today'] > 0 ? 'ring-2 ring-warning/50' : '' }}">
        <div class="w-12 h-12 rounded-xl bg-warning/15 flex items-center justify-center">
            <i class="mgc_star_line text-2xl text-warning"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ $isCurrentMonth ? $stats['today'] : '—' }}
        </p>
        <p class="text-xs text-gray-500">Celebrating Today</p>
    </div>

    <div class="card p-5 flex flex-col items-center gap-2 text-center">
        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
            <i class="mgc_male_line text-2xl text-blue-500"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['male'] }}</p>
        <p class="text-xs text-gray-500">Male</p>
    </div>

    <div class="card p-5 flex flex-col items-center gap-2 text-center">
        <div class="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
            <i class="mgc_female_line text-2xl text-pink-500"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['female'] }}</p>
        <p class="text-xs text-gray-500">Female</p>
    </div>

</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title"><i class="mgc_filter_line me-2"></i>Filter</h5>
    </div>
    <form method="GET" action="{{ route('birthdays.index') }}">
        <div class="flex flex-wrap items-end gap-4 p-6">

            {{-- Month --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Month</label>
                <select name="month" class="form-select w-44">
                    @foreach($monthNames as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- PWD --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">PWD</label>
                <select name="is_pwd" class="form-select w-36">
                    <option value="" {{ $is_pwd === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $is_pwd === '1' ? 'selected' : '' }}>PWD Only</option>
                    <option value="0" {{ $is_pwd === '0' ? 'selected' : '' }}>Non-PWD</option>
                </select>
            </div>

            {{-- Senior --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Senior (60+)</label>
                <select name="senior" class="form-select w-36">
                    <option value="" {{ $senior === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $senior === '1' ? 'selected' : '' }}>Senior Only</option>
                    <option value="0" {{ $senior === '0' ? 'selected' : '' }}>Non-Senior</option>
                </select>
            </div>

            {{-- Solo Parent --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Solo Parent</label>
                <select name="is_soloparents" class="form-select w-36">
                    <option value="" {{ $soloparents === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $soloparents === '1' ? 'selected' : '' }}>Solo Parent Only</option>
                    <option value="0" {{ $soloparents === '0' ? 'selected' : '' }}>Not Solo Parent</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white">
                    <i class="mgc_search_line me-1"></i> Apply
                </button>
                <a href="{{ route('birthdays.index') }}" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    <i class="mgc_close_line me-1"></i> Reset
                </a>
            </div>

        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            {{ $selectedMonthName }} Celebrants
            <span class="ms-2 text-sm font-normal text-gray-400">({{ $citizens->count() }} records)</span>
        </h5>
    </div>

    <div class="overflow-x-auto">
        <div class="min-w-full inline-block align-middle">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-28">Birthday</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-20">Age</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Zone / Area</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tags</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($citizens as $citizen)
                    @php
                        $isToday = $isCurrentMonth && $citizen->bday && $citizen->bday->day === $todayDay;
                    @endphp
                    <tr class="{{ $isToday
                        ? 'bg-warning/5 dark:bg-warning/10'
                        : 'odd:bg-white even:bg-gray-50 hover:bg-gray-100 dark:odd:bg-slate-800 dark:even:bg-slate-700 dark:hover:bg-slate-600' }}">

                        {{-- Birthday --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold {{ $isToday ? 'text-warning' : 'text-gray-700 dark:text-gray-200' }}">
                                    {{ $citizen->bday ? $citizen->bday->format('M d') : '—' }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $citizen->bday ? $citizen->bday->format('Y') : '' }}
                                </span>
                                @if($isToday)
                                    <span class="text-xs font-semibold text-warning bg-warning/10 rounded-full px-1.5 mt-0.5 w-fit">Today</span>
                                @endif
                            </div>
                        </td>

                        {{-- Name --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($citizen->profile)
                                    <img src="{{ asset('storage/' . $citizen->profile) }}"
                                        class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0"
                                        alt="{{ $citizen->fname }}">
                                @else
                                    <div class="w-9 h-9 rounded-full {{ $isToday ? 'bg-warning/20 text-warning' : 'bg-primary/20 text-primary' }} flex items-center justify-center font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $citizen->lname }}, {{ $citizen->fname }}
                                        @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                                        @if($citizen->suffix) <span class="text-gray-400 text-xs">{{ $citizen->suffix }}</span> @endif
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ \App\Models\Setting::instance()->formatCitizenId($citizen->id) }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Age --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $citizen->age }}</span>
                            <span class="text-xs text-gray-400 block">yrs old</span>
                        </td>

                        {{-- Zone --}}
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $citizen->addressZone->description ?? '—' }}
                            @if($citizen->addressZone && $citizen->addressZone->is_subd == 1 && ($citizen->blk || $citizen->lot))
                                <span class="text-xs text-gray-400 block">
                                    @if($citizen->blk) Blk {{ $citizen->blk }} @endif
                                    @if($citizen->lot) Lot {{ $citizen->lot }} @endif
                                    @if($citizen->phase) Ph {{ $citizen->phase }} @endif
                                </span>
                            @elseif($citizen->no || $citizen->street)
                                <span class="text-xs text-gray-400 block">
                                    {{ trim(($citizen->no ?? '') . ' ' . ($citizen->street ?? '')) }}
                                </span>
                            @endif
                        </td>

                        {{-- Contact --}}
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->contact ?: '—' }}
                        </td>

                        {{-- Tags --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @if($citizen->is_pwd)
                                    <span class="text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded px-1.5 py-0.5">PWD</span>
                                @endif
                                @if($citizen->age >= 60)
                                    <span class="text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 rounded px-1.5 py-0.5">Senior</span>
                                @endif
                                @if($citizen->is_soloparents)
                                    <span class="text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 rounded px-1.5 py-0.5">Solo Parent</span>
                                @endif
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400">
                                <i class="mgc_cake_line text-5xl"></i>
                                <p class="text-sm font-medium">No birthday celebrants found</p>
                                <p class="text-xs">Try selecting a different month or adjusting your filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Export Modal --}}
<div id="export-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div id="export-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md flex flex-col pointer-events-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <i class="mgc_file_download_line text-primary text-xl"></i>
                    <h5 class="font-semibold text-gray-800 dark:text-gray-100">Export Birthday List</h5>
                </div>
                <button type="button" id="close-export-modal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="mgc_close_line text-xl"></i>
                </button>
            </div>

            <form id="export-form" method="POST" action="{{ route('birthdays.export') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="is_pwd" value="{{ $is_pwd }}">
                <input type="hidden" name="senior" value="{{ $senior }}">
                <input type="hidden" name="is_soloparents" value="{{ $soloparents }}">

                <div class="px-6 py-5 space-y-4">

                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Month</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedMonthName }} {{ date('Y') }} — {{ $citizens->count() }} celebrants</p>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Format</p>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="format" value="csv" checked class="accent-primary w-4 h-4">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mgc_table_2_line text-green-500 me-1"></i>CSV
                                    <span class="text-xs text-gray-400">(Excel-compatible)</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="format" value="pdf" class="accent-primary w-4 h-4">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mgc_pdf_line text-red-500 me-1"></i>PDF
                                    <span class="text-xs text-gray-400">(landscape A4)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Fields included:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(['Citizen ID', 'Full Name', 'Birthday', 'Age', 'Full Address', 'Contact No'] as $f)
                                <span class="text-xs bg-primary/10 text-primary rounded px-2 py-0.5">{{ $f }}</span>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="cancel-export-modal"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="btn bg-primary text-white">
                        <i class="mgc_file_download_line me-1"></i>
                        <span id="export-submit-label">Export CSV</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection

@section('script')
<script>
window.addEventListener('DOMContentLoaded', () => {
    const modal     = document.getElementById('export-modal');
    const backdrop  = document.getElementById('export-backdrop');
    const openBtn   = document.getElementById('open-export-modal');
    const closeBtn  = document.getElementById('close-export-modal');
    const cancelBtn = document.getElementById('cancel-export-modal');
    const label     = document.getElementById('export-submit-label');

    function openModal()  { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.add('hidden');    document.body.style.overflow = '';       }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    document.querySelectorAll('input[name="format"]').forEach(r => {
        r.addEventListener('change', function () {
            label.textContent = 'Export ' + this.value.toUpperCase();
        });
    });
});
</script>
@endsection
