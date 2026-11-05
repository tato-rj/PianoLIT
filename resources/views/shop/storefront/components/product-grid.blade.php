<div class="row gx-3 gx-md-4 gy-4 gy-md-5 mt-2" aria-label="Products">
    @foreach($products as $product)
        <div class="col-6 col-lg-3">@include('shop.storefront.components.card')</div>
    @endforeach
</div>
