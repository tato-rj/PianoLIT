@env('local')
@include('webapp.user.my-pieces.favorites.index')
@else
@if(auth()->user()->favorites()->exists())
@include('webapp.components.sorting', ['disabled' => false, 'env' => 'local'])
@endif

<section id="pieces-list"> 
@forelse(auth()->user()->favorites as $piece)
	@include('webapp.components.piece', compact('hasFullAccess'))
@empty
	@include('webapp.components.empty', [
		'icon' => 'empty-favorites', 
		'title' => 'No favorites yet', 
		'subtitle' => 'Tap <i class="fas fa-heart"></i> to add a piece to your favorites'])
@endforelse
</section>
@endenv