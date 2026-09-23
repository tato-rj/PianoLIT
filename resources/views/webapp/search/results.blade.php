@foreach($pieces as $piece)
	@include('webapp.components.piece', compact('hasFullAccess'))
@endforeach
@guest('web')
    @if($pieces->isNotEmpty())
        <div class="text-center py-4" data-search-signup>
            <p>Visitors can view the first 3 search results. Sign up to see more.</p>
            <a href="{{ route('register') }}" class="btn btn-primary rounded-pill mr-2">Sign up</a>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary rounded-pill">Sign in</a>
        </div>
    @endif
@endguest
