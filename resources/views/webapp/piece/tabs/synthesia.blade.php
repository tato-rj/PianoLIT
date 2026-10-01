<div class="tab-pane fade" id="tab-synthesia">
	@if($piece->media['synthesia'])
	<div class="rounded-video video-container" style="    max-width: 520px;
    margin: 0 auto;">
		@video([
			'classes' => 'w-100',
			'id' => 'piece-synthesia',
            'moments' => $piece->media['synthesia']->listeningMoments(),
            'previewSeconds' => $hasMediaAccess ? null : $previewSeconds,
			'thumbnail' => asset('images/webapp/synthesia-thumbnail.gif'),
			'url' => $piece->media['synthesia']->video_url])
	</div>
	@else

	<div class="text-center">
		<img src="{{asset('images/webapp/synthesia-missing.svg')}}" class="mx-auto mb-4" style="width: 132px; opacity:  .1">
		<p class="text-muted">Would you like to watch a synthesia of this piece?<br>Tap below to make your request.</p>
		@auth('web')
		<button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#synthesia-request-modal">Send my request</button>
		@else
		<a href="{{ route('login') }}" class="btn btn-secondary">Sign in to request</a>
		@endauth
	</div>

	@auth('web')
	@include('webapp.piece.synthesia-request')
	@endauth
	@endif

	<div class="mt-6">
		<h5 class="mb-3">Other releases</h5>
		@component('webapp.components.grids.grid')
			@foreach(\App\Tutorial::synthesia(12)->loadMissing('piece.tags') as $tutorial)
				@include('webapp.explore.cards.synthesia', ['isAuthorized' => $hasFullAccess])
			@endforeach
		@endcomponent
	</div>
{{-- 	<h5 class="mb-3">What is Synthesia?</h5>
	<p>Synthesia is an animation that can help you learn how to play the piano using falling notes. While this tool is not a substitute for reading music, it does provide a quick and easy way for beginners to get started.</p>
 --}}

</div>
