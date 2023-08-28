<div class="row">
	@foreach($piece->performances()->approved()->get() as $performance)
	<div class="col-lg-6 col-md-6 col-12 col-md-12 rounded-video video-container">
		<div class="mb-2">
		@video([
			'classes' => 'w-100', 
			'id' => 'performance-'.$performance->id, 
			'thumbnail' => $performance->thumbnail_url,
			'url' => $performance->video_url])
			<div class="mt-1">
				@if($performance->isBy(auth()->user()))
				@include('webapp.piece.performances.edit')
				@else
				@include('webapp.piece.performances.clap')
				@endif
			</div>
		</div>
	</div>
	@endforeach
</div>