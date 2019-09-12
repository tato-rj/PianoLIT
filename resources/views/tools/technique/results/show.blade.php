<div class="tab-pane fade {{$loop->first ? 'show active' : null}}" id="pills-{{str_slug($result['name'])}}" role="tabpanel" aria-labelledby="pills-{{str_slug($result['name'])}}-tab">
	<div class="row mb-4">
	
	@if(\View::exists('tools.technique.components.alerts.'.str_slug($result['name'])))
	    @include('tools.technique.components.alerts.'.str_slug($result['name']))
	@endif

	@include('tools.technique.components.notes', [
		'label' => 'The notes in the <strong class="text-dark">'.noteToHumans($result['key']).'</strong> are',
		'notes' => $result['notes']
	])

	</div>
	<div class="row">
		<div class="col-12 mb-3 text-center" id="keyboard-{{str_slug($result['name'])}}">
			@include('tools.technique.components.keyboard')
			@include('tools.technique.components.fingering')
		</div>
	
		@include('tools.technique.components.hands')
	</div>
</div>