@component('webapp.components.grids.grid')
	@foreach($collection as $model)
		<div class="m-2 position-relative">
			@auth
			@fa(['icon' => 'lock', 'classes' => 'absolute-center opacity-6', 'size' => '2x', 'color' => 'white', 'if' => ! $hasFullAccess])
			@endauth
			@include('webapp.components.piece.highlight', ['height' => '220px', 'width' => '220px', 'piece' => $model])
		</div>
	@endforeach
@endcomponent