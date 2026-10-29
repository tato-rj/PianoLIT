@if($product['variants'])
    @php($inBag = $shoppingBag['lines'][$product['id'].':'.$product['variants'][0]['id']]['quantity'] ?? 0)
    <form action="{{ route('shop.bag.add') }}" method="POST" data-add-to-bag data-product-options>
        @csrf
        <input type="hidden" name="product_id" value="{{ $product['id'] }}">
        <div class="shop-field mb-4" data-variant-fallback>
            <label for="product-variant" class="shop-eyebrow mb-2">Choose an option</label>
            <select name="variant_id" id="product-variant" class="shop-select" required>
                @foreach($product['variants'] as $variant)
                    <option value="{{ $variant['id'] }}" data-color="{{ $variant['color'] }}" data-size="{{ $variant['size'] }}" data-price="{{ $variant['price'] }}" data-in-bag="{{ $shoppingBag['lines'][$product['id'].':'.$variant['id']]['quantity'] ?? 0 }}">{{ $variant['color'] }} / {{ $variant['size'] }} — {{ $variant['price'] }}</option>
                @endforeach
            </select>
        </div>
        <div data-option-pickers hidden>
            @foreach(['color' => 'Color', 'size' => 'Size'] as $key => $label)
                <fieldset class="shop-options mb-4" data-option-group="{{ $key }}">
                    <legend class="shop-eyebrow mb-2">{{ $label }}</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(array_unique(array_column($product['variants'], $key)) as $option)
                            @php($swatch = $key === 'color' ? ($product['colors'][$option] ?? []) : [])
                            @include('shop.storefront.components.option', ['selected' => false])
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
        <p class="shop-eyebrow mb-4">{{ $product['label'] }}</p>
        <div class="mb-3">@include('shop.storefront.components.quantity')</div>
        <button type="submit" class="shop-button shop-button--dark w-100">@icon('shopping-bag') <span data-bag-label aria-live="polite">{{ $inBag ? 'In bag ('.$inBag.') · Add more' : 'Add to bag' }}</span></button>
    </form>
@else
    <p class="shop-unavailable">Currently unavailable</p>
@endif
