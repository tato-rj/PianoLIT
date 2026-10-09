<div class="mb-5">
	<div class="d-flex d-apart">
	<h5>{{$data['label']}}</h5>
		@isset($link)
		<a href="{{$link['url']}}" class="btn-raw link-primary">{{$link['label']}}</a>
		@endisset
	</div>
	{{$slot}}
</div>