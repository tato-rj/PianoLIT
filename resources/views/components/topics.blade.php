<div class="d-flex flex-wrap mb-2">
	@foreach($topics as $topic)
	@isset($route)
	<a href="{{route($route, $topic->slug)}}" class="btn btn-light m-1 btn-sm text-muted">{{$topic->name}}</a>
	@else
	<button class="btn btn-light m-1 btn-sm text-muted">{{$topic->name}}</button>
	@endisset
	@endforeach
</div>