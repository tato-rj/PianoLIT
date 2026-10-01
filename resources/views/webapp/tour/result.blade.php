<div class="text-center py-4">
    <h3 tabindex="-1">Your match</h3>
    <p><strong>{{ $piece->medium_name }}</strong><br><span class="text-muted">by {{ $piece->composer->name }}</span></p>
    <button type="button" class="btn btn-primary rounded-pill" data-result-open>View your match</button>
</div>
@include('funnels.find-your-match.results', ['modalId' => 'match-tour-result', 'previewSeconds' => config('webapp.media_preview_seconds', 10)])
