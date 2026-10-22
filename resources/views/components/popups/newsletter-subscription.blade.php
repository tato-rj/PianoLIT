@component('components.modal', [
    'id' => 'modal-newsletter-subscription', 'titleId' => 'newsletter-subscription-heading',
    'descriptionId' => 'newsletter-subscription-description', 'centered' => true,
    'data' => ['popup-reuse' => 'true'],
    'options' => ['header' => ['raw' => true]],
])
    @slot('header')
        <span class="d-inline-flex text-primary bg-light rounded-circle p-3" aria-hidden="true">@icon('mail', ['mr' => 0, 'size' => 'xl'])</span>
    @endslot
    @slot('body')
        <h2 class="h3 mb-3" id="newsletter-subscription-heading">Stay in tune with PianoLIT.</h2>
        <p class="text-muted mb-4" id="newsletter-subscription-description">Be the first to explore new features, games, tools, stories, and more from across PianoLIT. Get everything new in one monthly email.</p>
        @include('components.newsletter.subscribe-form')
    @endslot
@endcomponent
