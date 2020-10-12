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
	@if ($products)
	@each('shop.components.display.lg', $products, 'product')
	@else
	<div class="p-4 text-center text-red"><strong>Coming up soon!</strong></div>
	@include('components.animations.workers')
	@endif
</div>

@endsection

@push('scripts')
@addthis
@endpush