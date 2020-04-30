<div class="mb-4 video-container">
	<button class="d-flex mb-2 btn-raw" data-target="#video-{{$loop->iteration}}" data-action="video">
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
	</video>
</div>