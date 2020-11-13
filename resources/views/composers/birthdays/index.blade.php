@extends('layouts.app')

@push('header')
<style type="text/css">
.offset-composer:not(:first-child) {
	margin-left: -18px;
}
</style>
@endpush

@section('content')
<div class="container mb-5">
	@pagetitle([
		'title' => 'Birthdays', 
		'subtitle' => 'Don\'t miss out on the birthday of any of your favorite composers'])

	<div class="row">
		@foreach($months as $month)
		@include('composers.birthdays.month')
		@endforeach
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>

<script type="text/javascript">
$('.calendar-month').click(function() {
	let $all = $('.calendar-month').not($(this)).removeClass('col-lg-6 col-md-8 col-12').addClass('col-lg-3 col-md-4 col-6').find('.composer-list');
	let $this = $(this).toggleClass('col-lg-3 col-md-4 col-6 col-lg-6 col-md-8 col-12').find('.composer-list');

	$all.addClass('d-flex flex-wrap').find('.composer-item').addClass('offset-composer');
	$all.find('.composer-info').hide();

	$this.toggleClass('d-flex flex-wrap').find('.composer-item').toggleClass('offset-composer');
	$this.find('.composer-info').fadeIn();
});
</script>
@endpush