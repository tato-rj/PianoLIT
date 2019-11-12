<div class="col-lg-3 col-md-4 col-6 mb-3 infograph-card rounded-0 thumbnail t-2 thumbnail-{{$infograph->type}}" style="border-color: rgba(0,0,0,0.06);" 
	data-url="{{route('infographs.download', $infograph->slug)}}" 
	data-review-url="{{route('infographs.update-score', $infograph->slug)}}" 
	data-image="{{storage($infograph->cover_path)}}" 
	data-name="{{$infograph->name}}"
	data-description="{{$infograph->description}}"
	data-type="{{ucfirst($infograph->type)}}"
	data-toggle="modal" data-target="#infograph-modal">
	
	<div class=" cursor-pointer border position-relative">
		@include('components.cards.new', ['is_new' => $infograph->is_new, 'color' => 'danger'])
		<img src="{{storage($infograph->thumbnail_path)}}" class="w-100">
	</div>
</div>