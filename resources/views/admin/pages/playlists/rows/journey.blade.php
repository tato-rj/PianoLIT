<div class="mt-3 playlist-container" data-url-reorder="{{route('admin.playlists.reorder')}}">
	@each('admin.pages.playlists.row', $journey, 'playlist')
</div>