@php
    $hubLinks = [
        ['label' => 'Home', 'url' => route('home'), 'icon' => 'home', 'color' => 'blue'],
        ['label' => 'Shop', 'url' => route('shop.home'), 'icon' => 'shopping-basket', 'color' => 'orange'],
        ['label' => 'Games', 'url' => route('games.home'), 'icon' => 'trophy', 'color' => 'pink'],
        ['label' => 'Learn', 'url' => route('learn.home'), 'icon' => 'graduation-cap', 'color' => 'green'],
        ['label' => 'Blog', 'url' => route('posts.index'), 'icon' => 'newspaper', 'color' => 'indigo'],
        ['label' => 'Metronome', 'url' => route('metronome.home'), 'icon' => 'metronome', 'color' => 'red'],
    ];
@endphp
<div class="offcanvas offcanvas-end hub-menu" id="public-hub-menu" tabindex="-1" aria-labelledby="public-hub-menu-title">
    @include('components.side-menu-header', ['titleId' => 'public-hub-menu-title', 'title' => 'Explore PianoLIT', 'closeLabel' => 'Close hub menu'])
    <div class="offcanvas-body px-4 py-3">
        <nav aria-label="PianoLIT hubs">
            <ul class="nav flex-column gap-2 mb-3 mx-n3" aria-label="PianoLIT apps">
                <li class="nav-item bg-light shw rounded border px-2 py-1">
                    <a href="{{ route('webapp.discover') }}" class="nav-link p-0 text-dark d-flex align-items-center" target="_blank" rel="noopener noreferrer">
                        @icon('pianolit', ['color' => 'black', 'mr' => 2, 'size' => 'lg', 'classes' => 'icon-fw'])
                        <span class="me-auto">Web app</span>
                        @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])
                    </a>
                </li>
                <li class="nav-item bg-light shw rounded border px-2 py-1">
                    <a href="{{ config('app.stores.ios') }}" class="nav-link p-0 text-dark d-flex align-items-center" target="_blank" rel="noopener noreferrer">
                        <span class="d-inline-flex" aria-hidden="true">@fa(['icon' => 'apple', 'fa_type' => 'b', 'color' => 'black', 'mr' => 2, 'classes' => 'icon-size-lg icon-fw', 'title' => ''])</span>
                        <span class="me-auto">iOS app</span>
                        @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'ml' => 4])
                    </a>
                </li>
            </ul>
            <ul class="nav flex-column gap-2 mb-0">
                @foreach($hubLinks as $hubLink)
                    @php($currentHub = request()->getHost() === parse_url($hubLink['url'], PHP_URL_HOST))
                    <li class="nav-item">
                        <a href="{{ $hubLink['url'] }}" class="nav-link p-0 text-dark d-flex align-items-center{{ $currentHub ? ' fw-semibold' : '' }}" @if($currentHub) aria-current="true" @endif>
                            @icon($hubLink['icon'], ['color' => $hubLink['color'], 'mr' => 3, 'size' => 'lg'])
                            <span>{{ $hubLink['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</div>
