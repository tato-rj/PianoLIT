<div class="mb-4">
	<h5 class="mb-3">Free weekly pick</h5>
	<div class="">
		@foreach($row['content'] as $piece)
		@if(auth()->user()->id == 284)
		@php($piece = \App\Piece::find(816))
		@endif
			@include('webapp.components.piece.highlight')
		@endforeach
	</div>
</div>