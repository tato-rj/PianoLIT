@extends('layouts.app', [
	'title' => 'eBooks | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => 'ebooks,ebook music,learn music,music theory,music sheet',
		'title' => 'eBooks',
		'description' => 'Dive into your favorite music topics and keep on learning',
		'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
		'created_at' => carbon('17-06-2020'),
		'updated_at' => carbon('17-06-2020')
	]])

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')
@include('components.title', [
	'title' => 'eBooks', 
	'subtitle' => 'Dive into your favorite music topics and keep on learning'])

<div class="container mb-5">
	@each('shop.ebooks.display', $ebooks, 'ebook')
</div>

@endsection

@push('scripts')
@include('components.addthis')
@endpush