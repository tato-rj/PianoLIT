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

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')
@include('components.title')

<div class="container mb-5">
	@each('components.shop.display.lg', $products, 'product')
</div>

@endsection

@push('scripts')
@include('components.addthis')
@endpush