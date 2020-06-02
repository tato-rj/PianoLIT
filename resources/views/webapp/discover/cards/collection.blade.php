@component('webapp.discover.cards.card', [
	'card' => $card,
	'type' => 'search-card', 
	'url' => route('webapp.search.results', ['search' => $card->name]),
	'locked' => false,
	'new' => false])

@endcomponent