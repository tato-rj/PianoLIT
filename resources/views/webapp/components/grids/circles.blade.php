@component('webapp.components.grids.grid', ['id' => !empty($composerRow) ? 'discover-composers-rail' : null])
	@foreach($collection as $model)
	@if(!empty($composerRow))
	<a class="composer-tile text-center link-none me-3" href="{{ route('webapp.composers.show', $model) }}">
		<div class="composer-tile__portrait rounded-circle mb-2" style="background-image: url('{{ $model->$image }}')" aria-hidden="true"></div>
		<p class="composer-tile__name m-0"><small class="fw-bold">{{ ucfirst($model->$name) }}</small></p>
		<p class="text-muted m-0"><small>{{ $model->$count }} {{ str_plural('piece', $model->$count) }}</small></p>
	</a>
	@else
	<div class="cursor-pointer me-3 text-center search-card" data-url="{{!empty($composerLinks) ? route('webapp.composers.show', $model) : route($route ?? 'webapp.search.results', ['search' => $model->$name])}}">
		<div style="width: 114px; height: 114px; background-image: url({{$model->$image}}); background-repeat: no-repeat; background-position: center; background-size: cover;" class="rounded-circle mb-2"></div>
		<p class="m-0 clamp-2" style="line-height: 1"><small class="fw-bold">{{ucfirst($model->$name)}}</small></p>
		<p class="text-muted m-0"><small>{{$model->$count}} {{str_plural('piece', $model->$count)}}</small></p>
	</div>
	@endif
	@endforeach
@endcomponent
