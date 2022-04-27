@php($isAuthorized = auth()->user()->isAuthorized())

@component('webapp.explore.rows.row', ['data' => $row])
@component('webapp.components.grids.grid')
	@foreach($row['collection'] as $tutorial)
		@include('webapp.explore.cards.synthesia')
	@endforeach
@endcomponent
@endcomponent