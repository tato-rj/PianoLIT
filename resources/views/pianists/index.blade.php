@extends('layouts.app', [
	'title' => 'Great Pianists | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'pianists,classical music,classical recordings,best classical pianists,chopin album,liszt recording,beethoven album,mozart music',
		'title' => 'Great Pianists',
		'description' => 'Discover the greatest pianists of our time and their recordings, an online database powered by Apple Music',
		'thumbnail' => asset('images/misc/thumbnails/pianists.jpg'),
		'created_at' => carbon('16-09-2019'),
		'updated_at' => carbon('16-09-2019')
		]])
		
@push('header')
@endpush

@section('content')
@include('pianists.powered')

@include('components.title', [
	'title' => 'Great Pianists', 
	'subtitle' => 'Discover recordings from the most famous pianists of our time'])

<div class="container mb-5">
	<div class="row mb-5">
		<div class="col-lg-6 col-md-7 col-10 mx-auto">
			<div class="search-bar position-relative">
				<i class="fas fa-search"></i>
				<input id="search-pianist" type="text" placeholder="Search here..." class="w-100 border-bottom">
			</div>
		</div>
	</div>
	<div class="row" id="pianists" data-cards=".pianist-card">
		@each('pianists.card', $pianists, 'pianist')
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
$('div#pianists').filterableBy('input#search-pianist');
</script>
@endpush