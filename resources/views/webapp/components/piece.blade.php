<div class="border-bottom py-2 piece-result {{str_slug($piece->long_name)}}" 
	data-sort-level="{{$piece->level_order}}"
	data-sort-catalogue="{{ord($piece->catalogue_full_name).''.$piece->catalogue_number}}"
	data-sort-name="{{ord($piece->long_name)}}"
	data-sort-composer="{{ord($piece->composer->name)}}"
	data-sort-views="{{$piece->views_count}}">
	<a href="" class="link-none">
		@include('webapp.components.piece.level')
		@include('webapp.components.piece.name')
		@include('webapp.components.piece.composer')
	</a>
	<div class="d-flex d-apart">
		<div class="text-muted">
			@fa(['icon' => 'eye', 'color' => 'muted']){{$piece->views_count}} {{ str_plural('view', $piece->views_count) }}
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