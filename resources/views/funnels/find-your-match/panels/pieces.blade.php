@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Pick your top 3 favorite pieces'])

@include('funnels.find-your-match.components.audio', [
	'media' => $pieces
])

@endcomponent