<div class="d-flex mb-4">
	<div class="mr-4">
		<img src="{{$composer->cover_image}}" style="width: 160px" class="rounded-circle shadow">
	</div>
	<div class="d-flex justify-content-center flex-column">
		<h4 class="mb-0">{{$composer->name}}</h4>
		<div class="mb-0"><small>{{$composer->lifespan}}</small></div>
		<div class="mb-3">
			@flag(['code' => $composer->country->flag_code])
			<small>{{$composer->country->name}}</small>
		</div>
		<div>
			<a href="{{route('webapp.search.results', ['search' => $composer->name])}}" class="btn btn-secondary btn-sm">
				@icon('search')Discover pieces by {{$composer->short_name}} @icon('chevron-right')</a>
		</div>
	</div>
</div>

<aside class="composer-curiosity mb-5" aria-labelledby="composer-curiosity-title">
	<h5 id="composer-curiosity-title" class="composer-curiosity__label">@icon('lightbulb', ['size' => 'xl'])Did you know?</h5>
	<p class="composer-curiosity__text">{{$composer->curiosity}}</p>
</aside>
<div class="mb-5">
	<h5 class="">Biography</h5>
	<p style="white-space: pre-wrap;">{{$composer->biography}}</p>
</div>