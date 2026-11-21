{{-- Standalone page for the public registration screens (no login, no sidebar). --}}
@php
    $brgy = \App\Models\Setting::instance();
    $logo = $brgy->logo ? asset('storage/' . preg_replace('#^public/#', '', $brgy->logo)) : null;
    // Opened from the barangay hall kiosk: offer a way back, and return there when left idle.
    $onKiosk = \App\Models\Kiosk::onKiosk(request());
@endphp
<!DOCTYPE html>
<html lang="en" data-mode="light">
<head>
    @include('layouts.shared/title-meta', ['title' => $pageTitle ?? 'Citizen Registration'])
    {{-- Own theme, light by default: residents' pages don't follow the staff theme (config.js is not loaded).
         Set before the CSS paints so there is no dark flash. --}}
    <script>
        (function () {
            var mode = 'light';
            try { mode = localStorage.getItem('reg_theme') === 'dark' ? 'dark' : 'light'; } catch (e) {}
            document.documentElement.setAttribute('data-mode', mode);
        })();
    </script>
    @vite(['resources/scss/app.scss', 'resources/scss/icons.scss'])
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="bg-gray-100 dark:bg-gray-900">
<div class="min-h-screen py-8 px-4">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            @if($logo)<img src="{{ $logo }}" alt="" class="w-14 h-14 object-contain shrink-0">@endif
            <div class="flex-1 min-w-0">
                <p class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $brgy->barangay_name ?: 'Barangay' }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ collect([$brgy->municipality, $brgy->province])->filter()->implode(', ') }}
                </p>
            </div>
            @if($onKiosk)
            <a href="{{ route('kiosk.home') }}"
               class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-full border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 font-semibold text-base">
                <i class="mgc_home_3_line text-xl"></i><span>{{ $kioskHomeLabel ?? 'Cancel' }}</span>
            </a>
            @endif
            {{-- Light / dark switch --}}
            <button type="button" id="theme-toggle"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-full border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 font-semibold text-base">
                <i id="theme-icon" class="mgc_moon_line text-xl"></i>
                <span id="theme-label">Dark mode</span>
            </button>
        </div>

        @yield('body')
    </div>
</div>
<script>
(function () {
    var html = document.documentElement;
    function paint() {
        var dark = html.getAttribute('data-mode') === 'dark';
        // The button offers the other mode.
        document.getElementById('theme-icon').className = (dark ? 'mgc_sun_line' : 'mgc_moon_line') + ' text-xl';
        document.getElementById('theme-label').textContent = dark ? 'Light mode' : 'Dark mode';
    }
    document.getElementById('theme-toggle').addEventListener('click', function () {
        var next = html.getAttribute('data-mode') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-mode', next);
        try { localStorage.setItem('reg_theme', next); } catch (e) {}
        paint();
    });
    paint();
})();
</script>
@if($onKiosk)
<script>
// Kiosk: nobody touching the screen for {{ $kioskIdle ?? 180 }} seconds → back to the kiosk start screen.
(function () {
    var last = Date.now();
    ['pointerdown', 'keydown', 'input', 'scroll'].forEach(function (ev) {
        document.addEventListener(ev, function () { last = Date.now(); }, { capture: true, passive: true });
    });
    setInterval(function () {
        if (Date.now() - last > {{ ($kioskIdle ?? 180) * 1000 }}) location.href = @json(route('kiosk.home'));
    }, 1000);
})();
</script>
@endif
@stack('page-scripts')
</body>
</html>
