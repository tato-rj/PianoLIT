@php($showNewsletter = $showNewsletter ?? true)
<header class="hub-header row align-items-end gy-4 mb-5" @if($preview ?? false) data-ds-preview @endif>
    <div class="{{ $showNewsletter ? 'col-lg-8' : 'col-12' }}">
        <h1 class="hub-header__title mb-3">{{ $heading }}</h1>
        <p class="hub-header__description text-muted mb-0">{{ $slot }}</p>
    </div>
    @if($showNewsletter)
        <div class="col-lg-4 text-lg-end">
            @include('components.newsletter.subscribe-button', ['label' => 'Join the newsletter', 'preview' => $preview ?? false])
        </div>
    @endif
</header>
