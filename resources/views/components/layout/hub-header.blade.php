@php($hasDescription = trim((string) $slot) !== '')
<header class="hub-header row align-items-end gy-4 {{ $spacing ?? 'mb-5' }}" @if($preview ?? false) data-ds-preview @endif>
    <div class="col-12">
        <h1 class="hub-header__title {{ $hasDescription ? 'mb-3' : 'mb-0' }}">{{ $heading }}</h1>
        @if($hasDescription)<p class="hub-header__description text-muted mb-0">{{ $slot }}</p>@endif
    </div>
</header>
