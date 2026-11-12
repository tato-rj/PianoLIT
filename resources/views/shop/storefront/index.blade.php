@extends('shop.storefront.layout', ['shopTitle' => $searchTitle])

@section('page-metadata')
    @include('components.seo.resource-head', ['siteName' => 'PianoLIT Shop', 'searchImage' => asset('images/home/worlds/shop.webp')])
@endsection

@section('shop-content')
@include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
<form method="GET" action="{{ $catalogUrl }}">
    <div class="row align-items-end justify-content-between g-4 mb-4">
        <div class="col-12 col-md-7">
            @component('components.layout.hub-header', ['heading' => $category ? $categories[$category] : 'PianoLIT Shop', 'spacing' => 'mb-0'])
                @if(!$search && !$topics){{ $searchDescription }}@endif
            @endcomponent
        </div>
        <div class="col-12 col-md-5 col-lg-4">
            <div class="shop-search shop-field">
                <label class="visually-hidden" for="shop-search">Search products</label>
                @input(['name' => 'q', 'id' => 'shop-search', 'label' => null, 'value' => $search, 'placeholder' => 'Search products…', 'required' => false, 'limit' => 100, 'bag' => 'storefront'])
                <button class="btn-raw" type="submit" aria-label="Search products">@icon('search', ['mr' => 0])</button>
            </div>
        </div>
    </div>
    @foreach($topics as $topic)<input type="hidden" name="topics[]" value="{{ $topic }}">@endforeach
    <div class="pb-4">
        @include('shop.storefront.components.category-navigation')
        @if($search || $topics)<a class="d-inline-block mt-4" href="{{ $catalogUrl }}">Clear filters @icon('x')</a>@endif
    </div>
</form>
@if($hasProducts)
    @include('shop.storefront.components.product-grid')
    @if($products->hasPages())
        <div class="mt-5">@pagination(['collection' => $products->onEachSide(1)])</div>
    @endif
@else
    <div class="shop-empty py-5 my-md-4">
        <span class="shop-empty__icon bg-light text-muted rounded-circle" aria-hidden="true">@icon($search || $category ? 'search' : 'shopping-bag', ['mr' => 0, 'size' => '2x'])</span>
        <h2>{{ $search || $category ? 'No products found' : 'No products available' }}</h2>
        <p>{{ $search || $category ? 'Try another search or browse all products.' : 'There are no products available at the moment.' }}</p>
        <a class="btn btn-primary" href="{{ $search || $category ? route('shop.home') : route('ebooks.index') }}">{{ $search || $category ? 'Browse all products' : 'Explore eBooks' }} @icon('arrow-right')</a>
    </div>
@endif
@endsection
