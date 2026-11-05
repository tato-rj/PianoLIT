<article class="shop-card border-0 card rounded-0 overflow-hidden h-100">
    <div class="shop-card__art">
        <a href="{{ $product['url'] }}" tabindex="-1" aria-hidden="true">
            @if($product['imageUrl'])
                <img class="shop-card__overview" src="{{ $product['imageUrl'] }}" alt="" width="600" height="600" loading="lazy">
                @if(!empty($product['closeupUrl']))
                    <img class="shop-card__detail" src="{{ $product['closeupUrl'] }}" alt="" width="600" height="600" loading="lazy" data-card-closeup>
                @endif
            @else
                <span class="shop-image-placeholder">@icon('image')<span>Image coming soon</span></span>
            @endif
        </a>
    </div>
    <div class="shop-card__copy">
        <{{ $cardHeading ?? 'h2' }} class="shop-card__title clamp-1"><a href="{{ $product['url'] }}">{{ $product['name'] }}</a></{{ $cardHeading ?? 'h2' }}>
        @include('shop.components.reviews.summary', ['reviewUrl' => $product['url'].'#reviews', 'reviewCount' => $product['reviewCount'] ?? 0, 'averageRating' => $product['averageRating'] ?? 0, 'compact' => true])
        <p class="shop-card__price mb-0">@if($product['price']){{ $product['priceVaries'] ? 'From ' : '' }}{{ $product['price'] }}@else {{ $product['variants'] ? 'Choose an option' : 'Currently unavailable' }} @endif</p>
{{--         @if($product['digital'])
            <a class="shop-card__action btn btn-primary px-2 w-100" href="{{ $product['url'] }}" aria-label="View {{ $product['name'] }}"><span class="d-sm-none">Details</span><span class="d-none d-sm-inline">View product @icon('arrow-up-right', ['mr' => 0])</span></a>
        @elseif(count($product['variants']) === 1)
            <form action="{{ route('shop.bag.add') }}" method="POST" class="shop-card__action w-100" data-add-to-bag>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                <input type="hidden" name="variant_id" value="{{ $product['variants'][0]['id'] }}">
                <input type="hidden" name="quantity" value="1">
                <button class="btn btn-primary px-2 w-100" type="submit" aria-label="Add {{ $product['name'] }} to bag">Add to bag @icon('shopping-bag', ['mr' => 0, 'classes' => 'd-none d-sm-inline'])</button>
            </form>
        @elseif(count($product['variants']) > 1)
            <a class="shop-card__action btn btn-primary px-2 w-100" href="{{ $product['url'] }}" aria-label="Choose options for {{ $product['name'] }}"><span class="d-sm-none">Options</span><span class="d-none d-sm-inline">Choose options @icon('arrow-up-right', ['mr' => 0])</span></a>
        @endif --}}
    </div>
</article>
