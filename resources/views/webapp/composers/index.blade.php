@extends('webapp.layouts.app')

@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Composers', 'subtitle' => 'Explore our list of composers'])

<section class="mb-2">
	@include('webapp.search.form', ['static' => true])
</section>

<section class="container">
	<div class="row" id="composers-list" data-cards=".composer-card">
		@foreach($composers->shuffle() as $composer)
			@include('webapp.composers.list-item')
		@endforeach
	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
$('form#search-form').on('submit', function(event) {
	event.preventDefault();
});

$('div#composers-list').filterableBy('input[name="search"]');
</script>
@endpush