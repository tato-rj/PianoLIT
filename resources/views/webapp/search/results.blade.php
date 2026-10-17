@foreach($pieces as $piece)
    @include('webapp.components.piece', compact('hasFullAccess'))
@endforeach
@php($searchCap = app(\App\Services\WebApp\AccessPolicy::class)->cap('search', auth('web')->user()))
@if($searchCap !== null && ($pieces->isNotEmpty() || $searchCap === 0))
    <div class="text-center py-4" data-search-signup>
        <p>{{ $searchCap === 0 ? 'Search results are currently locked.' : 'You can view the first 3 search results.' }}</p>
        @guest('web')<p>{{ app(\App\Services\WebApp\AccessPolicy::class)->snapshot()['rules']['account']['search']['registered'] === 'available' ? 'Sign up free to see more.' : 'Create your free account to explore PianoLIT.' }}</p>@endguest
        @include('webapp.components.access-action', ['accessFeature' => 'search'])
    </div>
@endif
