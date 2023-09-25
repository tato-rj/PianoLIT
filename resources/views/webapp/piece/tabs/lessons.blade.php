<div class="tab-pane fade" id="tab-lessons">

	@if(local() || auth()->user()->id === 284)
	
		<div class="mb-4">
			
			@if(! auth()->user()->performances()->of($piece)->approved()->exists())
			<div class="mb-4"> 
				@if(auth()->user()->performances()->of($piece)->waiting()->exists())
				@include('webapp.piece.performances.pending')
				@else
				@include('webapp.piece.performances.upload')
				@endif
			</div>
			@endif			
			
			@include('webapp.piece.performances.videos')
		</div>

	@else
	
		@if($piece->media['lessons'])
		<div>
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
			<div class="col-lg-7 col-md-10 col-12 mx-auto text-center mt-4">
				<p class="text-muted">Would you like to learn more about this piece? Request video tutorials to learn more</p>
				<button class="btn rounded-pill btn-outline-secondary btn-wide" data-toggle="modal" data-target="#tutorial-request-modal">Send my request</button>
			</div>
		</div>
		@include('webapp.piece.tutorial-request')
		@endif

		<div class="mt-6">
			<h5 class="mb-3">Other harmonic analysis</h5>
			<div class="row">
				@foreach(\App\Tutorial::harmonicAnalysis(4) as $tutorial)
					@include('webapp.explore.cards.harmony', ['isAuthorized' => $hasFullAccess])
				@endforeach
			</div>
		</div>
	
	@endif
</div>