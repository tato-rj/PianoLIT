@php($isAuthorized = auth()->user()->isAuthorized())

@component('webapp.explore.rows.row', ['data' => $row])
@component('webapp.components.grids.grid')
	@foreach($row['collection'] as $tutorial)
		<div class="m-2 position-relative">
			@auth
			@fa(['icon' => 'lock', 'classes' => 'absolute-center opacity-6', 'size' => '2x', 'color' => 'white', 'if' => $isAuthorized])
			@endauth
			<div class="border cursor-pointer rounded d-flex d-apart flex-column p-3 piece-card" role="img" aria-label="{{$tutorial->piece->name}}" data-url="{{route('webapp.pieces.show', $tutorial->piece)}}#tutorial" style="width: 220px">
				<div class="mb-2 d-flex align-items-center">
					@fa(['icon' => 'circle', 'classes' => 'color-'.lastword($tutorial->piece->level->name)])
					<div class="text-dark clamp-1"><strong>{{$tutorial->piece->medium_name}}</strong></div>
				</div>

				<div class="w-100 text-center">
					<img src="{{asset('images/webapp/synthesia-missing.svg')}}" class="mx-auto mb-2" style="width: 102px; opacity:  .1">
				</div>
				<div class="clamp-1">by {{$tutorial->piece->composer->short_name}}</div>
			</div>
		</div>
	@endforeach
@endcomponent
@endcomponent