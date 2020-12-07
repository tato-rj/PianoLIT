@php($is_favorited = $piece->isFavorited(auth()->user()->id))
{{-- @env('local')
<button class="btn-raw t-2" data-dismiss="fixed-panel" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}" style="font-size: 120%">
	@fa(['icon' => 'bookmark', 'fa_type' => $is_favorited ? 's' : 'r', 'color' => 'red'])
</button>
@else --}}
<button class="btn-raw t-2" data-manage="favorite" data-url-toggle="{{route('webapp.users.favorites.update', $piece->id)}}" data-favorited="{{$is_favorited}}" style="font-size: 120%" title="Click to favorite/unfavorite">
	@fa(['icon' => 'heart', 'fa_type' => $is_favorited ? 's' : 'r', 'color' => 'red'])
</button>
{{-- @endenv --}}