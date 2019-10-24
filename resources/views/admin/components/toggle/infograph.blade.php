<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox" data-target="#status-{{$infograph->slug}}" {{$infograph->published_at ? 'checked' : null}} data-url="{{route('admin.infographs.update-status', $infograph->slug)}}">
	<span class="slider round"></span>
</label>