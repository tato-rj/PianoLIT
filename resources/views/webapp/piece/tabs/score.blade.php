<div class="tab-pane fade" id="tab-score">
	@if($piece->hasAudio() && ! $piece->isPublicDomain)
	<div class="mb-4">
		<div class="text-center">
			<button id="launch-audio" data-url="{{route('webapp.pieces.audio', $piece)}}" class="btn btn-outline-secondary">
				@icon('mic', ['size' => '1x'])Listen now</button>
		</div>
	</div>
	@endif

	@if($piece->isPublicDomain)
    @include('webapp.piece.components.score-editor')
	@else
	<div class="text-center mb-4">
		<p class="text-muted">This piece is protected by copyrights. Click the button below and we'll show you where you can purchase the score!</p>
		<a href="{{$piece->score_url}}" target="_blank" class="btn btn-primary">@icon('shopping-basket')Buy score</a>
	</div>
	@endif
</div>
