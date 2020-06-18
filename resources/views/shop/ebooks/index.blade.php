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
	<div class="row">
		<div class="col-lg-6 col-md-6 col-12">
			<img src="{{asset('images/ebook-template.png')}}" class="w-100">
		</div>
		<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
			<div>
				@topics(['topics' => \App\Blog\Post::first()->topics, 'route' => 'ebooks.topic'])

				<div>
					<h4 class=" clamp-2"><strong>The Little Book of Piano Techniques and How to Solve Them</strong></h4>
					<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
					tempor incididunt ut labore et dolore magna aliqua. Excepteur sint occaecat cupidatat non
					proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
				</div>
				
				<div>
					<a href="#" class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'shopping-cart'])Buy now for $25</a>
					<a href="#" class="btn btn-sm btn-wide btn-outline-secondary mb-2">@fa(['icon' => 'info-circle'])More details</a>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection

@push('scripts')
@include('components.addthis')
@endpush