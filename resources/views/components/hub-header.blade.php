<header class="hub-header row align-items-end gy-4 mb-4" @if($preview ?? false) data-ds-preview @endif>
    <div class="col-lg-8">
        <h1 class="hub-header__title mb-3">{{ $heading }}</h1>
        <p class="hub-header__description text-muted mb-0">{{ $slot }}</p>
    </div>
    <div class="col-lg-4 text-lg-end">
        @include('components.newsletter.subscribe-button', ['label' => 'Join the newsletter', 'preview' => $preview ?? false])
    </div>
</header>
