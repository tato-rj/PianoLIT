@php($isAuthorized = $hasFullAccess)

@component('webapp.explore.rows.row', ['data' => $row])
<div class="row">
	@foreach($row['collection'] as $tutorial)
		@include('webapp.explore.cards.harmony')
	@endforeach
</div>
@endcomponent
