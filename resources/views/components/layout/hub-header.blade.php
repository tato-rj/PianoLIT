@php($showNewsletter = $showNewsletter ?? true)
@if($showNewsletter && ! ($preview ?? false))
    @section('public-header-newsletter', true)
@endif
<header class="hub-header row align-items-end gy-4 mb-5" @if($preview ?? false) data-ds-preview @endif>
    <div class="col-12">
        <h1 class="hub-header__title mb-3">{{ $heading }}</h1>
        <p class="hub-header__description text-muted mb-0">{{ $slot }}</p>
    </div>
</header>
