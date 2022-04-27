@component('webapp.discover.cards.card', [
	'card' => $card,
	'type' => 'piece-card', 
	'color' => $color ?? $card->color,
	'url' => route('webapp.pieces.show', $card),
	'locked' => ! $hasFullAccess,
	'new' => $card->is_new])

@endcomponent