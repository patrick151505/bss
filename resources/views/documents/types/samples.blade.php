@extends('layouts.vertical', [
    'title'         => 'Template Samples',
    'sub_title'     => 'Documents',
    'sub_title_url' => route('documents.types.index'),
    'tagline'       => 'Ready-made certificate templates — copy and paste into the editor.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('css')
<style>
/* Mini certificate preview — mirrors the editor/print document typography so
   these samples look like the real output (placeholders show as literal text). */
.sample-paper {
    font-family: "Times New Roman", Times, serif;
    font-size: 13px;
    line-height: 1.7;
    color: #111827;
}
.sample-paper p { margin: 0 0 5px 0; line-height: 1.4; }
.sample-paper h1 { font-size: 1.6em;  font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
.sample-paper h2 { font-size: 1.35em; font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
.sample-paper h3 { font-size: 1.17em; font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
.sample-paper h4 { font-size: 1em;    font-weight: bold; margin: 0.4em 0; line-height: 1.2; }
.sample-paper ul { list-style: disc;    margin: 0 0 10px 0; padding-left: 32px; }
.sample-paper ol { list-style: decimal; margin: 0 0 10px 0; padding-left: 32px; }
.sample-paper li { margin: 0; }
.sample-paper strong, .sample-paper b { font-weight: bold; }
.sample-paper em, .sample-paper i { font-style: italic; }
.sample-paper u { text-decoration: underline; }
.sample-paper hr { border: 0; border-top: 1px solid #999; margin: 10px 0; }
.sample-paper table { border-collapse: collapse; width: 100%; margin: 8px 0; }
.sample-paper td, .sample-paper th { border: 1px solid #d1d5db; padding: 3px 0px; vertical-align: top; }
.sample-paper th { background: #f3f4f6; font-weight: 600; }
.sample-paper table.no-border,
.sample-paper table.no-border td,
.sample-paper table.no-border th { border: none; background: transparent; }
.sample-paper table.no-border td.border,
.sample-paper table.no-border th.border,
.sample-paper td.border,
.sample-paper th.border { border-bottom: 1px solid #000; }
/* Photo placeholders render as tiny gray boxes in the preview */
.sample-paper img { max-width: 100%; }
</style>
@endsection

@section('content')

{{-- How to use --}}
<div class="mb-5 rounded-xl border border-primary/30 bg-primary/5 p-4">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
            <i class="mgc_information_line text-primary"></i>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-300">
            <p><strong>Snippets</strong> are small formatting how-tos (e.g. borderless vs bordered table). <strong>Templates</strong> are full certificates. Click <strong>Copy</strong> on any card, then paste (Ctrl&nbsp;+&nbsp;V) directly into the editor — formatting is kept.</p>
        </div>
    </div>
</div>

{{-- Tabs --}}
<div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 mb-5">
    <button type="button" id="tab-btn-snippets" onclick="showSamplesTab('snippets')"
            class="samples-tab px-4 py-2 text-sm font-medium border-b-2 -mb-px flex items-center gap-1.5">
        <i class="mgc_code_line"></i> Snippets
        <span class="text-xs text-gray-400">({{ count($snippets) }})</span>
    </button>
    <button type="button" id="tab-btn-templates" onclick="showSamplesTab('templates')"
            class="samples-tab px-4 py-2 text-sm font-medium border-b-2 -mb-px flex items-center gap-1.5">
        <i class="mgc_document_2_line"></i> Templates
        <span class="text-xs text-gray-400">({{ count($samples) }})</span>
    </button>
</div>

{{-- Snippets tab (default) --}}
<div id="tab-snippets" class="grid grid-cols-12 gap-5">
    @foreach($snippets as $i => $item)
        @include('documents.types._sample_card', ['item' => $item, 'uid' => 'sn'.$i])
    @endforeach
</div>

{{-- Templates tab --}}
<div id="tab-templates" class="hidden grid grid-cols-12 gap-5">
    @foreach($samples as $i => $item)
        @include('documents.types._sample_card', ['item' => $item, 'uid' => 'tp'.$i])
    @endforeach
</div>

@endsection

@push('inline-scripts')
<script>
// ── Tabs ───────────────────────────────────────────────────────────────
function showSamplesTab(tab) {
    const isSnippets = tab === 'snippets';
    // Set display directly to avoid the Tailwind hidden/grid ordering gotcha.
    document.getElementById('tab-snippets').style.display  = isSnippets ? 'grid' : 'none';
    document.getElementById('tab-templates').style.display = isSnippets ? 'none' : 'grid';

    [['tab-btn-snippets', isSnippets], ['tab-btn-templates', !isSnippets]].forEach(([id, active]) => {
        const b = document.getElementById(id);
        b.classList.toggle('border-primary', active);
        b.classList.toggle('text-primary', active);
        b.classList.toggle('border-transparent', !active);
        b.classList.toggle('text-gray-500', !active);
    });
}

// Toggle between the rendered preview and the raw HTML for a sample.
function toggleSampleView(i, btn) {
    const preview = document.getElementById('sample-preview-' + i);
    const code    = document.getElementById('sample-code-wrap-' + i);
    const label   = btn.querySelector('span');
    const icon    = btn.querySelector('i');
    const showingCode = !code.classList.contains('hidden');

    if (showingCode) {
        code.classList.add('hidden');
        preview.classList.remove('hidden');
        label.textContent = 'View HTML';
        icon.className = 'mgc_code_line';
    } else {
        preview.classList.add('hidden');
        code.classList.remove('hidden');
        label.textContent = 'View Preview';
        icon.className = 'mgc_eye_2_line';
    }
}

function copySample(i, btn) {
    // The raw template HTML (with placeholder tags intact) is the source.
    const code = document.getElementById('sample-code-' + i);
    const html = code ? code.textContent : '';

    const done = () => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="mgc_check_line"></i> Copied!';
        btn.classList.add('bg-success');
        btn.classList.remove('bg-primary');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.add('bg-primary');
            btn.classList.remove('bg-success');
        }, 1500);
    };

    // Copy as RICH TEXT so pasting into the visual editor keeps the formatting
    // (bold, headings, tables, alignment) — no need to use the code view.
    // We put the markup on the clipboard as text/html, and the same markup as
    // text/plain so a code-view paste still works.
    if (navigator.clipboard && window.ClipboardItem && window.isSecureContext) {
        const item = new ClipboardItem({
            'text/html':  new Blob([html], { type: 'text/html' }),
            'text/plain': new Blob([html], { type: 'text/plain' }),
        });
        navigator.clipboard.write([item]).then(done).catch(() => copyRichFallback(html, done));
    } else {
        copyRichFallback(html, done);
    }
}

// Fallback: select a rendered HTML fragment and use execCommand('copy'), which
// captures rich text on the clipboard even without the async Clipboard API
// (works on plain http:// origins like localhost).
function copyRichFallback(html, done) {
    const holder = document.createElement('div');
    holder.innerHTML = html;
    holder.setAttribute('contenteditable', 'true');
    holder.style.position = 'fixed';
    holder.style.left = '-9999px';
    holder.style.opacity = '0';
    document.body.appendChild(holder);

    const range = document.createRange();
    range.selectNodeContents(holder);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);

    try { document.execCommand('copy'); } catch (e) {}

    sel.removeAllRanges();
    document.body.removeChild(holder);
    done();
}

// Snippets is the default tab.
document.addEventListener('DOMContentLoaded', () => showSamplesTab('snippets'));
</script>
@endpush
