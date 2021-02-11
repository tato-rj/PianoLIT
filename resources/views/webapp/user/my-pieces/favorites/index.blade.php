<section id="folders-list" class="row"> 
	<div class="col-12 mb-3">
		<div class="d-flex d-apart">
			<div class="text-muted"><small>You have <strong>{{auth()->user()->favoriteFolders()->count()}}</strong> {{str_plural('folder', auth()->user()->favoriteFolders()->count())}}</small></div>
			<button data-toggle="modal" data-target="#new-folder-modal" class="btn btn-outline-secondary btn-sm">@fa(['icon' => 'plus']) New folder</button>

			@component('components.modal', ['id' => 'new-folder-modal', 'header' => 'New folder'])
			@slot('body')
			<form method="POST" action="{{route('webapp.users.favorites.folders.store')}}">
				@csrf
				@input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Folder name'])
				@submit(['label' => 'Create folder', 'block' => true])
			</form>
			@endslot
			@endcomponent
		</div>
	</div>
@forelse(auth()->user()->favoriteFolders()->alphabetical('name')->get() as $folder)
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