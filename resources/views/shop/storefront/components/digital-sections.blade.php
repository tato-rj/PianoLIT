@include('shop.storefront.components.product-row', ['products' => $relatedProducts])
<div class="mt-5">@include('shop.components.reviews.layout', ['storefront' => true])</div>
@include('shop.components.preview.magazine', ['storefront' => true])
