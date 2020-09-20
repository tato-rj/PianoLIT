@php($isAuthorized = auth()->user()->isAuthorized())

@component('webapp.explore.rows.row', ['data' => $row])
<div class="row">
	@foreach($row['collection'] as $tutorial)
	<div class="cursor-pointer col-lg-6 col-md-6 col-12 mb-3" 
		@if($isAuthorized)
		data-toggle="modal" data-target="#harmony-{{$loop->iteration}}"
		@else
		onclick="location.href='{{route('webapp.pieces.show', $tutorial->piece)}}'" 
		@endif
	>
		<div class="alert-grey px-4 py-3 rounded">
			<div class="mb-2 d-flex align-items-center">
				<img src="{{$tutorial->piece->composer->cover_image}}" style="width: 40px; height: 40px" class="rounded-circle mr-2">
				<div>
					<div class="text-dark clamp-1"><strong>{{$tutorial->piece->medium_name}}</strong></div>
					<div class="clamp-1">by {{$tutorial->piece->composer->name}}</div>
				</div>
			</div>
			<div class="mb-2">
				A full harmonic analysis of this piece, one measure at a time
				{{-- {{$tutorial->description}} --}}
			</div>
			<div class="d-flex d-apart">
				<div>@fa(['icon' => 'play-circle', 'size' => 'lg', 'classes' => 'opacity-4'])<strong>2 min</strong></div>
				<div>@fa(['icon' => 'lock', 'classes' => 'opacity-4', 'if' => ! $isAuthorized])</div>
			</div>
		</div>
	</div>
	
	@if($isAuthorized)
	@component('components.modal', ['id' => 'harmony-' . $loop->iteration, 'title' => $tutorial->piece->medium_name])
	@slot('body')
		<div class="mb-5">
			@foreach($tutorial->piece->tutorials()->byType('harmonic')->get() as $video)
				<div class="mb-4 video-container">
					<div class="mb-2">
						<p class="m-0 text-dark"><strong>{{$video->type}}</strong></p>
						<p class="m-0 text-muted">{{$video->description}}</p>
					</div>
					<video class="w-100" id="piece-video-{{$video->id}}">
						<source src="{{$video->video_url}}" type="video/mp4">
						Your browser does not support the video tag.
					</video>
				</div>
			@endforeach
		</div>
		<div class="text-center mb-4">
			<a href="{{route('webapp.pieces.show', $tutorial->piece)}}" class="btn rounded-pill btn-default">Learn more about this piece</a>
		</div>
	@endslot
	@endcomponent
	@endif

	@endforeach
</div>
@endcomponent