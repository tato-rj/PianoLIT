@component('webapp.discover.cards.card', [
	'card' => $card,
	'type' => 'search-card', 
	'url' => route('webapp.search.results', ['for' => $card->name]),
	'locked' => false,
	'new' => false])

@endcomponent