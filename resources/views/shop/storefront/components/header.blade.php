<header class="container pt-4 pt-md-5 shop-header">
    <div class="row align-items-center justify-content-between gy-3 mx-lg-4">
        <div class="col-auto">
            <a class="shop-brand d-inline-flex align-items-center gap-3" href="{{ route('shop.home') }}" aria-label="PianoLIT Shop home">
                @brandIcon(['size' => 'clamp(48px, 7vw, 64px)', 'tilt' => true])
                <span>Shop</span>
            </a>
        </div>
        <nav class="col-auto ms-auto" aria-label="Bag and account">
            <ul class="list-unstyled d-flex align-items-center gap-3 gap-md-4 mb-0">
                <li><a class="shop-bag-link" href="{{ route('shop.bag') }}" @if(request()->routeIs('shop.bag')) aria-current="page" @endif>@icon('shopping-bag')<span>Bag (<span data-bag-count>{{ $shoppingBag['count'] }}</span>)</span></a></li>
                @include('layouts.menu.user', ['accountCircle' => true])
            </ul>
        </nav>
    </div>
</header>
