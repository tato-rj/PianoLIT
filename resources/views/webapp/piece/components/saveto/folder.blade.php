@php($is_favorited = $folder->favorites()->where('piece_id', $piece->id)->exists())
<button class="p-3 d-flex d-apart bg-light mb-2 rounded btn btn-light w-100" 
	data-submit="favorite" data-url="{{route('api.users.favorites.update', ['piece_id' => $piece->id, 'user_id' => auth()->user()->id, 'folder_id' => $folder->id])}}">
	<div class="font-weight-bold">{{$folder->name}} <span class="badge bg-white text-muted border">{{$folder->favorites_count}}</span></div>
	<div class="favorite-icons">
		@fa(['name' => 'saved', 'icon' => 'dot-circle', 'size' => 'lg', 'fa_type' => 's', 'color' => 'blue', 'if' => $is_favorited])
		@fa(['name' => 'unsaved', 'icon' => 'circle', 'size' => 'lg', 'fa_type' => 'r', 'color' => 'blue', 'if' => ! $is_favorited])
		@fa(['name' => 'success', 'icon' => 'check', 'size' => 'lg', 'color' => 'green', 'if' => false])
	</div>
</button>