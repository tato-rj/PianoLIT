@php($collectionLinkPolicy = app(\App\Services\WebApp\AccessPolicy::class))
@if($collectionLinkPolicy->cap('collections', auth('web')->user()) === 0)
    @php($collectionLinkPrompt = $collectionLinkPolicy->promptTarget(auth('web')->user(), 'collections'))
    href="{{ $collectionLinkPrompt }}" data-bs-toggle="modal" data-bs-target="{{ $collectionLinkPrompt }}" aria-haspopup="dialog" role="button" data-collection-blocked
@else
    href="{{ route('webapp.playlists.show', $playlist) }}"
@endif
