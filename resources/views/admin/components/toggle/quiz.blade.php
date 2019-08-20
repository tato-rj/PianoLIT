<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox" data-target="#status-{{$quiz->slug}}" {{$quiz->published_at ? 'checked' : null}} data-url="{{route('admin.quizzes.update-status', $quiz->slug)}}">
	<span class="slider round"></span>
</label>