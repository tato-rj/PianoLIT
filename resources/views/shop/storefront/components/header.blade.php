@component('components.layout.public.header', ['navigationLabel' => 'Bag and account'])
    @slot('navigationBeforeAccount')
        <li><a class="d-inline-flex align-items-center gap-2 text-body text-decoration-none" href="{{ route('shop.bag') }}" @if(request()->routeIs('shop.bag')) aria-current="page" @endif>@icon('shopping-bag', ['mr' => 0])<span>Bag (<span data-bag-count>{{ $shoppingBag['count'] }}</span>)</span></a></li>
    @endslot
@endcomponent
