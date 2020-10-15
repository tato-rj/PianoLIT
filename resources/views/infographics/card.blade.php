<div class="infograph-card rounded-0 thumbnail p-1 {{$sizes}}">
	<div class="position-relative no-clickx border" style="overflow-y: hidden;">
		<a href="{{route('resources.infographs.show', $infograph->slug)}}" class="link-none">
			@include('components.tags.new', ['is_new' => $infograph->is_new, 'color' => 'danger'])
			<img src="{{storage($infograph->thumbnail_path)}}" class="w-100">
		</a>
	</div>
</div>