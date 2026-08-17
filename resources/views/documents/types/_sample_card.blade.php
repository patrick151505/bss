{{-- Reusable sample/snippet card. Expects: $item (name, desc, icon, html), $uid (unique string) --}}
<div class="col-span-12 lg:col-span-6">
    <div class="card h-full flex flex-col">
        <div class="card-header">
            <div class="flex justify-between items-center gap-3">
                <h4 class="card-title flex items-center gap-2">
                    <i class="{{ $item['icon'] }} text-primary"></i> {{ $item['name'] }}
                </h4>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                            class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-1.5"
                            onclick="toggleSampleView('{{ $uid }}', this)">
                        <i class="mgc_code_line"></i> <span>View HTML</span>
                    </button>
                    <button type="button"
                            class="btn btn-sm bg-primary text-white flex items-center gap-1.5"
                            onclick="copySample('{{ $uid }}', this)">
                        <i class="mgc_copy_line"></i> Copy
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $item['desc'] }}</p>
        </div>
        <div class="p-4 flex-1 bg-gray-100 dark:bg-gray-800/40">
            {{-- Rendered preview (default) --}}
            <div id="sample-preview-{{ $uid }}"
                 class="sample-paper bg-white rounded shadow-sm mx-auto p-6 overflow-y-auto"
                 style="max-height:20rem;">
                {!! $item['html'] !!}
            </div>

            {{-- Raw HTML (hidden until toggled; also the copy source) --}}
            <pre id="sample-code-wrap-{{ $uid }}"
                 class="hidden text-[11px] leading-relaxed bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3 overflow-auto max-h-80"><code id="sample-code-{{ $uid }}">{{ $item['html'] }}</code></pre>
        </div>
    </div>
</div>
