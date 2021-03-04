@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Which is your biggest challenge right now?'])

@include('funnels.find-your-match.components.answers', [
	'answers' => [
		'Sight-reading' => 'beginner',
		'Music theory' => 'intermediate',
		'Playing fast pieces' => 'fast',
		'Memorization' => 'intermediate',
		'Motivation' => 'intermediate',
		'I\'m a newbie, it\'s all a challenge' => 'dreamy'
	]
])

@endcomponent