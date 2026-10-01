@component('webapp.components.grids.grid')
	@foreach($collection as $model)
	<div class="cursor-pointer me-3 text-center search-card" data-url="{{route($route ?? 'webapp.search.results', ['search' => $model->$name])}}">
		<div style="width: 114px; height: 114px; background-image: url({{$model->$image}}); background-repeat: no-repeat; background-position: center; background-size: cover;" class="rounded-circle mb-2"></div>
		<p class="m-0 clamp-2" style="line-height: 1"><small class="fw-bold">{{ucfirst($model->$name)}}</small></p>
		<p class="text-muted m-0"><small>{{$model->$count}} {{str_plural('piece', $model->$count)}}</small></p>
	</div>
	@endforeach
@endcomponent