<div class="border-bottom py-2">
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