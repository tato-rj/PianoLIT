@foreach($pieces as $piece)
	<div class="col-lg-4 col-md-6 col-12 mb-4 position-relative"
		data-tags="{{$piece->tags->pluck('name')->implode(' ')}}"
		data-sort-level="{{$piece->level_order}}"
		data-sort-catalogue="{{ord($piece->catalogue_full_name).''.$piece->catalogue_number.''.$piece->collection_number}}"
		data-sort-name="{{ord($piece->long_name)}}"
		data-sort-composer="{{ord($piece->composer->name)}}"
		data-sort-period="{{$piece->period->id}}"
		data-sort-views="{{$piece->views_count}}">
		@auth
		@fa(['icon' => 'lock', 'classes' => 'absolute-center opacity-6', 'size' => '2x', 'color' => 'white', 'if' => ! $hasFullAccess && ! $piece->is_free])
		@endauth
		<a href="{{route('webapp.pieces.show', $piece)}}">
			@include('webapp.components.piece.highlight', ['height' => '220px', 'piece' => $piece])
		</a>
	</div>
@endforeach