<div class="infograph-card rounded-0 thumbnail p-1 col-lg-3 col-md-4 col-6">
	<div class="position-relative no-clickx border" style="overflow-y: hidden;">
		<a href="{{route('resources.infographs.show', $infograph->slug)}}" class="link-none">
			@include('components.cards.new', ['is_new' => $infograph->is_new, 'color' => 'danger'])
			<img src="{{storage($infograph->thumbnail_path)}}" class="w-100">
		</a>
	</div>
</div>