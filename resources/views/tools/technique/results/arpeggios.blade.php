<div class="row">
	@include('tools.technique.components.tabs', [
		'collection' => $arpeggio['positions']
	])
</div>

<div class="tab-content" id="pills-tabContent">
	@foreach($arpeggio['positions'] as $result)
		@include('tools.technique.results.show')
	@endforeach
</div>

@include('tools.technique.components.reload')