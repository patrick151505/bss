{{--
    Paper-template styles for standalone print pages.
    Params: $paper (array from App\Support\Paper, or null) and $sheet (CSS
    selector of the page's sheet element, e.g. ".sheet").

    Screen: the sheet is drawn at the paper's size with the letterhead behind
    it and the template's margins as padding.

    Print: @page has NO margin (Chrome clips anything painted in page margins,
    which is exactly where a letterhead's header/footer sit). Instead:
      • the letterhead is a fixed <img> (partials.paper.background) covering
        the full sheet — fixed elements repeat on every printed page;
      • the top/bottom margins are spacer rows in a table's <thead>/<tfoot>
        (partials.paper.open / close) — Chrome repeats those on every page,
        so content clears the letterhead header and footer on page 2+ too;
      • left/right margins are plain padding on the sheet.
    An <img> is used rather than a CSS background because images always print,
    even with "Background graphics" turned off in the print dialog.
--}}
@if($paper)
<style>
    @page { size: {{ $paper['w'] }}in {{ $paper['h'] }}in; margin: 0; }

    /* The paper carries its own letterhead — hide the built-in one. */
    .builtin-letterhead { display: none !important; }
    /* The template's Content Padding is the exact content edge: the first
       element must not add its own top margin on top of it. */
    .builtin-letterhead + * { margin-top: 0 !important; }
    .paper-bg { display: none; }
    /* The frame is layout only — neutralise any page-level table styling
       (e.g. "table { font-size }", "thead tr { background }") so it adds
       nothing visible and leaves the content's own sizes alone. */
    .paper-frame { width: 100% !important; border-collapse: collapse !important; border: 0 !important;
                   margin: 0 !important; font-size: inherit !important; background: none !important; }
    .paper-frame > thead > tr, .paper-frame > tfoot > tr, .paper-frame > tbody > tr { background: none !important; }
    .paper-frame > thead > tr > td,
    .paper-frame > tfoot > tr > td,
    .paper-frame > tbody > tr > td { padding: 0 !important; border: 0 !important; background: none !important;
                                      text-align: inherit !important; vertical-align: top !important; }
    /* Absolutely-positioned extras (stamps, badges) measure from the content
       area, i.e. inside the template's Content Padding, not the sheet edge. */
    .paper-frame > tbody > tr > td { position: relative; }
    .paper-frame > tbody > tr { break-inside: auto !important; page-break-inside: auto !important; }

    @media screen {
        {{ $sheet }} {
            width: {{ $paper['w'] }}in !important;
            max-width: none !important;
            min-height: {{ $paper['h'] }}in !important;
            padding: {{ $paper['top'] }}px {{ $paper['right'] }}px {{ $paper['bottom'] }}px {{ $paper['left'] }}px !important;
            {{-- Raw output: {{ }} would turn the quotes into &#039;, which is invalid
                 inside <style>. The URL is stripped of anything that could end the
                 CSS string. --}}
            {{-- repeat-y: one letterhead per on-screen page (see the paginator below) --}}
            background: #fff {!! $paper['bg_url'] ? 'url("' . str_replace(['"', '\\', "\n", "\r", '<', '>'], '', $paper['bg_url']) . '")' : '' !!} top left / {{ $paper['w'] }}in {{ $paper['h'] }}in repeat-y !important;
        }
        .paper-spacer { display: none; }   /* the sheet's padding does this on screen */
    }

    /* Screen-only page-break spacers added by the paginator. */
    @media print { .pg-spacer { display: none !important; } }

    @media print {
        html, body { background: transparent !important; margin: 0 !important; padding: 0 !important; }
        {{ $sheet }} {
            width: auto !important; min-height: 0 !important; margin: 0 !important;
            padding: 0 {{ $paper['right'] }}px 0 {{ $paper['left'] }}px !important;
            background: none !important; box-shadow: none !important;
        }
        .paper-bg {
            display: block;
            position: fixed; top: 0; left: 0;
            width: {{ $paper['w'] }}in; height: {{ $paper['h'] }}in;
            z-index: -1;
        }
        .paper-spacer.top    { height: {{ $paper['top'] }}px; }
        .paper-spacer.bottom { height: {{ $paper['bottom'] }}px; }
    }
</style>
<script>
/*
 * On-screen pagination for the paper preview.
 * Printing paginates by itself (the letterhead and margins repeat per page),
 * but the screen preview is one tall sheet, so long content would run over
 * the letterhead's footer. This walks the content top-down and, whenever a
 * block (or a table row) would cross a page's bottom margin, inserts a
 * screen-only spacer that pushes it below the next page's header margin.
 * Spacers are hidden in print, so the printout is unaffected.
 */
(function () {
    const SHEET  = @json($sheet);
    const PAGE_H = {{ $paper['h'] }} * 96;          // px per page at 96 dpi
    const TOP    = {{ $paper['top'] }};
    const BOTTOM = {{ $paper['bottom'] }};
    const USABLE = PAGE_H - TOP - BOTTOM;

    function visible(el) { return el.offsetParent !== null && el.getBoundingClientRect().height > 0; }

    // Blocks that may move as a whole. Anything taller than a page is opened
    // up: tables by their body rows, other elements by their children.
    function units(container, out) {
        for (const el of container.children) {
            if (!visible(el) || el.classList.contains('pg-spacer')) continue;
            const h = el.getBoundingClientRect().height;
            if (h <= USABLE) { out.push(el); continue; }
            if (el.tagName === 'TABLE') {
                el.querySelectorAll(':scope > tbody > tr').forEach(tr => out.push(tr));
            } else if (el.children.length) {
                units(el, out);
            } else {
                out.push(el);
            }
        }
        return out;
    }

    function spacer(before, gap) {
        let s;
        if (before.tagName === 'TR') {
            s = document.createElement('tr');
            s.innerHTML = '<td colspan="50" style="height:' + gap + 'px;padding:0;border:0;background:transparent"></td>';
        } else {
            s = document.createElement('div');
            s.style.height = gap + 'px';
        }
        s.className = 'pg-spacer';
        before.parentNode.insertBefore(s, before);
    }

    function paginate() {
        if (window.matchMedia('print').matches) return;
        document.querySelectorAll('.pg-spacer').forEach(n => n.remove());
        const sheet = document.querySelector(SHEET);
        const root  = sheet && (sheet.querySelector('.paper-frame > tbody > tr > td') || sheet);
        if (!root) return;
        sheet.style.removeProperty('min-height');

        for (const el of units(root, [])) {
            const sTop = sheet.getBoundingClientRect().top;
            const r    = el.getBoundingClientRect();
            const y0   = r.top - sTop, y1 = y0 + r.height;
            const page = Math.floor(y0 / PAGE_H);
            const limit = page * PAGE_H + PAGE_H - BOTTOM;   // where this page's footer margin starts
            if (y1 > limit + 0.5 && r.height <= USABLE) {
                spacer(el, Math.ceil((page + 1) * PAGE_H + TOP - y0));
            }
        }

        // Whole pages, so each on-screen page shows its full letterhead.
        const pages = Math.max(1, Math.ceil((sheet.scrollHeight - 1) / PAGE_H));
        sheet.style.setProperty('min-height', (pages * PAGE_H) + 'px', 'important');
    }

    window.addEventListener('load', paginate);
    window.addEventListener('resize', () => { clearTimeout(window.__pgT); window.__pgT = setTimeout(paginate, 150); });
})();
</script>
@endif
