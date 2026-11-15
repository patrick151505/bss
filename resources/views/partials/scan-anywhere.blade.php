{{--
    Handheld QR/barcode scanner support that works wherever focus is on the page.

    listenForScans({ skip, onScan }):
      skip    — an element that already handles scans itself (optional); keys typed there are ignored
      onScan  — called with the scanned code

    A scan is several keys in a row arriving < 50 ms apart (people don't type that fast),
    usually ending with Enter (scanners without Enter are caught after 150 ms of silence).
    The first few keys of a scan may already have landed in the focused text field before
    the scan can be recognised; they are removed again. Enter at the end of a scan never
    reaches the page (so it can't click a focused button). Rich-text editors are left alone.
--}}
<script>
window.listenForScans = window.listenForScans || function ({ skip = null, onScan }) {
    const FAST = 50, IDLE = 150, MIN_LEN = 8, HOLD_AFTER = 4;
    let buf = '', last = 0, timer = null, field = null, typedIntoField = 0;

    const isTextField = el => el && (el.tagName === 'TEXTAREA' ||
        (el.tagName === 'INPUT' && /^(text|search|number|tel|email|url|)$/i.test(el.type)));

    function reset() { buf = ''; field = null; typedIntoField = 0; clearTimeout(timer); }

    function finish() {
        const code = buf.trim(), f = field, typed = typedIntoField;
        reset();
        if (code.length < MIN_LEN) return;
        // Take back the characters that reached the focused field before the scan was recognised.
        if (f && typed > 0 && typeof f.selectionStart === 'number') {
            const end = f.selectionStart, start = end - typed;
            if (start >= 0 && f.value.slice(start, end) === code.slice(0, typed)) {
                f.value = f.value.slice(0, start) + f.value.slice(end);
                f.setSelectionRange(start, start);
                f.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
        if (f) f.blur();
        onScan(code);
    }

    document.addEventListener('keydown', function (e) {
        const t = e.target;
        if ((skip && t === skip) || (t && t.isContentEditable)) return;
        if (e.ctrlKey || e.altKey || e.metaKey) return;

        // When the key was pressed, not when we got to handle it: if the page is busy
        // (e.g. redrawing a table) keys queue up, and handling time would make a scan
        // look like slow typing.
        const now = e.timeStamp || performance.now(), gap = now - last;
        last = now;

        if (e.key === 'Enter') {
            if (buf.length >= MIN_LEN && gap < 200) {      // end of a scan: keep Enter away from the page
                e.preventDefault();
                e.stopPropagation();
                finish();
            } else {
                reset();
            }
            return;
        }
        if (e.key.length !== 1) return;

        if (gap > FAST || !buf) {                          // could be the start of a scan — let it through for now
            reset();
            buf = e.key;
            field = isTextField(t) ? t : null;
            typedIntoField = field ? 1 : 0;
        } else {
            buf += e.key;
            if (buf.length > HOLD_AFTER) {
                e.preventDefault();                        // clearly a scanner: keep the rest out of the field
            } else if (field) {
                typedIntoField++;
            }
        }
        clearTimeout(timer);
        let due = performance.now() + IDLE;
        timer = setTimeout(function idle() {                // scanners without Enter: end after a short silence
            // If this timer runs late, the page was busy and more keys (or the Enter)
            // may still be queued — wait once more instead of cutting the scan short.
            const t = performance.now();
            const busy = t - due > 40 || navigator.scheduling?.isInputPending?.() || t - last < IDLE;
            if (busy) { due = t + IDLE; timer = setTimeout(idle, IDLE); return; }
            if (buf.length >= MIN_LEN) finish(); else reset();
        }, IDLE);
    }, true);
};
</script>
