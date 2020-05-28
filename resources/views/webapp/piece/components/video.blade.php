<div class="mb-4 video-container">
	<button class="d-flex btn-raw align-items-center mb-2" data-url="{{route('webapp.pieces.video', ['piece' => $piece, 'video' => $loop->iteration])}}" data-action="video">
		<div class="mr-4 play-icon" style="width: 60px; min-width: 60px">
			<img src="{{asset('images/webapp/icons/play-red.svg')}}" class="w-100">
		</div>
		<div class="text-left">
			<p class="m-0 text-dark"><strong>{{$video['title']}}</strong></p>
			<p class="m-0 text-muted">{{$video['description']}}</p>
		</div>
	</button>
	{{-- APPEND VIDEO ELEMENT HERE --}}
</div>