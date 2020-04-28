<div class="mb-2">
	<div class="d-flex mb-2">
		<div class="mr-4" style="max-width: 60px">
			<img src="{{asset('images/webapp/icons/apple-music.svg')}}" class="w-100">
		</div>
		<div>
			<p class="m-0">{{$piece->medium_name}}</p>
			<p class="m-0 text-muted">{{$itunes['album']}}</p>
			<p class="m-0 text-muted"><small>{{$itunes['artist']}}</small></p>
		</div>
	</div>
	<div class="text-center">
		<a href="{{$itunes['link']}}" target="_blank" class="btn btn-sm btn-wide btn-purple-outline" style="border-width: 1.4px;">Find on @fa(['icon' => 'apple', 'fa_type' => 'b', 'mr' => 0]) Music</a>
	</div>
</div>