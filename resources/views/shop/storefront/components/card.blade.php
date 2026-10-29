<article class="shop-card h-100">
    <div class="shop-card__art">
        <a href="{{ $product['url'] }}" tabindex="-1" aria-hidden="true">
            @if($product['imageUrl'])
                <img src="{{ $product['imageUrl'] }}" alt="" width="600" height="600" loading="lazy">
            @else
                <span class="shop-image-placeholder">@icon('image')<span>Image coming soon</span></span>
            @endif
        </a>
        @if($product['digital'])
            <a class="shop-card__quick shop-button shop-button--dark" href="{{ $product['url'] }}">View product @icon('arrow-up-right')</a>
        @elseif(count($product['variants']) === 1)
            <form action="{{ route('shop.bag.add') }}" method="POST" class="shop-card__quick" data-add-to-bag>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                <input type="hidden" name="variant_id" value="{{ $product['variants'][0]['id'] }}">
                <input type="hidden" name="quantity" value="1">
                <button class="shop-button shop-button--dark w-100" type="submit" aria-label="Quick add {{ $product['name'] }}">Quick add @icon('plus')</button>
            </form>
        @elseif(count($product['variants']) > 1)
            <a class="shop-card__quick shop-button shop-button--dark" href="{{ $product['url'] }}">Choose options @icon('arrow-up-right')</a>
        @endif
    </div>
    <div class="shop-card__copy">
        <{{ $cardHeading ?? 'h2' }} class="shop-card__title"><a href="{{ $product['url'] }}">{{ $product['name'] }}</a></{{ $cardHeading ?? 'h2' }}>
        @if($product['digital'] && !empty($product['subtitle']) && $product['subtitle'] !== $product['name'])
            <p class="shop-card__subtitle">{{ $product['subtitle'] }}</p>
        @endif
        <p class="shop-card__price mb-0">@if($product['price']){{ $product['priceVaries'] ? 'From ' : '' }}{{ $product['price'] }}@else {{ $product['variants'] ? 'Choose an option' : 'Currently unavailable' }} @endif</p>
    </div>
</article>
