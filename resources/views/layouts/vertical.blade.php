<!DOCTYPE html>
<html lang="en" data-sidenav-view="{{ $sidenav ?? 'hover' }}">

<head>
    @include('layouts.shared/title-meta', ['title' => $title])
    @yield('css')
    @include('layouts.shared/head-css')
</head>

<body>

    <div class="flex wrapper">

        @include('layouts.shared/sidebar')

        <div class="page-content">

            @include('layouts.shared/topbar')

            <main class="flex-grow p-6">

                @include('layouts.shared/page-title', [
                    'title'         => $title,
                    'sub_title'     => $sub_title ?? null,
                    'tagline'       => $tagline      ?? null,
                    'breadcrumbs'   => $breadcrumbs  ?? null,
                    'sub_title_url' => $sub_title_url ?? null,
                ])

                {{-- Skeleton shown while page hydrates, swapped out by JS --}}
                <div id="page-skeleton" class="animate-pulse">
                    @stack('skeleton')
                </div>

                <div id="page-content" class="hidden">
                    @yield('content')
                </div>

            </main>

            @include('layouts.shared/footer')

        </div>

    </div>

    @include('layouts.shared/customizer')

    @include('layouts.shared/footer-scripts')

    @vite(['resources/js/app.js'])

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('inline-scripts')

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var skeleton = document.getElementById('page-skeleton');
        var content  = document.getElementById('page-content');
        if (skeleton) skeleton.classList.add('hidden');
        if (content)  content.classList.remove('hidden');
    });
    </script>

    {{-- Peso amount-in-words popover — auto-attaches to all number inputs with step="0.01" --}}
    <script>
    (function () {
        const ones  = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                       'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                       'Seventeen','Eighteen','Nineteen'];
        const tens  = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

        function toWords(n) {
            n = Math.floor(Math.abs(n));
            if (n === 0) return 'Zero';
            if (n < 20)  return ones[n];
            if (n < 100) return tens[Math.floor(n/10)] + (n % 10 ? ' ' + ones[n % 10] : '');
            if (n < 1000)
                return ones[Math.floor(n/100)] + ' Hundred' + (n % 100 ? ' ' + toWords(n % 100) : '');
            if (n < 1000000)
                return toWords(Math.floor(n/1000)) + ' Thousand' + (n % 1000 ? ' ' + toWords(n % 1000) : '');
            if (n < 1000000000)
                return toWords(Math.floor(n/1000000)) + ' Million' + (n % 1000000 ? ' ' + toWords(n % 1000000) : '');
            return toWords(Math.floor(n/1000000000)) + ' Billion' + (n % 1000000000 ? ' ' + toWords(n % 1000000000) : '');
        }

        function pesoWords(value) {
            const num   = parseFloat(value);
            if (isNaN(num) || value === '') return null;
            const whole = Math.floor(Math.abs(num));
            const cents = Math.round((Math.abs(num) - whole) * 100);
            let words   = (num < 0 ? 'Negative ' : '') + toWords(whole) + ' Peso' + (whole !== 1 ? 's' : '');
            if (cents > 0) words += ' and ' + toWords(cents) + ' Centavo' + (cents !== 1 ? 's' : '');
            return words;
        }

        // Popover element (singleton)
        const tip = document.createElement('div');
        tip.id    = 'peso-tip';
        tip.style.cssText = [
            'position:fixed','z-index:9999','background:#1e293b','color:#f1f5f9',
            'font-size:11px','padding:4px 10px','border-radius:6px','pointer-events:none',
            'white-space:nowrap','display:none','box-shadow:0 2px 8px rgba(0,0,0,.35)',
            'max-width:320px','white-space:normal','line-height:1.4'
        ].join(';');
        document.body.appendChild(tip);

        function isVisible(el) {
            // Walk up the DOM — if any ancestor is hidden, the input is not visible
            while (el && el !== document.body) {
                const s = window.getComputedStyle(el);
                if (s.display === 'none' || s.visibility === 'hidden' || s.opacity === '0') return false;
                el = el.parentElement;
            }
            return true;
        }

        function showTip(input) {
            if (!isVisible(input)) { tip.style.display = 'none'; return; }
            const words = pesoWords(input.value);
            if (!words) { tip.style.display = 'none'; return; }
            tip.textContent = '₱ ' + words;
            tip.style.display = 'block';
            moveTip(input);
        }

        function moveTip(input) {
            const r = input.getBoundingClientRect();
            let top = r.top - tip.offsetHeight - 6;
            if (top < 4) top = r.bottom + 6;
            let left = r.left;
            if (left + tip.offsetWidth > window.innerWidth - 8)
                left = window.innerWidth - tip.offsetWidth - 8;
            tip.style.top  = top + 'px';
            tip.style.left = left + 'px';
        }

        function hideTip() { tip.style.display = 'none'; }

        // Hide tip whenever any modal closes (class toggled on overlay div)
        const modalObserver = new MutationObserver(mutations => {
            mutations.forEach(m => {
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    const el = m.target;
                    if (el.classList.contains('hidden')) hideTip();
                }
            });
        });
        document.querySelectorAll('[id$="-modal"]').forEach(el =>
            modalObserver.observe(el, { attributes: true })
        );

        // Attach to all current and future number inputs with step="0.01"
        function attach(input) {
            if (input._pesoTip) return;
            input._pesoTip = true;
            input.addEventListener('input',  () => showTip(input));
            input.addEventListener('focus',  () => showTip(input));
            input.addEventListener('blur',   hideTip);
            input.addEventListener('scroll', () => moveTip(input), true);
        }

        function attachAll() {
            document.querySelectorAll('input[type="number"][step="0.01"]').forEach(attach);
        }

        document.addEventListener('DOMContentLoaded', attachAll);

        // Also catch dynamically added inputs (modals, etc.)
        const obs = new MutationObserver(attachAll);
        obs.observe(document.body, { childList: true, subtree: true });
    })();
    </script>

</body>


</html>
