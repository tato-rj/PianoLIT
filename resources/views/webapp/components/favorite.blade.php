@php($is_favorited = $piece->isFavorited(auth()->user()->id))
<button class="btn-raw t-2" data-manage="favorite" data-url-toggle="{{route('api.users.favorites.update', ['user_id' => auth()->user()->id,'piece_id' => $piece->id])}}" data-piece-id="{{$piece->id}}" data-favorited="{{$is_favorited}}" style="font-size: 120%" title="Click to favorite/unfavorite">
	@fa(['icon' => 'heart', 'fa_type' => $is_favorited ? 's' : 'r', 'color' => 'red'])
</button>