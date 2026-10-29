@php
    $hubLinks = [
        ['label' => 'Home', 'url' => \App\Support\ProfileUrl::base()],
        ['label' => 'Web app', 'url' => route('webapp.discover')],
        ['label' => 'iOS app', 'url' => config('app.stores.ios'), 'external' => true],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Games', 'url' => route('games.home')],
        ['label' => 'Shop', 'url' => route('shop.home')],
        ['label' => 'Metronome', 'url' => route('metronome.home')],
        ['label' => 'Blog', 'url' => route('posts.index')],
    ];
@endphp
<nav class="hub-menu__links" aria-label="PianoLIT hubs">
    <details class="hub-menu__disclosure">
        <summary class="account-menu__link">
            @icon('layers', ['mr' => 0])<span>PianoLIT Hubs</span>
            @icon('chevron-down', ['mr' => 0, 'classes' => 'hub-menu__toggle ms-auto'])
        </summary>
        <ul class="nav flex-column mb-0 hub-menu__children">
            @foreach($hubLinks as $hubLink)
                @php
                    $currentHub = ! ($designSystemPreview ?? false) && ! ($accountItemIsCurrent ?? false)
                        && empty($hubLink['external'])
                        && ($hubLink['label'] === 'Home'
                            ? request()->routeIs('home')
                            : request()->getHost() === parse_url($hubLink['url'], PHP_URL_HOST));
                @endphp
                <li class="nav-item">
                    <a href="{{ $hubLink['url'] }}" class="hub-menu__link d-flex align-items-center gap-3" @if($currentHub) aria-current="true" @endif @if($hubLink['external'] ?? false) target="_blank" rel="noopener noreferrer" @endif>
                        <span class="me-auto">{{ $hubLink['label'] }}</span>
                        @icon('chevron-right', ['mr' => 0, 'classes' => 'hub-menu__arrow'])
                    </a>
                </li>
            @endforeach
        </ul>
    </details>
</nav>
