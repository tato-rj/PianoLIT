<div class="mb-4 video-container">
	<button class="d-flex btn-raw align-items-center mb-2" data-target="#video-{{$loop->iteration}}" data-action="video">
		<div class="mr-4 play-icon" style="width: 60px; min-width: 60px">
			<img src="{{asset('images/webapp/icons/play-red.svg')}}" class="w-100">
		</div>
		<div class="text-left">
			<p class="m-0"><strong>{{$video['title']}}</strong></p>
			<p class="m-0 text-muted">{{$video['description']}}</p>
		</div>
	</button>
	<video class="w-100" style="display: none;" id="video-{{$loop->iteration}}">
		<source src="{{$video['video_url']}}" type="video/mp4">
		Your browser does not support the video tag.
	</video>
</div>
	<div style="display: none;" id="video-{{$loop->iteration}}-fallback">
		<video class="w-100">
			<source src="{{$video['video_url']}}" type="video/mp4">
			Your browser does not support the video tag.
		</video>
	</div>