@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Which 3 moods do you like the most?'])

@include('funnels.find-your-match.components.buttons', [
	'buttons' => $tags
])

@endcomponent