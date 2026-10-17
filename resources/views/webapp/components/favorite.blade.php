@auth('web')
@php($favoritePolicy = app(\App\Services\WebApp\AccessPolicy::class))
@php($favoritesLocked = $favoritePolicy->cap('folder_pieces', auth('web')->user()) === 0)
@php($is_favorited = ($favoriteSaved ?? $piece->webapp_is_favorited ?? $piece->favorites()->where('user_id', auth('web')->id())->whereNotNull('favorite_folder_id')->exists()) > 0)

<button class="btn-raw t-2 {{ $favoriteClasses ?? '' }}" id="flag-{{$piece->id}}"
    data-favorite-piece="{{ $piece->id }}" data-favorited="{{ $is_favorited ? 'true' : 'false' }}" aria-pressed="{{ $is_favorited ? 'true' : 'false' }}" aria-label="Favorite {{ $piece->medium_name }}"
    @if($favoritesLocked && !$is_favorited)
    data-bs-toggle="modal" data-bs-target="{{ $favoritePolicy->promptTarget(auth('web')->user(), 'favorites') }}"
    @else
    data-bs-toggle="offcanvas" data-bs-target="#save-to-offcanvas" aria-controls="save-to-offcanvas" data-manage="save-to" data-url="{{route('webapp.pieces.save-to', $piece)}}"
    @endif
    type="button" style="{{ $favoriteStyle ?? 'font-size: 120%' }}">
	@icon('heart', ['color' => $favoriteColor ?? 'red', 'filled' => $is_favorited, 'mr' => $favoriteIconMargin ?? 2])
</button>
@else
<button class="btn-raw t-2 {{ $favoriteClasses ?? '' }}" type="button" style="{{ $favoriteStyle ?? 'font-size: 120%' }}"
    data-bs-toggle="modal" data-bs-target="#signup-favorites-modal" aria-label="Sign up to favorite {{ $piece->medium_name }}">
    @icon('heart', ['color' => $favoriteColor ?? 'red', 'mr' => $favoriteIconMargin ?? 2])
</button>
@endauth
