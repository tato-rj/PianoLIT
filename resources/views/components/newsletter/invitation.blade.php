<section class="pastel-card rounded p-4 p-md-5" data-pastel-color="sky" aria-labelledby="{{ $headingId ?? 'newsletter-invitation-title' }}" @if($preview ?? false) data-ds-preview @endif>
    <span class="pastel-card__tag">PianoLIT newsletter</span>
    <h2 id="{{ $headingId ?? 'newsletter-invitation-title' }}" class="h4 mt-3 mb-3">Stay in tune with PianoLIT</h2>
    <p class="mb-4">New features, games, tools, stories, and more. One monthly email, only when there’s something new.</p>
    @include('components.newsletter.subscribe-button', ['label' => 'Join the newsletter', 'preview' => $preview ?? false])
</section>
