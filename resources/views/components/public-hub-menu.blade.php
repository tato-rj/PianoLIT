@php
    $hubLinks = [
        ['label' => 'PianoLIT home', 'url' => route('home')],
        ['label' => 'Web app', 'url' => route('webapp.discover')],
        ['label' => 'iOS app', 'url' => config('app.stores.ios')],
        ['label' => 'Shop', 'url' => route('shop.home')],
        ['label' => 'Games', 'url' => route('games.home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Blog', 'url' => route('posts.index')],
        ['label' => 'Metronome', 'url' => route('metronome.home')],
    ];
@endphp
<div class="offcanvas offcanvas-end hub-menu" id="public-hub-menu" tabindex="-1" aria-labelledby="public-hub-menu-title">
    <div class="offcanvas-header p-4 border-bottom">
        <h2 class="offcanvas-title h5 mb-0 mr-4" id="public-hub-menu-title">Explore PianoLIT</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close hub menu"></button>
    </div>
    <div class="offcanvas-body p-4">
        <nav aria-label="PianoLIT hubs">
            <ul class="nav flex-column gap-3 mb-0">
                @foreach($hubLinks as $hubLink)
                    @php($currentHub = request()->getHost() === parse_url($hubLink['url'], PHP_URL_HOST))
                    <li class="nav-item">
                        <a href="{{ $hubLink['url'] }}" class="nav-link p-0 text-dark{{ $currentHub ? ' fw-semibold' : '' }}" @if($hubLink['url'] === config('app.stores.ios')) target="_blank" rel="noopener noreferrer" @endif @if($currentHub) aria-current="true" @endif>
                            <span>{{ $hubLink['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</div>
