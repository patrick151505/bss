{{-- The letterhead image, repeated on every printed page (see partials.paper.styles). --}}
@if($paper && $paper['bg_url'])
<img class="paper-bg" src="{{ $paper['bg_url'] }}" alt="">
@endif
