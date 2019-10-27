<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox" 
		data-target="#status-{{$infograph->slug}}" {{$infograph->$attribute ? 'checked' : null}} 
		data-url="{{route('admin.infographs.update-status', ['infograph' => $infograph->slug, 'attribute' => $attribute])}}">
	<span class="slider round"></span>
</label>