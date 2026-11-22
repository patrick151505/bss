{{-- Kiosk app bar: barangay, progress through the document steps (when $step is set), Back and Home. --}}
@php $steps = ['Scan ID', 'Details', 'Document', 'Purpose', 'Confirm']; @endphp
<header class="k-bar">
    <div class="k-brand">
        @if($logo)<img src="{{ $logo }}" alt="">@endif
        <div><b>{{ $bName }}</b>@if($place)<small>{{ $place }}</small>@endif</div>
    </div>

    @if(!empty($step))
    <ol class="k-stepnav" aria-label="Progress">
        @foreach($steps as $i => $label)
        @php $n = $i + 1; $cls = $n < $step ? 'done' : ($n === $step ? 'cur' : ''); @endphp
        <li class="{{ $cls }}">
            <span class="k-stepnum">@if($n < $step)<i class="mgc_check_line"></i>@else{{ $n }}@endif</span>
            <span class="k-steplbl">{{ $label }}</span>
        </li>
        @endforeach
    </ol>
    @endif

    <div class="k-bar-btns">
        @if(!empty($back))
        <button type="button" class="k-btn k-btn-ghost" data-back="{{ $back }}"><i class="mgc_arrow_left_line"></i><span>Back</span></button>
        @endif
        <button type="button" class="k-btn k-btn-ghost" data-home><i class="mgc_home_3_line"></i><span>Home</span></button>
        <button type="button" class="k-btn k-btn-ghost k-logout" data-logout title="Log out" aria-label="Log out"><i class="mgc_exit_line"></i></button>
    </div>
</header>
