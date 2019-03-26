<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox"  {{$post->is_published ? 'checked' : null}} data-url="{{route('admin.posts.update-status', $post->slug)}}">
	<span class="slider round"></span>
</label>