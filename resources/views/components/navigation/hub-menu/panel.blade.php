@php
    $designSystemPreview = $designSystemPreview ?? false;
    $hubMenuId = $designSystemPreview ? 'ds-public-hub-menu' : 'public-hub-menu';
    $hubLinks = [
        ['label' => 'Home', 'url' => route('home'), 'icon' => 'home'],
        ['label' => 'Shop', 'url' => route('shop.home'), 'icon' => 'shopping-basket'],
        ['label' => 'Games', 'url' => route('games.home'), 'icon' => 'trophy'],
        ['label' => 'Learn', 'url' => route('learn.home'), 'icon' => 'graduation-cap'],
        ['label' => 'Blog', 'url' => route('posts.index'), 'icon' => 'newspaper'],
        ['label' => 'Metronome', 'url' => route('metronome.home'), 'icon' => 'metronome'],
    ];
@endphp
<div class="{{ $designSystemPreview ? '' : 'offcanvas offcanvas-end ' }}hub-menu" id="{{ $hubMenuId }}" @if(!$designSystemPreview) tabindex="-1" @else data-ds-preview @endif aria-labelledby="{{ $hubMenuId }}-title">
    @include('components.layout.side-menu-header', ['titleId' => $hubMenuId.'-title', 'title' => 'Explore PianoLIT', 'closeLabel' => 'Close hub menu', 'designSystemPreview' => false])
    <div class="offcanvas-body px-3 pt-3 pb-4">
        <nav aria-label="PianoLIT hubs">
            <ul class="nav flex-column gap-1 mb-3 pb-3 hub-menu__apps" aria-label="PianoLIT apps">
                <li class="nav-item">
                    <a href="{{ route('webapp.discover') }}" class="hub-menu__link d-flex align-items-center gap-3" target="_blank" rel="noopener noreferrer">
                        @icon('pianolit', ['mr' => 0, 'classes' => 'hub-menu__icon'])
                        <span class="me-auto">Web app</span>
                        @icon('chevron-right', ['mr' => 0, 'classes' => 'hub-menu__arrow'])
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ config('app.stores.ios') }}" class="hub-menu__link d-flex align-items-center gap-3" target="_blank" rel="noopener noreferrer">
                        <span class="hub-menu__icon d-inline-flex align-items-center justify-content-center" aria-hidden="true">@fa(['icon' => 'apple', 'fa_type' => 'b', 'mr' => 0, 'title' => ''])</span>
                        <span class="me-auto">iOS app</span>
                        @icon('chevron-right', ['mr' => 0, 'classes' => 'hub-menu__arrow'])
                    </a>
                </li>
            </ul>
            <ul class="nav flex-column gap-1 mb-0">
                @foreach($hubLinks as $hubLink)
                    @php($currentHub = $designSystemPreview ? $hubLink['label'] === 'Shop' : request()->getHost() === parse_url($hubLink['url'], PHP_URL_HOST))
                    <li class="nav-item">
                        <a href="{{ $hubLink['url'] }}" class="hub-menu__link d-flex align-items-center gap-3" @if($currentHub) aria-current="true" @endif>
                            @icon($hubLink['icon'], ['mr' => 0, 'classes' => 'hub-menu__icon'])
                            <span>{{ $hubLink['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</div>
