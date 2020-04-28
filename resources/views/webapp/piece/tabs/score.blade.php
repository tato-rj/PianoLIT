<div class="tab-pane fade" id="tab-score">
	@if($piece->hasAudio())
	<div class="mb-2">
	<audio controls class="w-100">
		<source src="{{storage($piece->audio_path)}}" type="audio/mp3">
	</audio>
	</div>
	@endif

	@if($piece->isPublicDomain)
	<div class="text-center">
		<div class="embed-responsive embed-responsive-a4 mb-4">
			<embed type="application/pdf" src="{{storage($piece->score_path)}}" class="embed-responsive-item" frameborder="0">
		</div>
		<a href="{{storage($piece->score_path)}}" class="btn rounded-pill btn-default">@fa(['icon' => 'file-alt'])Download score</a>
	</div>
	@endif
</div>