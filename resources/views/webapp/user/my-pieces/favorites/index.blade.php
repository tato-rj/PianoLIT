<section id="folders-list" class="row"> 
@forelse(auth()->user()->favoriteFolders()->alphabetically()->get() as $folder)
	@include('webapp.user.my-pieces.favorites.folders.folder')
	@include('webapp.user.my-pieces.favorites.delete')
	@include('webapp.user.my-pieces.favorites.edit')
@empty
	@include('webapp.components.empty', [
		'icon' => 'empty-favorites', 
		'title' => 'No favorites yet', 
		'subtitle' => 'Tap <i class="fas fa-heart"></i> to add a piece to your favorites'])
@endforelse
</section>