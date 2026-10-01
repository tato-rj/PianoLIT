@component('webapp.explore.rows.row', ['data' => $row])
<div class="">
	@foreach($row['collection'] as $level)
	<a class="link-none" href="{{route('webapp.search.results', ['search' => $level->name])}}">
		<div class="rounded border mb-2 px-4 py-3 t-2 d-flex d-apart">
			<div class="d-flex d-apart flex-grow me-4">
				<div>@icon('circle', ['classes' => 'color-'.lastword($level->name), 'filled' => true])<strong>{{ucwords($level->name)}}</strong></div>
				<div class="text-muted">{{$level->pieces_count}} pieces</div>
			</div>
			@icon('circle-arrow-right', ['size' => 'lg', 'color' => 'grey', 'mr' => 0])
		</div>
	</a>
	@endforeach
</div>
@endcomponent