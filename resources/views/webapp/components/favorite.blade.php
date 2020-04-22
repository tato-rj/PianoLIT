@php($is_favorited = $piece->isFavorited(auth()->user()->id))
<button class="btn-raw" style="font-size: 120%" title="Click to {{$is_favorited ? 'remove from favorites' : 'add to favorites'}}">
	@fa(['icon' => 'heart', 'fa_type' => $is_favorited ? 's' : 'r', 'color' => 'red'])
</button>