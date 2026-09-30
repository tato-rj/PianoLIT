@auth('web')
@php($is_favorited = ($piece->webapp_is_favorited ?? $piece->isFavorited(auth()->id())) > 0)

<button class="btn-raw t-2" id="flag-{{$piece->id}}" data-dismiss="fixed-panel" data-bs-toggle="offcanvas" data-bs-target="#save-to-offcanvas" aria-controls="save-to-offcanvas" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}" style="font-size: 120%">
	@icon('heart', ['color' => 'red', 'filled' => $is_favorited])
</button>
@endauth
