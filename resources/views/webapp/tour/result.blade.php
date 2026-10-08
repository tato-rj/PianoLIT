@php($hasRecommendations = collect($recommendations ?? [])->count() >= 2)
<div data-result-view>
    <h3 id="match-result-heading" class="visually-hidden" tabindex="-1">We found your perfect match!</h3>
    <div data-result-card></div>
    @if(!empty($explanation))
        <div class="match-result-explanation">
            <h4>Why this piece?</h4>
            <p>{{ $explanation }}</p>
        </div>
    @endif
    <div class="match-result-actions">
        @button(['href' => route('webapp.pieces.show', $piece), 'label' => 'View piece →', 'styles' => ['theme' => 'primary'], 'classes' => 'match-primary'])
        @if($hasRecommendations)
            @button(['label' => 'You might also like →', 'data' => ['recommendations' => ''], 'styles' => ['theme' => 'secondary'], 'classes' => 'match-outline'])
        @endif
    </div>
</div>
@if($hasRecommendations)
<section data-recommendations-view hidden>
    <div class="match-heading"><h3 tabindex="-1">You might also like...</h3><p>More pieces at the same level, with a similar mood.</p></div>
    <div data-recommendation-cards></div>
    <div class="match-result-actions">
        @button(['href' => route('webapp.explore'), 'label' => 'Explore more pieces →', 'styles' => ['theme' => 'primary'], 'classes' => 'match-primary match-explore'])
    </div>
</section>
@endif
<script type="application/json" data-result-data>@json(['piece' => \App\Services\WebApp\MatchTour::card($piece, true), 'recommendations' => ($hasRecommendations ? collect($recommendations) : collect())->map(function ($other) { return \App\Services\WebApp\MatchTour::card($other); })->values()->all()])</script>
