<section class="membership-features" aria-labelledby="membership-features-heading">
    <div class="membership-section-heading">
        <span class="membership-eyebrow">Included in your membership</span>
        <h2 id="membership-features-heading">Less searching. More playing.</h2>
        <p>You can always browse for free. Premium helps you go further with the music you find.</p>
    </div>
    <div class="membership-feature-grid">
        @include('webapp.membership.pricing.components.feature', [
            'icon' => 'headphones', 'tone' => 'blue',
            'title' => 'Hear it. See it. Play it.',
            'description' => 'Enjoy full recordings and videos, with separate-hand recordings to help you learn where available.'
        ])
        @include('webapp.membership.pricing.components.feature', [
            'icon' => 'book-open', 'tone' => 'green',
            'title' => 'A score that feels like yours',
            'description' => 'Open complete public-domain scores and add your own markings, saved to your account as you practice.'
        ])
        @include('webapp.membership.pricing.components.feature', [
            'icon' => 'heart', 'tone' => 'purple',
            'title' => 'Grow your repertoire',
            'description' => 'Put your discoveries to work: save favorites, explore curated collections and find your next musical challenge.'
        ])
    </div>
</section>
