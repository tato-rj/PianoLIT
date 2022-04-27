@component('webapp.discover.cards.card', [
	'card' => $card,
	'type' => 'piece-card', 
	'url' => route('webapp.pieces.show', $card),
	'locked' => ! $hasFullAccess,
	'new' => $card->is_new])

@endcomponent