<section id="folders-list" class="row"> 
@forelse(auth()->user()->favoriteFolders()->lastUpdated()->get() as $folder)
	@include('webapp.user.my-pieces.favorites.folder-sm')
@empty
	@include('webapp.components.empty', [
		'icon' => 'empty-favorites', 
		'title' => 'No favorites yet', 
		'subtitle' => 'Tap <i class="fas fa-heart"></i> to add a piece to your favorites'])
@endforelse
</section>