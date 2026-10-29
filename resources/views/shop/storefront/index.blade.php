@extends('shop.storefront.layout', ['shopTitle' => ($category ? $categories[$category].' | ' : '').'PianoLIT Shop'])

@section('shop-content')
@include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
<form method="GET" action="{{ $catalogUrl }}" class="shop-catalog-heading">
    <div class="row align-items-end justify-content-between g-4 mb-4 mb-md-5">
        <div class="col-12 col-md-7">
            <h1 class="mb-0">{{ $category ? $categories[$category] : 'All products' }}</h1>
        </div>
        <div class="col-12 col-md-5 col-lg-4">
            <div class="shop-search shop-field">
                <label class="visually-hidden" for="shop-search">Search products</label>
                @input(['name' => 'q', 'id' => 'shop-search', 'label' => null, 'value' => $search, 'placeholder' => 'Search products…', 'required' => false, 'limit' => 100, 'bag' => 'storefront'])
                <button type="submit" aria-label="Search products">@icon('search')</button>
            </div>
        </div>
    </div>
    @foreach($topics as $topic)<input type="hidden" name="topics[]" value="{{ $topic }}">@endforeach
    <nav class="d-flex flex-wrap gap-3 pb-4" aria-label="Product categories">
        @foreach($categories as $key => $label)
            <a href="{{ $categoryUrls[$key].($search ? '?'.http_build_query(['q' => $search]) : '') }}" class="shop-button {{ ($category ?? '') === $key ? 'shop-button--dark' : '' }}" @if(($category ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        @if($search || $topics)<a class="shop-clear align-self-center" href="{{ $catalogUrl }}">Clear filters @icon('x')</a>@endif
    </nav>
</form>
@if($hasProducts)
    @if($category)
        <div class="row g-3 g-md-4 mt-2" aria-label="Products">
            @foreach($products as $product)
                <div class="col-6 col-lg-3">@include('shop.storefront.components.card')</div>
            @endforeach
        </div>
    @else
        <div class="row g-3 g-md-4 mt-2" aria-label="Products">
            @foreach($productGroups as $key => $group)
                @if($group->count())
                    @include('shop.storefront.components.category-row', [
                        'categoryKey' => $key, 'categoryLabel' => $sectionLabels[$key], 'categoryProducts' => $group,
                        'categoryUrl' => ($categoryUrls[$key] ?? app(\App\Services\Shop\Storefront::class)->categoryUrl($key)).($search ? '?'.http_build_query(['q' => $search]) : ''),
                        'halfWidth' => in_array($key, ['t-shirts', 'stickers'], true),
                        'offset' => $key === 'stickers' && !$productGroups['t-shirts']->count(),
                    ])
                @endif
            @endforeach
        </div>
    @endif
@else
    <div class="shop-empty py-5 my-md-4">
        <span class="shop-empty__icon" aria-hidden="true">@icon($search || $category ? 'search' : 'shopping-bag', ['mr' => 0, 'size' => '2x'])</span>
        <h2>{{ $search || $category ? 'No products found' : 'Good things are on their way' }}</h2>
        <p>{{ $search || $category ? 'Try another search or take a look at all products.' : 'T-shirts, stickers, and a little more piano in your everyday.' }}</p>
        <a class="shop-button shop-button--mint" href="{{ $search || $category ? route('shop.home') : route('ebooks.index') }}">{{ $search || $category ? 'Browse all products' : 'Explore eBooks' }} @icon('arrow-right')</a>
    </div>
@endif
@endsection
