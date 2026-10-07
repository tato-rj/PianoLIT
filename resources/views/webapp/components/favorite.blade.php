@auth('web')
@php($is_favorited = ($piece->webapp_is_favorited ?? $piece->isFavorited(auth()->id())) > 0)

<button class="btn-raw t-2 {{ $favoriteClasses ?? '' }}" id="{{ !empty($directToggle) ? 'match-' : '' }}flag-{{$piece->id}}"
    @if(!empty($directToggle))
    data-submit="favorite" data-target="#match-flag-{{ $piece->id }}" data-url="{{ route('webapp.users.favorites.update', $piece) }}" aria-pressed="{{ $is_favorited ? 'true' : 'false' }}" aria-label="Favorite {{ $piece->medium_name }}"
    @else
    data-bs-toggle="offcanvas" data-bs-target="#save-to-offcanvas" aria-controls="save-to-offcanvas" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}"
    @endif
    type="button" style="font-size: 120%">
	@icon('heart', ['color' => 'red', 'filled' => $is_favorited])
</button>
@endauth
