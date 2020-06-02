@component('webapp.discover.cards.card', [
	'card' => $card,
	'type' => 'piece-card', 
	'url' => route('webapp.pieces.show', $card),
	'locked' => ! auth()->user()->isAuthorized(),
	'new' => $card->is_new])

@endcomponent