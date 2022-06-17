@extends('webapp.layouts.app')

@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">

<style type="text/css">
.composers-highlight[selected] {
	opacity: 0.4;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Composers', 'subtitle' => 'Explore our list of composers'])

<section class="mb-4">
	@include('webapp.search.form', ['static' => true])
</section>

<section class="container">
	<div class="d-flex flex-wrap justify-content-center mb-2">
		@include('webapp.composers.highlight', ['field' => 'ethnicity', 'query' => 'black', 'label' => 'Black Composers'])
		@include('webapp.composers.highlight', ['field' => 'gender', 'query' => 'female', 'label' => 'Women Composers'])
		@include('webapp.composers.highlight', ['field' => 'period', 'query' => 'modern contemporary', 'label' => '20th Century Composers'])
	</div>
	<div class="row" id="composers-list" data-cards=".composer-card">
		@foreach($composers as $composer)
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

$('.composers-highlight').click(function() {
	let field = $(this).data('field');
	let query = $(this).data('query');

	if ($(this).attr('selected')) {
		$('.composer-card').show();
	} else {
		$('.composer-card').each(function() {
			let attr = $(this).data(field);

			if (attr.toLowerCase().indexOf(query) >= 0) {
				$('.composer-card').hide();
				$(this).show();
			} else {
				$('.composer-card').show();
			}
		});
	}

	$('.composers-highlight').not(this).removeAttr('selected');
	$(this).toggleAttr('selected');
});
</script>
@endpush