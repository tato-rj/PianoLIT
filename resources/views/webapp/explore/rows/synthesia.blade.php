@php($isAuthorized = auth()->user()->isAuthorized())

@component('webapp.explore.rows.row', ['data' => $row])
@component('webapp.components.grids.grid')
	@foreach($row['collection'] as $tutorial)
		<div class="m-2 position-relative">
			@auth
			@fa(['icon' => 'lock', 'classes' => 'absolute-center opacity-6', 'size' => '3x', 'color' => 'white', 'if' => $isAuthorized])
			@endauth
			<div class="border cursor-pointer rounded d-flex d-apart flex-column piece-card" role="img" aria-label="{{$tutorial->piece->name}}" data-url="{{route('webapp.pieces.show', $tutorial->piece)}}#tutorial" style="width: 240px">
				<div class="d-flex align-items-center px-3 py-2">
					@fa(['icon' => 'circle', 'classes' => 'color-'.lastword($tutorial->piece->level->name)])
					<div class="text-dark clamp-1"><strong>{{$tutorial->piece->medium_name}}</strong></div>
				</div>

				<img src="{{asset('images/webapp/synthesia-thumbnails/thumb-'.$loop->index.'.jpg')}}" class="w-100">
				<div class="clamp-1 px-2 py-1"><small>{{$tutorial->piece->composer->name}}</small></div>
			</div>
		</div>
	@endforeach
@endcomponent
@endcomponent