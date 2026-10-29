<section id="quiz-share-panel" class="link-share quiz-share-panel" data-quiz-share-panel data-link-share data-share-url="{{ $share['url'] }}" data-share-title="{{ $share['title'] }}" data-share-message="{{ $share['message'] }}" hidden aria-labelledby="quiz-share-title">
    <button type="button" class="btn btn-secondary btn-sm mb-3" data-quiz-share-back>@icon('arrow-left')Your result</button>
    <h2 id="quiz-share-title" class="h4 text-center mb-3" tabindex="-1">Throw down the challenge.</h2>
    <img src="{{ $share['image'] }}" alt="{{ $share['title'] }} {{ $share['challenge'] }}" width="1200" height="630" class="quiz-share-card w-100 d-block mb-4" loading="lazy">
    @include('components.media.link-share-options', ['id' => 'quiz-share'])
    <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
        <a class="btn btn-secondary" href="{{ $share['download'] }}" download="pianolit-quiz-score.png">@icon('download')Save score card</a>
        <button type="button" class="btn btn-secondary" data-quiz-share-native hidden>@icon('share')More apps</button>
    </div>
    <p class="small text-muted text-center mt-2 mb-0">Save it for Instagram &amp; Stories.</p>
</section>
