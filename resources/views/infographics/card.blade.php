<div class="infograph-card rounded-0 thumbnail t-2 w-100" style="border-color: rgba(0,0,0,0.06);">
	<div class="position-relative no-click">
		<a href="{{route('resources.infographs.show', $infograph->slug)}}" class="link-none">
			@include('components.cards.new', ['is_new' => $infograph->is_new, 'color' => 'danger'])
			<img src="{{storage($infograph->thumbnail_path)}}" style="width: 95%" class="border mb-3">
		</a>
	</div>
</div>