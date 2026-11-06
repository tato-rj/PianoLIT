@if($product['variants'])
    @php
        $initialVariant = $product['selectedVariant'] ?? $product['variants'][0];
        $galleryColors = $product['galleryColors'];
        $productOptions = [
            'color' => array_values(array_unique(array_merge([$initialVariant['color']], array_column($product['variants'], 'color')))),
            'size' => array_unique(array_column($product['variants'], 'size')),
        ];
        $inBag = $shoppingBag['lines'][$product['id'].':'.$initialVariant['id']]['quantity'] ?? 0;
    @endphp
    <form action="{{ route('shop.bag.add') }}" method="POST" data-add-to-bag data-product-options>
        @csrf
        <input type="hidden" name="product_id" value="{{ $product['id'] }}">
        <input type="hidden" name="quantity" value="1">
        <div class="shop-field mb-4" data-variant-fallback>
            <label for="product-variant" class="shop-eyebrow mb-2">Choose an option</label>
            <select name="variant_id" id="product-variant" class="form-control" required>
                @foreach($product['variants'] as $variant)
                    <option value="{{ $variant['id'] }}" @if(!in_array($variant['color'], $galleryColors, true)) disabled @elseif($variant['id'] === $initialVariant['id']) selected @endif data-color="{{ $variant['color'] }}" data-size="{{ $variant['size'] }}" data-price="{{ $variant['price'] }}" data-in-bag="{{ $shoppingBag['lines'][$product['id'].':'.$variant['id']]['quantity'] ?? 0 }}">{{ $variant['color'] }} / {{ $variant['size'] }} — {{ $variant['price'] }}</option>
                @endforeach
            </select>
        </div>
        <div data-option-pickers hidden>
            @foreach(['color' => 'Color', 'size' => 'Size'] as $key => $label)
                <fieldset class="shop-options mb-4" data-option-group="{{ $key }}">
                    <legend class="shop-eyebrow mb-2">{{ $label }}</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($productOptions[$key] as $option)
                            @php($swatch = $key === 'color' ? ($product['colors'][$option] ?? []) : [])
                            @php($unavailable = $key === 'color' && !in_array($option, $galleryColors, true))
                            @include('shop.storefront.components.option', ['selected' => !empty($galleryColors) && !$unavailable && $option === $initialVariant[$key], 'small' => $key === 'size', 'disabled' => $unavailable || empty($galleryColors), 'unavailable' => $unavailable])
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
        @include('shop.storefront.components.product-price')
        <button type="submit" class="btn btn-primary w-100" @if(empty($galleryColors)) disabled @endif>@icon('shopping-bag') <span data-bag-label aria-live="polite">{{ $inBag ? 'In bag ('.$inBag.') · Add more' : 'Add to bag' }}</span></button>
        <p class="text-center mt-3 mb-0" data-shopping-bag-link @if(!$shoppingBag['count']) hidden @endif><a href="{{ route('shop.bag') }}">Go to shopping bag</a></p>
    </form>
@else
    <p class="shop-unavailable">Currently unavailable</p>
@endif
