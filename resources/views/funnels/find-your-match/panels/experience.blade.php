@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'How long have you been playing the piano?'])

@include('funnels.find-your-match.components.answers', [
	'answers' => [
		'I am a newbie, just getting started' => 'elementary',
		'About 1 or 2 years' => 'beginner',
		'Somewhere around 3 or 4 years' => 'beginner',
		'It\'s been many years' => 'advanced',
		'I used to play well but haven\'t done much in quite some time' => 'intermediate',
	]
])

@endcomponent