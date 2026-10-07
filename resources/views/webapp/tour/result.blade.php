<article class="match-result" aria-labelledby="match-result-heading">
    <h3 class="text-center mb-4" id="match-result-heading" tabindex="-1">Your match</h3>
    <div class="match-result-card">
        @include('funnels.find-your-match.result-body', ['modalId' => 'match-tour-result', 'inlineResult' => true, 'previewSeconds' => config('webapp.media_preview_seconds', 10)])
        @include('funnels.find-your-match.result-footer')
    </div>
</article>
