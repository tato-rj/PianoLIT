<div class="grid-item rounded-0 thumbnail p-1 col-lg-3 col-md-4 col-6">
	<div class="position-relative no-click border">
		<a href="{{route('resources.infographs.show', $item)}}" class="link-none">
			@include('components.tags.new', ['is_new' => $item->is_new, 'color' => 'danger'])
			<img src="{{storage($item->thumbnail_path)}}" class="w-100">
		</a>
	</div>
</div>