<a href="{{route('webapp.playlists.show', $playlist)}}" class="link-none mb-4 {{$loop->first ? 'col-12' : 'col-6'}}">
	<div class="bg-align-center rounded mb-3 position-relative" style="background-image: url({{$playlist->cover_image}}); height: {{$loop->first ? '28vh' : '20vh'}}">
		@fa(['icon' => 'lock', 'classes' => 'absolute-center opacity-4', 'size' => '3x', 'color' => 'white', 'if' => ! $playlist->isFeatured && ! auth()->user()->isAuthorized()])
		@pill(['label' => 'featured', 'color' => 'primary', 'text' => 'white', 'pos' => 'bottom-right', 'if' => $loop->first])
	</div>
	<div class="text-center">
		<h6 class="mb-1" style="line-height: 1; {{$loop->first ? 'font-size: 112%' : 'font-size: 92%'}}">{{$playlist->name}}</h6>
		<p class="m-0" style="line-height: 1;"><small>{{$loop->first ? $playlist->subtitle : $playlist->pieces_count . ' pieces'}}</small></p>
	</div>
</a>