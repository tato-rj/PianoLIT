<section class="playlist-player__sections" id="playlist-sections" data-sections-panel aria-label="Sections" hidden>
    <ol class="list-unstyled mb-0" data-sections-list></ol>
    <div class="playlist-section-comment border rounded p-3 mt-2" id="playlist-section-commentary" data-section-commentary role="region" aria-labelledby="playlist-section-title" hidden>
        <div class="d-flex align-items-start gap-2">
            <span class="text-muted small" data-section-comment-time></span>
            <h6 class="mb-2 flex-grow-1" id="playlist-section-title" data-section-comment-title></h6>
            <button class="btn-raw text-muted" type="button" data-section-comment-close aria-label="Close section commentary">@icon('x', ['mr' => 0])</button>
        </div>
        <p class="mb-0 small" data-section-comment-text></p>
    </div>
</section>
<template data-section-template>
    <li class="playlist-section border-bottom">
        <button class="btn-raw playlist-section__seek" type="button" data-section-seek>
            <span class="playlist-section__play" data-section-play>@icon('play', ['mr' => 0, 'filled' => true])</span>
            <span class="text-muted" data-section-time></span>
            <span class="playlist-section__title" data-section-title></span>
        </button>
        <button class="btn btn-secondary btn-sm playlist-section__about" type="button" data-section-about aria-expanded="false" aria-controls="playlist-section-commentary">
            @icon('info', ['mr' => 0])<span data-section-about-label hidden>About this section</span>
        </button>
    </li>
</template>
