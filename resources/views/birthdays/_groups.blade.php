{{-- PWD / Senior / Solo parent pills and the citizen's tags. Param: $c (Citizen with tags loaded). --}}
<div class="flex flex-wrap gap-1">
    @if($c->is_pwd)
        <span class="text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded px-1.5 py-0.5">PWD</span>
    @endif
    @if(($c->age ?? 0) >= 60)
        <span class="text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 rounded px-1.5 py-0.5">Senior</span>
    @endif
    @if($c->is_soloparents)
        <span class="text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 rounded px-1.5 py-0.5">Solo Parent</span>
    @endif
    @foreach($c->tags as $tag)
        <span class="text-xs font-semibold text-white rounded px-1.5 py-0.5" style="background:{{ $tag->color }}">{{ $tag->name }}</span>
    @endforeach
</div>
