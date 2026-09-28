<div class="text-center mb-4">
	<img src="{{$composer->cover_image}}" style="width: 160px" class="rounded-circle shadow mb-3">
	<h5 class="mb-0">{{$composer->name}}</h5>
	<div class="mb-1"><small>{{$composer->lifespan}}</small></div>
	<div>
		@flag(['code' => $composer->country->flag_code])
		<strong class="text-muted">{{$composer->country->name}}</strong>
	</div>
</div>
<div class="text-center mb-5">
	<a href="{{route('webapp.search.results', ['search' => $composer->name])}}" class="btn btn-default">
		@icon('search')Discover pieces by {{$composer->short_name}}</a>
</div>

<div class="mb-5">
	<h5 class="">Did you know?</h5>
	<p class="">{{$composer->curiosity}}</p>
</div>
<div class="mb-5">
	<h5 class="">Biography</h5>
	<p style="white-space: pre-wrap;">{{$composer->biography}}</p>
</div>