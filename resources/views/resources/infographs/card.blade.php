<div class="col-3 mb-4 thumbnail t-2 thumbnail-{{$infograph->type}}" 
	data-url="{{route('infographs.download', $infograph->slug)}}" 
	data-review-url="{{route('infographs.update-score', $infograph->slug)}}" 
	data-image="{{storage($infograph->cover_path)}}" 
	data-name="{{$infograph->name}}"
	data-description="{{$infograph->description}}"
	data-type="{{ucfirst($infograph->type)}}"
	data-toggle="modal" data-target="#infograph-modal">
	<div class="shadow-light cursor-pointer">
		<img src="{{storage($infograph->thumbnail_path)}}" class="w-100">
	</div>
</div>