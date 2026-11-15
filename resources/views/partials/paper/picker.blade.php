{{--
    Read-only note for print toolbars (screen only): which paper this printout
    uses. The paper is chosen once in Settings → Print Paper — no per-print
    selection. Param: $paper (current, or null).
--}}
<span class="paper-note" style="display:inline-flex;align-items:center;gap:6px;font:13px system-ui,-apple-system,'Segoe UI',sans-serif;color:inherit;opacity:.85;">
    Paper: <strong>{{ $paper ? $paper['name'] . ' v' . $paper['version'] : 'Plain (built-in header)' }}</strong>
    @if($paper)<span>({{ $paper['pinned'] ? 'pinned version' : 'latest' }})</span>@endif
    <a href="{{ route('settings.index') }}" target="_blank" rel="noopener"
       style="color:inherit;text-decoration:underline;opacity:.8;">change in Settings</a>
</span>
