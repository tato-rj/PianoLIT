@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'How long have you been playing the piano?'])

@include('funnels.find-your-match.components.answers', [
	'answers' => [
		'I am a newbie, just getting started' => 'elementary',
		'Between one and two years' => 'beginner',
		'More than three years' => 'beginner',
		'It\'s been more than eight years' => 'intermediate',
		'I played for some times years ago but haven\'t done much ever since' => 'advanced'
	]
])

@endcomponent