@php($is_favorited = $folder->piece_favorites_count > 0)
<button class="p-3 d-flex d-apart bg-light mb-2 rounded btn btn-light w-100" 
	data-submit="favorite" data-target="#flag-{{$piece->id}}" data-url="{{route('webapp.users.favorites.update', ['piece' => $piece, 'folder_id' => $folder->id])}}">
	<div class="font-weight-bold">{{$folder->name}} <span class="badge bg-white text-muted border">{{$folder->favorites_count}}</span></div>
	<div class="favorite-icons">
		@icon('circle-dot', ['name' => 'saved', 'size' => 'lg', 'color' => 'blue', 'if' => $is_favorited])
		@icon('circle', ['name' => 'unsaved', 'size' => 'lg', 'color' => 'blue', 'if' => ! $is_favorited])
		@icon('check', ['name' => 'success', 'size' => 'lg', 'color' => 'green', 'if' => false])
	</div>
</button>