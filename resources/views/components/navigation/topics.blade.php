<div class="d-flex flex-wrap mb-2">
	@foreach($model->topics as $topic)
	<a href="{{route($model->route, ['topics' => $topic->slug])}}" class="btn btn-light m-1 btn-sm text-muted">{{ucfirst($topic->name)}}</a>
	@endforeach
</div>