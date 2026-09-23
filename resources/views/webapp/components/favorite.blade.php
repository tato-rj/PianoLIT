@auth('web')
@php($is_favorited = ($piece->webapp_is_favorited ?? $piece->isFavorited(auth()->id())) > 0)

<button class="btn-raw t-2" id="flag-{{$piece->id}}" data-dismiss="fixed-panel" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}" style="font-size: 120%">
	@fa(['icon' => 'heart', 'fa_type' => $is_favorited ? 's' : 'r', 'color' => 'red'])
</button>
@endauth
