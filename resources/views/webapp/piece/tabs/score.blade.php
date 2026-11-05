<div class="tab-pane fade" id="tab-score" role="tabpanel" aria-labelledby="main-nav-tab-score" tabindex="0">
	@if($piece->hasAudio() && ! $piece->isPublicDomain)
	<div class="mb-4">
		<div class="text-center">
			<button type="button" id="launch-audio" data-url="{{route('webapp.pieces.audio', $piece)}}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
				@include('webapp.piece.components.listen-button-content', ['listenLabel' => 'Listen now'])</button>
		</div>
	</div>
	@endif

	@if($piece->isPublicDomain)
    @include('webapp.piece.components.score-editor')
	@else
	<div class="text-center">
		<p class="text-muted">This score is under copyright. Use the button below to find a purchase link.</p>
		<a href="{{$piece->score_url}}" target="_blank" class="btn btn-primary">@icon('shopping-basket')Buy score</a>
	</div>
	@endif
</div>
