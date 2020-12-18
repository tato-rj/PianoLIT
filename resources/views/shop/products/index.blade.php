@extends('layouts.app', [
	'title' => $title . ' | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => $keywords,
		'title' => $title,
		'description' => $subtitle,
		'thumbnail' => $thumbnail,
		'created_at' => carbon('17-06-2020'),
		'updated_at' => carbon('17-06-2020')
	]])

@section('content')

@pagetitle

<div class="container mb-5">
	@component('components.display.layout', [
		'collection' => $products,
		'topics' => $topics,
		'ads' => $ads
	])

	@slot('items')
		@each('shop.components.card', $products, 'item')
	@endslot
	
	@endcomponent
</div>

@endsection

@push('scripts')
@addthis
@endpush