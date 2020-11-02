<div class="text-center mb-4">
	<img src="{{$composer->cover_image}}" style="width: 160px" class="rounded-circle shadow mb-3">
	<h5 class="mb-0">{{$composer->name}}</h5>
	<div class="mb-1"><small>{{$composer->lifespan}}</small></div>
	<div>
		<span class="flag-icon flag-icon-{{$composer->country->flag_code}} rounded-sm shadow-center mr-1"></span>
		<strong class="text-muted">{{$composer->country->name}}</strong>
	</div>
</div>
<div class="mb-4">
		<div class="bg-light rounded p-4 text-center mx-auto" style="max-width: 500px">
			<h5 class="text-blue">Did you know?</h5>
			<p class="text-blue">{{$composer->curiosity}}</p>
		</div>
</div>
<div class="mb-4">
	<p style="white-space: pre-wrap;">{{$composer->biography}}</p>
</div>

<div class="text-center mb-2">
	<a href="{{route('webapp.search.results', ['search' => $composer->name])}}" class="btn rounded-pill btn-default">
		@fa(['icon' => 'folder-plus'])Discover {{possessive($composer->last_name)}} pieces</a>
</div>