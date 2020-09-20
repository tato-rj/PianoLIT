@component('webapp.explore.rows.row', ['data' => $row])
<div class="custom-scroll dragscroll dragscroll-horizontal">
	<div class="d-flex">
		@foreach($row['collection'] as $piece)
			<div class="m-2 position-relative">
				@fa(['icon' => 'lock', 'classes' => 'absolute-center opacity-6', 'size' => '2x', 'color' => 'white', 'if' => ! auth()->user()->isAuthorized()])
				@include('webapp.components.piece.highlight', ['height' => '220px', 'width' => '220px'])
			</div>
		@endforeach
	</div>
</div>
@endcomponent