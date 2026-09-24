<div class="tab-pane fade" id="tab-score">
	@if($piece->hasAudio() && ! $piece->isPublicDomain)
	<div class="mb-4">
		<div class="text-center">
			<button id="launch-audio" data-url="{{route('webapp.pieces.audio', $piece)}}" class="btn btn-outline-secondary">
				@fa(['icon' => 'microphone', 'size' => '1x'])Listen now</button>
		</div>
	</div>
	@endif
	
	@if($piece->isPublicDomain)
    @unless($hasMediaAccess)
    <div class="text-center mb-4">
        <p class="text-muted">Subscribe to read and download the full score.</p>
        <button type="button" data-toggle="modal" data-target="#piece-upgrade-modal" class="btn btn-primary rounded-pill btn-wide">@fa(['icon' => 'crown'])GO PREMIUM</button>
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
		<a href="{{$piece->score_url}}" target="_blank" class="btn rounded-pill btn-default">@fa(['icon' => 'shopping-basket'])Buy score</a>
	</div>
	@endif
</div>
