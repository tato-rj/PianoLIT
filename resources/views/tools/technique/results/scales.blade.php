<div class="row">
	@include('tools.technique.components.tabs', [
		'collection' => $scale['modes']
	])
</div>

<div class="tab-content" id="pills-tabContent">
	@foreach($scale['modes'] as $result)
		@include('tools.technique.results.show')
	@endforeach
</div>

@include('tools.technique.components.reload')