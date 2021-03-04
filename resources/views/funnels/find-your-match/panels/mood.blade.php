@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Select your top 3 favorite types of pieces?'])

@include('funnels.find-your-match.components.buttons', [
	'buttons' => \App\Tag::mood()->get()->sortByDesc('pieces_count')->take(12)->pluck('name')
])

@endcomponent