{{--
    Top-of-page loading bar, shown for every action:
      • clicking a link to another page, submitting a form, any page leave (form.submit(), location.href…)
      • background requests (fetch / XMLHttpRequest) that take longer than a moment — saves, searches, modals.
    Self-contained (no library). Include right after <body>.

    Skip it for one link or form with data-no-loader. From code: TopLoader.start() / TopLoader.done().
--}}
<div id="top-loader" aria-hidden="true"><div id="top-loader-bar"></div></div>
<style>
    #top-loader { position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 100000; pointer-events: none; opacity: 0; transition: opacity .3s ease .15s; }
    #top-loader.is-active { opacity: 1; transition-delay: 0s; }
    #top-loader-bar {
        height: 100%; width: 0; background: #3073F1;
        box-shadow: 0 0 8px rgba(48,115,241,.7), 0 0 3px rgba(48,115,241,.7);
        transition: width .25s ease;
        border-radius: 0 2px 2px 0;
    }
    @media (prefers-reduced-motion: reduce) { #top-loader-bar { transition: none; } }
</style>
<script>
(function () {
    var root = document.getElementById('top-loader');
    var bar  = document.getElementById('top-loader-bar');
    if (!root || !bar) return;

    var width = 0, trickle = null, safety = null, active = false;
    var pending = 0, showTimer = null;
    var SHOW_AFTER = 250;          // background requests faster than this don't flash the bar

    function set(w) { width = w; bar.style.width = w + '%'; }

    function start() {
        if (!active) {
            active = true;
            bar.style.transition = 'none'; set(0); void bar.offsetWidth; bar.style.transition = '';
            root.classList.add('is-active');
            set(12);
        }
        clearInterval(trickle);
        // Creep towards 90% — slower the closer it gets — until done() is called.
        trickle = setInterval(function () { if (width < 90) set(width + (90 - width) * 0.08); }, 300);
    }

    function done() {
        clearTimeout(safety); safety = null;
        if (!active) return;
        clearInterval(trickle);
        set(100);
        setTimeout(function () {
            root.classList.remove('is-active');
            setTimeout(function () { if (!root.classList.contains('is-active')) { active = false; set(0); } }, 450);
        }, 200);
    }

    // A page leave that never happens (a file download, a cancelled navigation) must not leave the bar running.
    function startNavigation() {
        start();
        clearTimeout(safety);
        safety = setTimeout(function () { safety = null; done(); }, 12000);
    }

    window.TopLoader = { start: start, done: done };

    // ── Links ──────────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.hasAttribute('download') || a.hasAttribute('data-no-loader')) return;
        if (a.target && a.target !== '_self') return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
        var url;
        try { url = new URL(a.href, location.href); } catch (err) { return; }
        if (url.origin !== location.origin) return;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return;   // same-page anchor
        // Let the page's own handlers run first (a modal opener calls preventDefault).
        setTimeout(function () { if (!e.defaultPrevented) startNavigation(); }, 0);
    });

    // ── Forms ──────────────────────────────────────────────────────────────
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f || f.hasAttribute('data-no-loader')) return;
        if (f.target && f.target !== '_self') return;
        // AJAX forms call preventDefault — their request shows the bar instead.
        setTimeout(function () { if (!e.defaultPrevented) startNavigation(); }, 0);
    });

    // form.submit(), location.href = …, Swal-confirmed deletes, etc.
    window.addEventListener('beforeunload', startNavigation);
    // Back/forward cache: the page comes back with the bar still running.
    window.addEventListener('pageshow', function (e) { if (e.persisted) { pending = 0; done(); } });

    // ── Background requests ────────────────────────────────────────────────
    function reqStart() {
        pending++;
        if (pending === 1 && !showTimer) {
            showTimer = setTimeout(function () { showTimer = null; if (pending > 0) start(); }, SHOW_AFTER);
        }
    }
    function reqEnd() {
        pending = Math.max(0, pending - 1);
        if (pending === 0) {
            clearTimeout(showTimer); showTimer = null;
            if (!safety) done();   // a page leave in progress keeps the bar until the new page loads
        }
    }

    if (window.fetch) {
        var origFetch = window.fetch;
        window.fetch = function () {
            reqStart();
            return origFetch.apply(this, arguments).then(
                function (r) { reqEnd(); return r; },
                function (err) { reqEnd(); throw err; }
            );
        };
    }

    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function () {
        reqStart();
        this.addEventListener('loadend', reqEnd, { once: true });
        return origSend.apply(this, arguments);
    };
})();
</script>
