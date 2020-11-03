<div class="mb-4">
	<h5 class="mb-3">Free weekly pick</h5>
	<div class="">
		@foreach($row['content'] as $piece)
			@include('webapp.components.piece.highlight')
		@endforeach
	</div>
</div>