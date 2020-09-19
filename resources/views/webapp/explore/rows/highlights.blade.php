@component('webapp.explore.rows.row', ['data' => $row])
<div class="custom-scroll dragscroll dragscroll-horizontal">
	<div class="d-flex">
		@foreach($row['collection'] as $piece)
			<div class="m-2">
				@include('webapp.components.piece.highlight', ['height' => '220px', 'width' => '220px'])
			</div>
		@endforeach
	</div>
</div>
@endcomponent