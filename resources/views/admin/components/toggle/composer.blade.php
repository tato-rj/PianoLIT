<label class="switch cursor-pointer">
	<input class="famous-toggle" type="checkbox" 
		{{$composer->is_famous ? 'checked' : null}} 
		data-url="{{route('admin.composers.toggle-famous', $composer->id)}}">
	<span class="slider round"></span>
</label>