@extends('layouts.app', ['title' => 'Pianists | ' . config('app.name')])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}
.search input { 
	text-indent: 30px;
	border: 0; 
	padding-bottom: .35rem;
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
@include('tools.pianists.powered')

@include('components.title', [
	'title' => 'Pianists', 
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
		@include('tools.pianists.card')
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
<script type="text/javascript">

$('input#search-pianist').on('keyup', function() {
	let val = $(this).val();

	if (val.length > 2) {
		console.log('Find names with: '+val);
		$('.name').each(function() {
			let $name = $(this);
			if ($name.text().toLowerCase().includes(val)) {
				$name.parent().parent().show();
			} else {
				$name.parent().parent().hide();
			}
		});
	} else {
		console.log('Show all');
		$('#pianists .pianist-card').show();
	}
});
</script>
@endpush