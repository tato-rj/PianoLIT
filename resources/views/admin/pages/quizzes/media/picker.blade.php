@component('components.dialogs.modal', ['id' => 'quiz-media-picker', 'header' => 'Choose media'])
@slot('body')
    <div data-quiz-media-content aria-live="polite" aria-busy="false"></div>
@endslot
@endcomponent
<template id="quiz-media-loading"><p class="small text-muted mb-0" role="status">Loading files…</p></template>
<template id="quiz-media-error">
    @include('components.feedback.alert', ['color' => 'red', 'message' => 'Files could not be loaded. Try again.'])
    <button type="button" class="btn btn-secondary btn-sm" data-retry-quiz-media>Retry</button>
</template>
