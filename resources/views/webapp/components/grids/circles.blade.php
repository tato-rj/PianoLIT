@component('webapp.components.grids.grid')
	@foreach($collection as $model)
	<div class="cursor-pointer mr-3 text-center search-card" data-url="{{route($route ?? 'webapp.search.results', ['search' => $model->$name])}}">
		<img src="{{$model->$image}}" class="rounded-circle mb-2" style="width: 114px">
		<p class="m-0 clamp-2" style="line-height: 1"><small><strong>{{$model->$name}}</strong></small></p>
		<p class="text-muted m-0"><small>{{$model->$count}} pieces</small></p>
	</div>
	@endforeach
@endcomponent