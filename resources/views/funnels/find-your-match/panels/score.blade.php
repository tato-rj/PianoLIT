@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Does this score look like something easy or difficult for you?'])

<img src="{{asset('images/misc/score1.png')}}" class="w-100">

@include('funnels.find-your-match.components.answers', [
	'answers' => [
		'I\'ve no idea how to read this' => 'elementary',
		'It looks hard, it would take me a little while' => 'beginner',
		'Not too difficult, but not too easy either' => 'beginner',
		'This looks pretty simple' => 'intermediate',
		'I could sight read this with no problem' => 'advanced'
	]
])

@endcomponent