@include('webapp.components.sorting', ['disabled' => false, 'env' => 'local'])

<section id="pieces-list"> 
@forelse(auth()->user()->favorites as $piece)
	@include('webapp.components.piece')
@empty
	@include('webapp.components.empty', [
		'icon' => 'empty-favorites', 
		'title' => 'No favorites yet', 
		'subtitle' => 'Press <i class="fas fa-heart"></i> to add a piece to your favorites'])
@endforelse
</section>