<div class="col-lg-3 col-md-4 col-6 mb-3
	@foreach($infograph->topics as $topic)
	thumbnail-{{$topic->slug}}
	@endforeach
  infograph-card rounded-0 thumbnail t-2" style="border-color: rgba(0,0,0,0.06);" data-name="{{$infograph->name}}">
	<div class="border position-relative no-click">
		<a href="{{route('resources.infographs.show', $infograph->slug)}}" class="link-none">
			@include('components.cards.new', ['is_new' => $infograph->is_new, 'color' => 'danger'])
			<img src="{{storage($infograph->thumbnail_path)}}" class="w-100">
		</a>
	</div>
</div>