@once
<script>{!! file_get_contents(resource_path('js/components/product-reveal.js')) !!}</script>
@endonce
<div class="row gx-3 gx-md-4 gy-4 gy-md-5 mt-2" aria-label="Products" data-product-grid>
    @foreach($products as $product)
        <div class="col-6 col-lg-3" data-product-reveal>@include('shop.storefront.components.card', ['prioritizeImage' => $loop->first])</div>
    @endforeach
</div>
<script>window.PianoLITProductReveal(document.currentScript.previousElementSibling, window);</script>
