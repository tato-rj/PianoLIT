@php($pieces = (new \App\Resources\FindYourMatch\Quiz)->showPieces())

@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Pick the 3 pieces you like the most'])

@include('funnels.find-your-match.components.audio', [
	'media' => $pieces
])

@endcomponent