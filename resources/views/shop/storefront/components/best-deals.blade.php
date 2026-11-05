@php($dealsId = $dealsId ?? 'shop-best-deals')
<section class="shop-best-deals mt-3 mb-5" aria-labelledby="{{ $dealsId }}-title">
    <h2 id="{{ $dealsId }}-title" class="h3 mb-4">Best deals</h2>
    <div class="shop-deals-track {{ $bestDeals->count() === 4 ? 'shop-deals-track--mosaic' : '' }}" role="region" aria-label="Best deals products" tabindex="0">
        @foreach($bestDeals as $deal)
            <a class="shop-deal rounded overflow-hidden text-body text-decoration-none" href="{{ $deal['url'] }}"
               style="--shop-deal-surface: var(--pastel-{{ ['t-shirts' => 'peach', 'stickers' => 'sky', 'ebooks' => 'rose', 'escores' => 'cream'][$deal['category']] }}-surface)">
                <div class="shop-deal__media">
                    @if(!empty($deal['displayMockupUrl']) || $deal['imageUrl'])
                        <img class="shop-deal__image {{ !empty($deal['displayMockupUrl']) ? 'shop-deal__image--display' : '' }}" src="{{ $deal['displayMockupUrl'] ?? $deal['imageUrl'] }}" alt="" width="600" height="600" loading="lazy">
                    @else
                        <span class="shop-deal__placeholder text-body opacity-4" aria-hidden="true">@icon('image', ['mr' => 0, 'size' => '3x'])</span>
                    @endif
                </div>
                <div class="shop-deal__copy">
                    <div class="shop-deal__details">
                        <h3 class="h4 mb-2">{{ \App\Services\Shop\Storefront::CATEGORIES[$deal['category']] }}</h3>
                        <p class="mb-2">{{ $deal['name'] }}</p>
                        <p class="mb-0">{{ $deal['priceVaries'] ? 'From ' : '' }}{{ $deal['price'] }}</p>
                    </div>
                    <span class="shop-deal__arrow btn btn-secondary btn-sm rounded-circle" aria-hidden="true">@icon('arrow-up-right', ['mr' => 0])</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
