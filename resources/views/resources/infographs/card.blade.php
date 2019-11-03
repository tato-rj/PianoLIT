<div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3 rounded-0 thumbnail t-2 thumbnail-{{$infograph->type}}" style="border-color: rgba(0,0,0,0.06);" 
	data-url="{{route('infographs.download', $infograph->slug)}}" 
	data-review-url="{{route('infographs.update-score', $infograph->slug)}}" 
	data-image="{{storage($infograph->cover_path)}}" 
	data-name="{{$infograph->name}}"
	data-description="{{$infograph->description}}"
	data-type="{{ucfirst($infograph->type)}}"
	data-toggle="modal" data-target="#infograph-modal">

	@include('components.cards.new', ['is_new' => $infograph->is_new])
	
	<div class=" cursor-pointer border">
		<img src="{{storage($infograph->thumbnail_path)}}" class="w-100">
	</div>
</div>