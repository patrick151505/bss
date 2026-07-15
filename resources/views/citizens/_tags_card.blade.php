{{--
    Reusable tags card for create/edit forms.
    Requires: $allTags (Collection), $selectedTagIds (array of ints, default [])
--}}
@php $selectedTagIds = $selectedTagIds ?? []; @endphp

@if($allTags->isNotEmpty())
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h5 class="card-title"><i class="mgc_tag_line me-2 text-primary"></i>Tags</h5>
        <span class="text-xs text-gray-400">Select all that apply</span>
    </div>
    <div class="p-5">
        {{-- Search --}}
        <div class="relative mb-3">
            <input type="text" id="tag-card-search" placeholder="Search tags…"
                   oninput="filterTagCard(this.value)"
                   class="form-input ps-8 text-sm w-full">
            <i class="mgc_search_line absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        </div>

        {{-- Sentinel so tags key always exists in POST even when nothing is checked --}}
        <input type="hidden" name="tags" value="">

        {{-- Tag checkboxes --}}
        <div id="tag-card-list" class="flex flex-col gap-1 max-h-56 overflow-y-auto pr-1">
            @foreach($allTags as $tag)
            <label class="tag-card-item flex items-start gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                   data-name="{{ strtolower($tag->name) }}">
                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                       {{ in_array($tag->id, $selectedTagIds) ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary shrink-0">
                <div class="min-w-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                          style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                    @if($tag->description)
                    <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ $tag->description }}</p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>

        {{-- Selected count --}}
        <p class="text-xs text-gray-400 mt-3" id="tag-selected-count"></p>
    </div>
</div>

<script>
(function () {
    function updateCount() {
        const n = document.querySelectorAll('#tag-card-list input[type=checkbox]:checked').length;
        document.getElementById('tag-selected-count').textContent =
            n ? n + ' tag' + (n > 1 ? 's' : '') + ' selected' : '';
    }

    document.querySelectorAll('#tag-card-list input[type=checkbox]').forEach(cb => {
        cb.addEventListener('change', updateCount);
    });

    updateCount();
})();

function filterTagCard(q) {
    const term = q.toLowerCase().trim();
    document.querySelectorAll('.tag-card-item').forEach(item => {
        item.style.display = (!term || item.dataset.name.includes(term)) ? '' : 'none';
    });
}
</script>
@endif
