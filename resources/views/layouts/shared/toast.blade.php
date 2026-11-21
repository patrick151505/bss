{{--
    Toast notifications, top-right, for every action:
      • Session flashes after a save / redirect: success, error, warning, info.
        The kind is read from the message — created / updated / deleted get
        their own title and icon ("Created", "Updated", "Deleted", "Saved").
      • Validation errors (the form came back with problems).
      • Background saves (fetch / XHR, not GET) that fail: no permission,
        session expired, too many tries, server error.
    The page's own green success box for the same message is hidden, so it
    doesn't show twice; red error / yellow warning boxes stay on the page.

    From code:  Toast.success('Saved'), Toast.error('…'), Toast.warning('…'),
                Toast.info('…'), Toast.show('…', 'success', { title, duration }).
    Self-contained (no library). Include once, before </body>.
--}}
@php
    $toastFlashes = [];
    foreach (['success', 'error', 'warning', 'info'] as $k) {
        $v = session($k);
        if (is_string($v) && trim($v) !== '') $toastFlashes[] = ['type' => $k, 'message' => $v];
    }
    $toastErrors = isset($errors) ? $errors->all() : [];
@endphp
<div id="toast-stack" aria-live="polite" aria-atomic="false"></div>
<style>
    #toast-stack {
        position: fixed; top: 76px; right: 16px; z-index: 100001;
        display: flex; flex-direction: column; gap: 10px;
        width: min(380px, calc(100vw - 32px)); pointer-events: none;
    }
    .tst {
        --tst-c: #10b981;
        pointer-events: auto; position: relative; overflow: hidden;
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 36px 14px 12px; border-radius: 12px;
        background: #fff; color: #1f2937;
        border: 1px solid #e5e7eb; border-left: 4px solid var(--tst-c);
        box-shadow: 0 10px 30px -8px rgba(15,23,42,.25);
        font-size: 13px; line-height: 1.4;
        transform: translateX(110%); opacity: 0;
        transition: transform .3s cubic-bezier(.2,.8,.2,1), opacity .3s;
    }
    .tst.is-in  { transform: none; opacity: 1; }
    .tst.is-out { transform: translateX(110%); opacity: 0; }
    .tst-success { --tst-c: #10b981; } .tst-error { --tst-c: #ef4444; }
    .tst-warning { --tst-c: #f59e0b; } .tst-info  { --tst-c: #3073F1; }
    .tst-deleted { --tst-c: #ef4444; }
    .tst-ico {
        width: 32px; height: 32px; border-radius: 9999px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 17px;
        color: var(--tst-c); background: color-mix(in srgb, var(--tst-c) 14%, transparent);
    }
    .tst-body  { min-width: 0; flex: 1; padding-top: 1px; }
    .tst-title { font-weight: 700; font-size: 13px; color: #111827; }
    .tst-msg   { color: #4b5563; margin-top: 1px; word-wrap: break-word; }
    .tst-msg ul { margin: 4px 0 0; padding-left: 16px; list-style: disc; }
    .tst-x {
        position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        background: transparent !important; border: 0; color: #9ca3af; cursor: pointer; font-size: 14px;
    }
    .tst-x:hover { color: #374151; background: #f3f4f6 !important; }
    .tst-bar {
        position: absolute; left: 0; bottom: 0; height: 3px; width: 100%;
        background: var(--tst-c); opacity: .35; transform-origin: left;
    }
    html[data-mode="dark"] .tst { background: #1f2937; color: #e5e7eb; border-color: #374151; border-left-color: var(--tst-c); box-shadow: 0 10px 30px -8px rgba(0,0,0,.6); }
    html[data-mode="dark"] .tst-title { color: #f9fafb; }
    html[data-mode="dark"] .tst-msg   { color: #d1d5db; }
    html[data-mode="dark"] .tst-x:hover { color: #f3f4f6; background: #374151 !important; }
    @media (max-width: 640px) { #toast-stack { top: 12px; right: 12px; left: 12px; width: auto; } }
    @media (prefers-reduced-motion: reduce) { .tst { transition: opacity .2s; transform: none; } .tst.is-out { transform: none; } }
</style>
<script>
(function () {
    var stack = document.getElementById('toast-stack');
    if (!stack || window.Toast) return;

    var MAX = 4;
    var ICONS = { success: 'mgc_check_circle_line', error: 'mgc_close_circle_line', warning: 'mgc_alert_line', info: 'mgc_information_line', deleted: 'mgc_delete_2_line' };
    var TITLES = { success: 'Success', error: 'Something went wrong', warning: 'Heads up', info: 'Notice' };

    // Name the action from the message: "Citizen updated." → Updated.
    function guess(type, msg) {
        if (type !== 'success') return { kind: type, title: TITLES[type] };
        var m = (msg || '').toLowerCase();
        if (/\b(deleted|removed|archived|voided|cancelled|canceled|deactivated|revoked|discarded)\b/.test(m)) return { kind: 'deleted', title: 'Deleted' };
        if (/\b(created|added|registered|recorded|generated|uploaded|issued|imported)\b/.test(m)) return { kind: 'success', title: 'Created' };
        if (/\b(updated|changed|edited|renamed|moved|restored|activated|approved|released|reopened)\b/.test(m)) return { kind: 'success', title: 'Updated' };
        if (/\b(saved|stored)\b/.test(m)) return { kind: 'success', title: 'Saved' };
        return { kind: 'success', title: TITLES.success };
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function dismiss(el) {
        if (!el || el._gone) return;
        el._gone = true;
        clearTimeout(el._t);
        el.classList.remove('is-in'); el.classList.add('is-out');
        setTimeout(function () { el.remove(); }, 320);
    }

    function show(message, type, opts) {
        opts = opts || {};
        type = ICONS[type] && type !== 'deleted' ? type : 'success';
        var g = guess(type, typeof message === 'string' ? message : '');
        var title = opts.title || g.title;
        var duration = opts.duration != null ? opts.duration : (type === 'error' ? 7000 : type === 'warning' ? 6000 : 4500);

        var body = Array.isArray(message)
            ? (message.length === 1 ? esc(message[0]) : '<ul>' + message.slice(0, 5).map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('') + (message.length > 5 ? '<li>…and ' + (message.length - 5) + ' more</li>' : '') + '</ul>')
            : esc(message);

        var el = document.createElement('div');
        el.className = 'tst tst-' + g.kind;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.innerHTML =
            '<div class="tst-ico"><i class="' + ICONS[g.kind] + '"></i></div>' +
            '<div class="tst-body"><div class="tst-title">' + esc(title) + '</div>' +
            (body ? '<div class="tst-msg">' + body + '</div>' : '') + '</div>' +
            '<button type="button" class="tst-x" aria-label="Close"><i class="mgc_close_line"></i></button>' +
            (duration ? '<div class="tst-bar"></div>' : '');
        el.querySelector('.tst-x').addEventListener('click', function () { dismiss(el); });

        stack.appendChild(el);
        while (stack.children.length > MAX) dismiss(stack.firstElementChild);
        requestAnimationFrame(function () { requestAnimationFrame(function () { el.classList.add('is-in'); }); });

        if (duration) {
            // Countdown bar; hovering pauses it so the message can be read.
            var bar = el.querySelector('.tst-bar'), left = duration, started;
            var run = function () {
                started = Date.now();
                bar.style.transition = 'transform ' + left + 'ms linear';
                bar.style.transform = 'scaleX(0)';
                el._t = setTimeout(function () { dismiss(el); }, left);
            };
            el.addEventListener('mouseenter', function () {
                clearTimeout(el._t);
                left -= Date.now() - started;
                var w = bar.getBoundingClientRect().width / el.getBoundingClientRect().width;
                bar.style.transition = 'none'; bar.style.transform = 'scaleX(' + w + ')';
            });
            el.addEventListener('mouseleave', function () { if (!el._gone) run(); });
            requestAnimationFrame(function () { requestAnimationFrame(run); });
        }
        return el;
    }

    window.Toast = {
        show: show,
        success: function (m, o) { return show(m, 'success', o); },
        error:   function (m, o) { return show(m, 'error', o); },
        warning: function (m, o) { return show(m, 'warning', o); },
        info:    function (m, o) { return show(m, 'info', o); },
    };

    // ── Session flashes + validation errors from the last request ─────────
    var flashes = @json($toastFlashes);
    var errors  = @json($toastErrors);

    // Hide the page's own flash box carrying the same message (green/red/yellow/blue panels).
    function hideInlineFlash(msg) {
        var want = msg.trim();
        var nodes = document.querySelectorAll('p, span, div, li');
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            if (n.children.length > 1 || n.closest('#toast-stack')) continue;
            if ((n.textContent || '').trim() !== want) continue;
            var box = n.closest('[class*="bg-success/"], [class*="bg-danger/"], [class*="bg-warning/"], [class*="bg-info/"], [class*="bg-green-"], [class*="bg-red-"], .alert');
            if (box && box !== document.body && !box.querySelector('form, input, select, textarea')) box.style.display = 'none';
        }
    }

    function boot() {
        flashes.forEach(function (f, i) {
            setTimeout(function () { show(f.message, f.type); }, 120 + i * 150);
            // Errors / warnings keep their box on the page too, so they can still be read after the toast goes.
            if (f.type === 'success' || f.type === 'info') hideInlineFlash(f.message);
        });
        if (errors.length) {
            setTimeout(function () {
                show(errors, 'error', { title: errors.length === 1 ? 'Please check the form' : 'Please check the form (' + errors.length + ' problems)' });
            }, 120 + flashes.length * 150);
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();

    // ── Background saves that fail ─────────────────────────────────────────
    // Only non-GET requests, and only problems the page usually can't explain
    // itself (422 form errors are left to the page).
    var FAIL = {
        401: 'You were signed out. Please sign in again.',
        403: 'You don’t have permission to do this.',
        404: 'That record no longer exists. Refresh the page.',
        419: 'Your session expired. Refresh the page and try again.',
        429: 'Too many tries. Wait a moment and try again.',
    };
    var lastFail = 0;
    function failed(method, status) {
        if (!method || /^(GET|HEAD|OPTIONS)$/i.test(method)) return;
        var msg = FAIL[status] || (status >= 500 ? 'The server had a problem saving this. Please try again.' : null);
        if (!msg || Date.now() - lastFail < 1500) return;   // one toast for a burst of failures
        lastFail = Date.now();
        show(msg, 'error', { title: status >= 500 ? 'Not saved' : 'Not allowed' });
    }

    if (window.fetch) {
        var _fetch = window.fetch;
        window.fetch = function (input, init) {
            var method = (init && init.method) || (input && input.method) || 'GET';
            return _fetch.apply(this, arguments).then(function (res) {
                if (!res.ok) failed(method, res.status);
                return res;
            });
        };
    }
    if (window.XMLHttpRequest) {
        var _open = XMLHttpRequest.prototype.open, _send = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (method) { this._tstMethod = method; return _open.apply(this, arguments); };
        XMLHttpRequest.prototype.send = function () {
            var xhr = this;
            xhr.addEventListener('loadend', function () { if (xhr.status >= 400) failed(xhr._tstMethod, xhr.status); });
            return _send.apply(this, arguments);
        };
    }
})();
</script>
