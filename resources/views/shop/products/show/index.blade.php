@extends('layouts.app', [
	'title' => $product->title . ' | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => $product->keywords(),
		'title' => $product->title,
		'description' => $product->subtitle,
		'thumbnail' => $product->mockup_image(),
		'created_at' => $product->created_at,
		'updated_at' => $product->created_at
	]])

@php
    $productKind = $product instanceof \App\Shop\eScore ? 'eScore' : 'eBook';
    $catalogLabel = $productKind === 'eScore' ? 'eScores' : 'eBooks';
    $reviews = $product->publishedReviews()->latest()->get();
    $reviewCount = $reviews->count();
    $averageRating = $reviewCount ? number_format($reviews->avg('rating'), 1) : 0;
    $ownedReview = auth()->check() ? auth()->user()->reviewFor($product) : null;
@endphp

@section('content')
<section class="container product-detail pt-4 mb-5" data-product-detail>
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Shop', 'url' => route('shop.home')],
        ['label' => $catalogLabel, 'url' => $product->indexRoute()],
        ['label' => rtrim($product->title, '. ')],
    ]])

    @include('shop.products.show.lead')
    @include('shop.products.show.about')
    @include('shop.components.reviews.layout')
    @include('components.cards.suggestions', [
        'title' => 'More to explore',
        'card' => 'shop.components.card',
        'collection' => $product->suggestions(4)->get()])
</section>
@include('shop.components.preview.magazine')
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/turn.min.js') }}" defer></script>
<script src="{{ mix('js/views/shop-product.js') }}" defer></script>
@endpush
