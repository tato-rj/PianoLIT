@php
    $detailsId = $detailsId ?? 'product-details';
    $sizeChartId = $sizeChartId ?? 'product-size-chart';
@endphp
<section class="shop-details mt-4" aria-labelledby="{{ $detailsId }}-title">
    <h2 id="{{ $detailsId }}-title" class="{{ $digitalProduct ? 'mb-3' : 'visually-hidden' }}">Details</h2>
    @if($digitalProduct)
        <dl class="shop-product-facts mb-0">
            <div><dt>Edition</dt><dd>{{ $productKind }} · Digital download</dd></div>
            @if($formats)<div><dt>Format</dt><dd>{{ $formats }}</dd></div>@endif
            @if($digitalProduct->pages_count)<div><dt>Length</dt><dd>{{ $digitalProduct->pages_count }} pages</dd></div>@endif
            @if($digitalProduct->author)<div><dt>Author</dt><dd>{{ $digitalProduct->author }}</dd></div>@endif
            @if($digitalProduct->published_at)<div><dt>Published</dt><dd>{{ $digitalProduct->published_at->toFormattedDateString() }}</dd></div>@endif
        </dl>
    @else
        @if(!empty($product['description']))
            <div class="shop-product-description mb-3">{!! nl2br(e($product['description'])) !!}</div>
        @endif
        @component('components.layout.accordion', ['id' => $detailsId.'-accordion', 'variant' => 'minimal'])
            @if(!empty($product['materials']) || empty($product['description']))
            @component('components.layout.accordion-item', ['id' => $detailsId.'-about', 'parentId' => $detailsId.'-accordion', 'title' => empty($product['materials']) ? 'About' : 'Material', 'indicator' => 'plus-minus'])
                @if(!empty($product['materials']))
                    @foreach($product['materials'] as $material)
                        <p class="{{ $loop->last ? 'mb-0' : 'mb-2' }}">@if($material['label'])<strong>{{ $material['label'] }}:</strong> @endif{!! nl2br(e($material['text'])) !!}</p>
                    @endforeach
                @else
                    <p>{{ $product['name'] }}</p>
                    <p class="mb-0">Choose from the available options above. Browse the gallery for a closer look at the design.</p>
                @endif
            @endcomponent
            @endif
            @if($product['category'] === 't-shirts')
                @component('components.layout.accordion-item', ['id' => $detailsId.'-fitting', 'parentId' => $detailsId.'-accordion', 'title' => 'Fitting', 'indicator' => 'plus-minus'])
                    @include('shop.storefront.components.fitting', ['sizeChartId' => $sizeChartId, 'sizeChart' => $product['sizeChart'] ?? null])
                @endcomponent
            @endif
            @component('components.layout.accordion-item', ['id' => $detailsId.'-quality-returns', 'parentId' => $detailsId.'-accordion', 'title' => 'Quality Guarantee & Returns', 'indicator' => 'plus-minus'])
                <ul class="mb-0">
                    <li class="mb-2">Quality is guaranteed. If there is a print error or visible quality issue, we'll replace or refund it.</li>
                    <li>Because the products are made to order, we do not accept general returns or sizing-related returns.</li>
                </ul>
            @endcomponent
        @endcomponent
    @endif
</section>
