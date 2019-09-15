@extends('layouts.app', ['title' => 'Great Pianists | ' . config('app.name')])

@push('header')
<style type="text/css">
.mark, mark {
	padding: 0!important;
}

.fadeInUp {
	animation-duration: .2s;
}
.search input { 
	text-indent: 30px;
	border: 0; 
	padding-bottom: .35rem;
	border-radius: 0;
}
.search .fa-search { 
    position: absolute;
    top: 7px;
    left: 8px;
    font-size: 15px;
}
</style>
@endpush

@section('content')
@include('resources.pianists.powered')

@include('components.title', [
	'title' => 'Great Pianists', 
	'subtitle' => 'Discover recordings from the most famous pianists of our time'])

<div class="container mb-5">
	<div class="row mb-5">
		<div class="col-lg-6 col-md-7 col-10 mx-auto">
			<div class="search position-relative">
				<i class="fas fa-search"></i>
				<input id="search-pianist" type="text" placeholder="Search here..." class="w-100 border-bottom">
			</div>
		</div>
	</div>
	<div class="row" id="pianists">		
		@foreach($pianists as $pianist)
		@include('resources.pianists.card')
		@endforeach
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.es6.min.js"></script>
<script type="text/javascript">
var marker = new Mark('div#pianists');

$('input#search-pianist').on('keyup', function() {
	let val = $(this).val().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
	$('.pianist-card').unmark();

	if (val.length > 2) {
		console.log('Find names with: '+val);
		$('.name').each(function() {
			let $element = $(this);
			let name = $element.text().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
			let country = $element.next('p').text().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

			if (name.includes(val) || country.includes(val)) {
				$element.parent().parent().show();
				$element.parent().mark(val);
			} else {
				$element.parent().parent().hide();
			}
		});
	} else {
		console.log('Show all');
		$('#pianists .pianist-card').show();
	}
});
</script>
@endpush