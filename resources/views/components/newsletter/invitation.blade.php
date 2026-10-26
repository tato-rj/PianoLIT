@php($plainInvitation = ($appearance ?? null) === 'plain')
<section class="{{ $plainInvitation ? 'bg-white border rounded-sm' : 'pastel-card rounded' }} p-4 p-md-5" @unless($plainInvitation) data-pastel-color="sky" @endunless aria-labelledby="{{ $headingId ?? 'newsletter-invitation-title' }}" @if($preview ?? false) data-ds-preview @endif>
    <span class="{{ $plainInvitation ? 'small text-muted' : 'pastel-card__tag' }}">PianoLIT newsletter</span>
    <h2 id="{{ $headingId ?? 'newsletter-invitation-title' }}" class="h4 mt-3 mb-3">Stay in tune with PianoLIT</h2>
    <p class="mb-4">New features, games, tools, stories, and more. One monthly email, only when there’s something new.</p>
    @include('components.newsletter.subscribe-button', ['label' => 'Join the newsletter', 'preview' => $preview ?? false])
</section>
