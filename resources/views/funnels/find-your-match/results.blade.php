@component('components.modal', [
	'id' => $modalId ?? 'match-modal',
	'data' => ['piece-id' => $piece->id],
	'options' => [
	    'header' => ['show' => false],
		'body' => ['padding' => 0],
	    'footer' => ['raw' => true],
]])
@slot('body')
@include('funnels.find-your-match.result-body')
@endslot

@slot('footer')
@include('funnels.find-your-match.result-footer')
@endslot
@endcomponent
