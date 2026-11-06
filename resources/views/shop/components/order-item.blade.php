@php($orderImage = app(\App\Services\Shop\DigitalMedia::class)->gallery($product)->first()['url'] ?? null)
<div class="d-flex align-items-start gap-3 shop-order-item">
    @if($orderImage)<img class="shop-order-item__cover" src="{{ $orderImage }}" alt="{{ $product->title }}">@endif
    <div class="flex-grow-1">
        <h3 class="h6 mb-2"><a class="text-body" href="{{ $product->showRoute() }}">{{ rtrim($product->title, '. ') }}</a></h3>
        <p class="small text-muted mb-1">PianoLIT {{ $product instanceof \App\Shop\eScore ? 'eScore' : 'eBook' }} · {{ $product->pages_count }} pages</p>
        <p class="small text-muted mb-0">{{ implode(' + ', array_keys($product->links())) }} · Digital download</p>
    </div>
</div>
