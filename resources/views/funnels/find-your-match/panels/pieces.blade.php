@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Pick your top 3 favorite pieces'])

@include('funnels.find-your-match.components.audio', [
	'media' => [
		storage(\App\Piece::find(1)->audio_path) => [\App\Piece::find(1)->name, \App\Piece::find(1)->composer->shortName, 1],
		storage(\App\Piece::find(5)->audio_path) => [\App\Piece::find(5)->name, \App\Piece::find(5)->composer->shortName, 5],
	]
])

@endcomponent