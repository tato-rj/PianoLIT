
<section class="my-3 position-relative">
	@include('webapp.components.back')

	<div class="d-flex px-4">
		<div class="piece-header w-100">
			<a href="{{route('webapp.search.results', ['search' => $piece->extended_level_name])}}">
				@include('webapp.components.piece.level')
			</a>
			<h3 class="mt-2 mb-1">{{$piece->medium_name}}</h3>
			<p class="text-muted">{{$piece->attribution}}{{$piece->composer->name}}</p>
		</div>

		<a href="{{route('webapp.search.results', ['search' => $piece->composer->name])}}" class="">
			<img src="{{$piece->composer->cover_image}}" style="width: 100px; top: -70px; left: 30px;" class="rounded-circle shadow border border-white border-1x position-absolute piece__composer-image">
		</a>
	</div>

	<div class="position-absolute d-flex align-items-center" style="right: 0; bottom: 50%; transform: translateY(50%);">
		<div class="d-none d-md-block">
		@include('webapp.components.favorite')
	</div>
	<button class="btn-raw ml-2" type="More options"  
	data-toggle="fixed-panel" data-target="#options-panel" style=" font-size: 1.44em">
		@fa(['icon' => 'ellipsis-v', 'mr' => 0])</button>
	</div>
</section>