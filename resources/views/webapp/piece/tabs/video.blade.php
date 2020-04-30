<div class="tab-pane fade" id="tab-video">
	@if($piece->hasVideos())
	<div class="mb-5">
		<h5 class="mb-4">PianoLIT videos</h5>
		@foreach($piece->videos_array as $video)
			@include('webapp.piece.components.video')
		@endforeach
	</div>
	@else
	<div class="text-center py-4">
		<p class="m-0">No videos found :/</p>
		<p class="text-grey"><small>We haven't uploaded videos yet...</small></p>
	</div>
	@endif

	@if(! $piece->hasVideos() || count($piece->videos_array) == 1)
	<div class="row">
		<div class="col-lg-7 col-md-10 col-12 mx-auto text-center bg-light rounded px-4 py-3">
			<p class="mb-1 text-brand">Request tutorial</p>
			<p class="text-muted">Curious about this piece? Request video tutorials to learn more</p>
			<button class="btn rounded-pill btn-default" style="padding: .6em 2.8em;" data-toggle="modal" data-target="#confirm-request-modal">Send my request</button>
		</div>
	</div>
	@endif
</div>