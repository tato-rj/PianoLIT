<div data-result-view>
    <h3 id="match-result-heading" class="visually-hidden" tabindex="-1">Your match</h3>
    <div class="match-confetti" aria-hidden="true">@for($i = 0; $i < 16; $i++)<span style="--confetti-index:{{ $i }}"></span>@endfor</div>
    <div data-result-card></div>
    <div class="match-result-actions">
        @button(['href' => route('webapp.pieces.show', $piece), 'label' => 'View piece →', 'styles' => ['theme' => 'primary'], 'classes' => 'match-primary'])
        @button(['label' => 'You might also like →', 'data' => ['recommendations' => ''], 'styles' => ['theme' => 'secondary'], 'classes' => 'match-outline'])
    </div>
</div>
<section data-recommendations-view hidden>
    <div class="match-heading"><h3 tabindex="-1">You might also like...</h3><p>Here are a few other pieces that match your taste.</p></div>
    <div data-recommendation-cards></div>
    <div class="match-result-actions">
        @button(['href' => route('webapp.explore'), 'label' => 'Explore more pieces →', 'styles' => ['theme' => 'primary'], 'classes' => 'match-primary match-explore'])
    </div>
</section>
<template data-result-favorite>@include('webapp.components.favorite', ['directToggle' => true, 'favoriteClasses' => 'match-favorite'])</template>
<script type="application/json" data-result-data>@json(['piece' => \App\Services\WebApp\MatchTour::card($piece, true), 'recommendations' => collect($recommendations ?? [])->map(function ($other) { return \App\Services\WebApp\MatchTour::card($other); })->values()->all()])</script>
