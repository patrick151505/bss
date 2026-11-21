{{-- Citizen photo or initials. Params: $c (Citizen), $size (Tailwind w/h classes), $tone (initials colour classes). --}}
@if($c->profile_url)
    <img src="{{ $c->profile_url }}" alt="" loading="lazy" class="{{ $size }} rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0">
@else
    <span class="{{ $size }} rounded-full {{ $tone ?? 'bg-primary/15 text-primary' }} flex items-center justify-center font-semibold shrink-0">
        {{ strtoupper(mb_substr($c->fname ?? '', 0, 1) . mb_substr($c->lname ?? '', 0, 1)) }}
    </span>
@endif
