<div class="py-2 piece-result {{str_slug($piece->long_name)}}" 
	data-tags="{{$piece->tags->pluck('name')->implode(' ')}}"
	data-sort-level="{{$piece->level_order}}"
	data-sort-catalogue="{{ord($piece->catalogue_full_name).''.$piece->catalogue_number.''.$piece->collection_number}}"
	data-sort-name="{{ord($piece->long_name)}}"
	data-sort-composer="{{ord($piece->composer->name)}}"
	data-sort-period="{{$piece->period->id}}"
	data-sort-views="{{$piece->views_count}}">
	<a href="{{route('webapp.pieces.show', $piece)}}" class="link-none">
		<div class="d-flex mb-2 align-items-center">
			@include('webapp.components.piece.level')
			@fa(['icon' => 'lock', 'color' => 'grey', 'ml' => 2, 'if' => ! $hasFullAccess])
		</div>
		@include('webapp.components.piece.name')
		@include('webapp.components.piece.composer')
	</a>
	<div class="d-flex d-apart">
		<div class="d-flex text-grey">
			@include('webapp.components.piece.icons')
		</div>
		<div>
			@empty($slot)
			@include('webapp.components.favorite')
			@else
			{{$slot}}
			@endempty
		</div>
	</div>
</div>