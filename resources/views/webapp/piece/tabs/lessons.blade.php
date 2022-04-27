<div class="tab-pane fade" id="tab-lessons">
	@if($piece->media['lessons'])
	<div class="mb-5">
		@foreach($piece->media['lessons'] as $lessons)
		<div class="pb-2">
			<h5 class="mb-3">{{$lessons['title']}}</h5>
			@each('webapp.piece.components.video', $lessons['videos'], 'tutorial')
		</div>
		@endforeach
	</div>
	@endif

	@if(! $piece->hasTutorials(['tutorial', 'harmony']))
	<div class="row">
		<div class="col-lg-7 col-md-10 col-12 mx-auto text-center mt-3 pt-3">
			<p class="text-muted">Would you like to learn more about this piece? Request video tutorials to learn more</p>
			<button class="btn rounded-pill btn-outline-secondary btn-wide" data-toggle="modal" data-target="#tutorial-request-modal">Send my request</button>
		</div>
	</div>

	@include('webapp.piece.tutorial-request')
	@endif

	<div class="mt-5 pt-5 border-top">
		<h5 class="mb-3">Latest harmonic analysis</h5>
		<div class="custom-scroll dragscroll dragscroll-horizontal">
			<div class="d-flex pb-2" style="height: 144px;">
				@foreach(\App\Tutorial::latestHarmonicAnalysis(4)->pluck('piece') as $card)
					@php($card->color = 'orange')
					@php($card->subtitle = $card->composer->short_name)
					@include('webapp.discover.cards.piece', ['hasFullAccess' => $hasFullAccess])
				@endforeach
			</div>
		</div>
	</div>
</div>