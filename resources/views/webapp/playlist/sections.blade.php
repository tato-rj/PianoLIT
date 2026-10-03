<section class="playlist-player__sections" id="playlist-sections" data-sections-panel aria-label="Sections" hidden>
    <ol class="list-unstyled mb-0" data-sections-list></ol>
</section>
<template data-section-template>
    <li class="playlist-section border-bottom">
        <button class="btn-raw playlist-section__seek" type="button" data-section-seek>
            <span class="playlist-section__play" data-section-play>@icon('play', ['mr' => 0, 'filled' => true])</span>
            <span class="text-muted" data-section-time></span>
            <span class="playlist-section__title" data-section-title></span>
        </button>
    </li>
</template>
