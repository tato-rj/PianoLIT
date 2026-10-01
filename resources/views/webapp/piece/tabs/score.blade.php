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
    @unless($hasMediaAccess)
    <div class="text-center mb-4">
        <p class="text-muted">Subscribe to read and download the full score.</p>
        <button type="button" data-bs-toggle="modal" data-bs-target="#piece-upgrade-modal" class="btn btn-primary">@icon('crown')GO PREMIUM</button>
    </div>
    <div id="score-preview" data-pdf-url="{{ storage($piece->score_path) }}">
        <p class="score-preview-status text-muted text-center">Loading score preview...</p>
        <div class="score-preview-pages" aria-hidden="true"></div>
    </div>
    @else
    @include('webapp.piece.components.score-editor')
    @endunless
	@else
	<div class="text-center mb-4">
		<p class="text-muted">This piece is protected by copyrights. Click the button below and we'll show you where you can purchase the score!</p>
		<a href="{{$piece->score_url}}" target="_blank" class="btn btn-primary">@icon('shopping-basket')Buy score</a>
	</div>
	@endif
</div>
