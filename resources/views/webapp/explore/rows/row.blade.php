<div class="mb-5">
	<div class="d-flex d-apart gap-2 mb-3">
	<h2 class="h5 mb-0">{{$data['label']}}</h2>
		{{ $action ?? null }}
		@isset($link)
		<a href="{{$link['url']}}" class="btn-raw link-primary">{{$link['label']}} @icon('arrow-right', ['ml' => 1, 'mr' => 0])</a>
		@endisset
	</div>
	{{$slot}}
</div>