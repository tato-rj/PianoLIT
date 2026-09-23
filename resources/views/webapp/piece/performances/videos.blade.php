@php($performance = auth()->check() ? $piece->performances()->by(auth()->user())->approved()->first() : null)
@php($performances = $piece->performances()->approved()->when(auth()->check(), function ($query) { $query->notBy(auth()->user()); })->get())

@if($performance)
<div class="row mb-4">
	<div class="col-lg-6 col-md-8 col-12 mx-auto rounded-video video-container">
		<p class="text-center small font-weight-bold mb-1">🎉 Your video is live!</p>
		<div class="mb-2">
		@video([
			'classes' => 'w-100', 
			'id' => 'performance-'.$performance->id, 
			'thumbnail' => $performance->thumbnail_url,
			'url' => $performance->video_url])

			@include('webapp.piece.performances.edit')
		</div>
	</div>
</div>
@endif

@if(! $performances->isEmpty())
<h5 class="mb-3">Other performances</h5>
<div class="row">
	@foreach($performances as $performance)
	<div class="col-lg-6 col-md-6 col-12 col-md-12 rounded-video video-container">
		<div class="mb-2">
		@video([
			'classes' => 'w-100', 
			'id' => 'performance-'.$performance->id, 
			'thumbnail' => $performance->thumbnail_url,
			'url' => $performance->video_url])

			@include('webapp.piece.performances.clap')
		</div>
	</div>
	@endforeach
</div>
@endif
