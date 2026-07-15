@extends('layouts.vertical', [
    'title'         => 'New Event',
    'sub_title'     => 'Events',
    'sub_title_url' => route('events.index'),
    'tagline'       => 'Create a new barangay event',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- ── Main fields ── --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Basic Info --}}
        <div class="card">
            <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">1</span>
                <span class="text-sm font-semibold text-primary uppercase tracking-wide">Event Details</span>
            </div>
            <div class="p-5 space-y-4">

                <div>
                    <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">
                        Event Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}"
                        class="form-input w-full @error('title') border-danger @enderror"
                        placeholder="e.g. Barangay General Assembly" required>
                    @error('title')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Description</label>
                    <textarea name="description" rows="4" class="form-input w-full"
                        placeholder="Details about the event…">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Category</label>
                        <input type="text" name="category" value="{{ old('category') }}"
                            class="form-input w-full" placeholder="e.g. Assembly, Sports, Health…">
                    </div>
                    <div>
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Venue</label>
                        <input type="text" name="venue" value="{{ old('venue') }}"
                            class="form-input w-full" placeholder="e.g. Barangay Hall">
                    </div>
                </div>

            </div>
        </div>

        {{-- Schedule --}}
        <div class="card">
            <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">2</span>
                <span class="text-sm font-semibold text-primary uppercase tracking-wide">Schedule</span>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">
                            Event Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="event_date" value="{{ old('event_date') }}"
                            class="form-input w-full @error('event_date') border-danger @enderror" required>
                        @error('event_date')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Start Time</label>
                        <input type="time" name="event_start" value="{{ old('event_start') }}" class="form-input w-full">
                    </div>
                    <div>
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">
                            End Time
                            <span class="text-xs text-gray-400 font-normal">— used for auto-close</span>
                        </label>
                        <input type="time" name="event_end" value="{{ old('event_end') }}" class="form-input w-full">
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Right sidebar ── --}}
    <div class="space-y-5">

        {{-- Cover photo --}}
        <div class="card">
            <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">3</span>
                <span class="text-sm font-semibold text-primary uppercase tracking-wide">Cover Photo</span>
            </div>
            <div class="p-5">
                <div id="cover-preview" class="hidden w-full h-40 rounded-xl overflow-hidden mb-3 bg-gray-100 dark:bg-gray-700">
                    <img id="cover-img" src="" class="w-full h-full object-cover">
                </div>
                <div id="cover-placeholder" class="w-full h-40 rounded-xl bg-gray-100 dark:bg-gray-700 flex flex-col items-center justify-center mb-3 text-gray-400">
                    <i class="mgc_pic_line text-3xl mb-1"></i>
                    <p class="text-xs">No image selected</p>
                </div>
                <input type="file" name="cover_photo" id="cover-input" accept="image/*" class="form-input w-full text-sm">
            </div>
        </div>

        {{-- Raffle settings --}}
        <div class="card">
            <div class="flex items-center gap-3 px-5 py-3 bg-warning/5 border-b border-warning/10">
                <span class="w-6 h-6 rounded-full bg-warning text-white text-xs flex items-center justify-center shrink-0">
                    <i class="mgc_star_line text-xs"></i>
                </span>
                <span class="text-sm font-semibold text-warning uppercase tracking-wide">Raffle Settings</span>
            </div>
            <div class="p-5 space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="raffle_enabled" id="raffle-enabled" value="1"
                        class="form-checkbox mt-0.5" {{ old('raffle_enabled') ? 'checked' : '' }}>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Enable Raffle</p>
                        <p class="text-xs text-gray-400 mt-0.5">Show spin wheel and winner draw on event page</p>
                    </div>
                </label>

                <div id="raffle-options" class="{{ old('raffle_enabled') ? '' : 'hidden' }} pl-7 pt-1 border-t border-gray-100 dark:border-gray-700">
                    <label class="flex items-start gap-3 cursor-pointer mt-3">
                        <input type="checkbox" name="allow_winner_repeat" value="1"
                            class="form-checkbox mt-0.5" {{ old('allow_winner_repeat') ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Allow Winner Repeat</p>
                            <p class="text-xs text-gray-400 mt-0.5">Same citizen can be drawn more than once</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-3">
            <a href="{{ route('events.index') }}" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white flex-1 justify-center">
                Cancel
            </a>
            <button type="submit" class="btn bg-primary text-white flex-1 justify-center gap-2">
                <i class="mgc_check_line"></i> Create Event
            </button>
        </div>

    </div>
</div>

</form>

@endsection

@push('inline-scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Cover photo preview
    document.getElementById('cover-input').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('cover-img').src = e.target.result;
            document.getElementById('cover-preview').classList.remove('hidden');
            document.getElementById('cover-placeholder').classList.add('hidden');
        };
        reader.readAsDataURL(file);
    });

    // Raffle toggle
    document.getElementById('raffle-enabled').addEventListener('change', function () {
        document.getElementById('raffle-options').classList.toggle('hidden', !this.checked);
    });
});
</script>
@endpush
