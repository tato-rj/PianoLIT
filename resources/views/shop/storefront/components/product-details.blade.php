@php
    $detailsId = $detailsId ?? 'product-details';
    $sizeChartId = $sizeChartId ?? 'product-size-chart';
    $description = $digitalProduct ? $digitalProduct->description : ($product['description'] ?? '');
    $hasAbout = !empty($product['subtitle']) || !empty($description)
        || ($digitalProduct && $digitalProduct->topics->count())
        || (!$digitalProduct && empty($product['materials']));
    $membershipNotice = $membershipNotice ?? ($digitalProduct
        ? trim(view('shop.components.loyalty.notification', ['product' => $digitalProduct, 'compact' => true])->render()) : '');
@endphp
<section class="shop-details mt-4" aria-labelledby="{{ $detailsId }}-title">
    <h2 id="{{ $detailsId }}-title" class="visually-hidden">Details</h2>
    @component('components.layout.accordion', ['id' => $detailsId.'-accordion', 'variant' => 'minimal'])
        @if($hasAbout)
            @component('components.layout.accordion-item', ['id' => $detailsId.'-about', 'parentId' => $detailsId.'-accordion', 'title' => 'About', 'indicator' => 'plus-minus'])
                @if(!empty($product['subtitle']))<p>{{ $product['subtitle'] }}</p>@endif
                @if($description)
                    <div class="shop-product-description">{!! $digitalProduct ? $description : nl2br(e($description)) !!}</div>
                @elseif(!$digitalProduct && empty($product['subtitle']))
                    <p>{{ $product['name'] }}</p>
                    <p class="mb-0">Choose from the available options above. Browse the gallery for a closer look at the design.</p>
                @endif
                @if($digitalProduct && $digitalProduct->topics->count())
                    <div class="d-flex flex-wrap gap-2 mt-3" aria-label="Topics">
                        @foreach($digitalProduct->topics as $topic)
                            <a class="btn btn-secondary btn-sm" href="{{ $digitalProduct->indexRoute().'?'.http_build_query(['topics' => [$topic->slug]]) }}">{{ $topic->name }}</a>
                        @endforeach
                    </div>
                @endif
            @endcomponent
        @endif
        @if($digitalProduct)
            @component('components.layout.accordion-item', ['id' => $detailsId.'-specifications', 'parentId' => $detailsId.'-accordion', 'title' => 'Details', 'indicator' => 'plus-minus'])
                <dl class="shop-product-facts mb-0">
                    <div><dt>Edition</dt><dd>{{ $productKind }} · Digital download</dd></div>
                    @if($formats)<div><dt>Format</dt><dd>{{ $formats }}</dd></div>@endif
                    @if($digitalProduct->audio_path)<div><dt>Audio</dt><dd>Listening sample</dd></div>@endif
                    @if($digitalProduct->pages_count)<div><dt>Length</dt><dd>{{ $digitalProduct->pages_count }} pages</dd></div>@endif
                    @if($digitalProduct->author)<div><dt>{{ $productKind === 'eScore' ? 'Composer' : 'Author' }}</dt><dd>{{ $digitalProduct->author }}</dd></div>@endif
                    @if($digitalProduct->published_at)<div><dt>Published</dt><dd>{{ $digitalProduct->published_at->toFormattedDateString() }}</dd></div>@endif
                </dl>
            @endcomponent
            @if($membershipNotice !== '')
                @component('components.layout.accordion-item', ['id' => $detailsId.'-membership', 'parentId' => $detailsId.'-accordion', 'title' => 'Membership', 'indicator' => 'plus-minus'])
                    {!! $membershipNotice !!}
                @endcomponent
            @endif
        @else
            @if(!empty($product['materials']))
                @component('components.layout.accordion-item', ['id' => $detailsId.'-materials', 'parentId' => $detailsId.'-accordion', 'title' => 'Material', 'indicator' => 'plus-minus'])
                    @foreach($product['materials'] as $material)
                        <p class="{{ $loop->last ? 'mb-0' : 'mb-2' }}">@if($material['label'])<strong>{{ $material['label'] }}:</strong> @endif{!! nl2br(e($material['text'])) !!}</p>
                    @endforeach
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
        @endif
    @endcomponent
</section>
