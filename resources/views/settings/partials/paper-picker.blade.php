{{--
    Paper template picker (same look as Documents → Document Type).
    Params: $print (a key of App\Support\Paper::PRINTS), $label ('' = no heading), $paperOptions, $setting.
    Posts {print}_paper_tpl ("" = none / template id) and
          {print}_paper_ver ("latest" / version id).
--}}
@php
    $tplField = "{$print}_paper_tpl";
    $verField = "{$print}_paper_ver";
    $curTpl   = (string) old($tplField, $setting->{"{$print}_paper_template_id"} ?? '');
    $curVer   = (string) old($verField, $setting->{"{$print}_paper_version_id"} ?? 'latest');
    $sizes    = ['a4' => 'A4', 'letter' => 'Short (Letter)', 'half_letter' => 'Half Letter', 'long' => 'Long (Legal)'];
    $sizeName = fn($s) => $sizes[$s] ?? strtoupper($s ?? 'A4');
@endphp
<div class="paper-picker space-y-2" data-print="{{ $print }}">
    @if($label !== '')<p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $label }}</p>@endif

    {{-- None --}}
    <label class="cursor-pointer block">
        <input type="radio" name="{{ $tplField }}" value="" class="sr-only peer paper-tpl-radio"
               {{ $curTpl === '' ? 'checked' : '' }}>
        <div class="flex items-center gap-3 p-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
            <div class="w-10 h-14 rounded border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                <i class="mgc_close_line text-gray-400 text-xs"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">None</p>
                <p class="text-[11px] text-gray-400">Plain built-in header from Barangay Settings</p>
            </div>
        </div>
    </label>

    @foreach($paperOptions as $tpl)
    @php
        $isSelected = $curTpl === (string) $tpl->id;
        $cv = $tpl->currentVersion;
    @endphp
    <label class="cursor-pointer block">
        <input type="radio" name="{{ $tplField }}" value="{{ $tpl->id }}" class="sr-only peer paper-tpl-radio"
               {{ $isSelected ? 'checked' : '' }}>
        <div class="flex items-center gap-3 p-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
            <div class="{{ ($cv?->orientation ?? 'portrait') === 'landscape' ? 'w-14 h-10' : 'w-10 h-14' }} rounded border border-gray-200 dark:border-gray-600 overflow-hidden shrink-0 bg-gray-100 dark:bg-gray-800">
                @if($cv?->paper_bg)
                    <img src="{{ asset('storage/' . $cv->paper_bg) }}" alt="{{ $tpl->name }}" class="w-full h-full object-cover object-top">
                @else
                    <div class="w-full h-full flex items-center justify-center"><i class="mgc_paper_line text-gray-300 text-xs"></i></div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $tpl->name }}</p>
                <p class="text-[11px] text-gray-400">
                    {{ $sizeName($cv?->paper_size) }} · {{ ucfirst($cv?->orientation ?? 'portrait') }} · {{ $tpl->versions->count() }} version(s)
                </p>
            </div>
        </div>
    </label>

    {{-- Version picker — shown while this template is selected --}}
    <div class="paper-versions {{ $isSelected ? '' : 'hidden' }} ml-3 pl-3 border-l-2 border-primary/30 space-y-1.5" data-tpl="{{ $tpl->id }}">
        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Pin a version</p>

        {{-- Latest: follows the template's current version automatically --}}
        <label class="cursor-pointer block">
            <input type="radio" name="{{ $verField }}" value="latest" class="sr-only peer paper-ver-latest"
                   {{ $isSelected && $curVer === 'latest' ? 'checked' : '' }} @unless($isSelected) disabled @endunless>
            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
                <div class="w-8 h-10 rounded border border-dashed border-primary/40 flex items-center justify-center shrink-0 bg-primary/5">
                    <i class="mgc_refresh_2_line text-primary text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Latest</span>
                    <p class="text-[10px] text-gray-400">Always the current version (now v{{ $cv?->version }}) — switches automatically when a new version is saved</p>
                </div>
            </div>
        </label>

        @foreach($tpl->versions as $ver)
        <label class="cursor-pointer block">
            <input type="radio" name="{{ $verField }}" value="{{ $ver->id }}" class="sr-only peer"
                   {{ $isSelected && $curVer === (string) $ver->id ? 'checked' : '' }} @unless($isSelected) disabled @endunless>
            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 peer-checked:border-primary peer-checked:bg-primary/5 transition">
                <div class="{{ ($ver->orientation ?? 'portrait') === 'landscape' ? 'w-10 h-8' : 'w-8 h-10' }} rounded border border-gray-200 dark:border-gray-600 overflow-hidden shrink-0 bg-white">
                    @if($ver->paper_bg)
                        <img src="{{ asset('storage/' . $ver->paper_bg) }}" class="w-full h-full object-cover object-top" alt="">
                    @else
                        <div class="w-full h-full flex items-center justify-center"><i class="mgc_paper_line text-gray-300 text-[9px]"></i></div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">v{{ $ver->version }}</span>
                        @if($ver->id === $tpl->current_version_id)
                            <span class="text-[9px] font-bold px-1 rounded bg-primary text-white">CURRENT</span>
                        @endif
                    </div>
                    <p class="text-[10px] text-gray-400">
                        {{ $sizeName($ver->paper_size) }} · {{ ucfirst($ver->orientation ?? 'portrait') }}
                        · pad {{ $ver->padding_top }}/{{ $ver->padding_right }}/{{ $ver->padding_bottom }}/{{ $ver->padding_left }}px
                    </p>
                    @if($ver->change_note)<p class="text-[10px] text-gray-400 truncate">{{ $ver->change_note }}</p>@endif
                    <p class="text-[10px] text-gray-400">{{ $ver->created_at?->format('M d, Y') }}</p>
                </div>
            </div>
        </label>
        @endforeach
    </div>
    @endforeach

    @error($tplField) <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    @error($verField) <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
</div>
