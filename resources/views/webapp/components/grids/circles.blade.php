@component('webapp.components.grids.grid')
	@foreach($collection as $model)
	<div class="cursor-pointer mr-3 text-center search-card" data-url="{{route($route ?? 'webapp.search.results', ['search' => $model->$name])}}">
		<img src="{{$model->$image}}" class="rounded-circle mb-2" style="width: 114px" alt="{{$model->$name}}">
		<p class="m-0 clamp-2 font-weight-bold" style="line-height: 1"><small>{{$model->$name}}</small></p>
		<p class="text-muted m-0"><small>{{$model->$count}} pieces</small></p>
	</div>
	@endforeach
@endcomponent