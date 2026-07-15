<!-- Page Title Start -->
<div class="flex justify-between items-start mb-6">

    {{-- Left: title + optional tagline --}}
    <div>
        <h4 class="text-slate-900 dark:text-slate-200 text-lg font-medium leading-tight">{{ $title }}</h4>
        @if(!empty($tagline))
        <p class="text-sm text-slate-400 mt-0.5">{{ $tagline }}</p>
        @endif
    </div>

    {{-- Right: breadcrumb --}}
    <nav class="md:flex hidden items-center gap-2 text-sm font-medium flex-wrap justify-end" aria-label="Breadcrumb">

        {{-- Home --}}
        <a href="{{ url('/') }}" class="text-slate-500 dark:text-slate-400 hover:text-primary transition">
            <i class="mgc_home_3_line text-base leading-none"></i>
        </a>

        @if(!empty($breadcrumbs))
            {{-- Custom breadcrumbs: array of ['label' => '', 'url' => ''] --}}
            @foreach($breadcrumbs as $crumb)
            <i class="mgc_right_line text-base text-slate-300 rtl:rotate-180 shrink-0"></i>
            @if(!empty($crumb['url']) && !$loop->last)
            <a href="{{ $crumb['url'] }}"
               class="text-slate-500 dark:text-slate-400 hover:text-primary transition truncate max-w-[160px]">
                {{ $crumb['label'] }}
            </a>
            @else
            <span class="text-slate-700 dark:text-slate-200 truncate max-w-[200px]" aria-current="page">
                {{ $crumb['label'] }}
            </span>
            @endif
            @endforeach
        @else
            {{-- Default: sub_title → title --}}
            <i class="mgc_right_line text-base text-slate-300 rtl:rotate-180 shrink-0"></i>
            @if(!empty($sub_title_url))
            <a href="{{ $sub_title_url }}" class="text-slate-500 dark:text-slate-400 hover:text-primary transition">{{ $sub_title }}</a>
            @else
            <span class="text-slate-500 dark:text-slate-400">{{ $sub_title }}</span>
            @endif
            <i class="mgc_right_line text-base text-slate-300 rtl:rotate-180 shrink-0"></i>
            <span class="text-slate-700 dark:text-slate-200" aria-current="page">{{ $title }}</span>
        @endif

    </nav>
</div>
<!-- Page Title End -->
