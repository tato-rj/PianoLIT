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

	<div class="row" id="calendar">
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
$('.calendar-month').on('click', function() {
	if (isClosed($(this))) {
		resetCalendar();
		openMonth($(this));
	} else {
		resetCalendar();
	}
});

$(document).ready(function() {
	$('#calendar > div').show();
});

function openMonth($month)
{
	$month.addClass('col-lg-6 col-md-8 col-12').removeClass('col-lg-3 col-md-4 col-6');
	$month.find('.composer-list').removeClass('d-flex flex-wrap').find('.composer-item').addClass('bg-light').removeClass('offset-composer').find('img').tooltip('disable');
	$month.find('.composer-info').fadeIn();
}

function resetCalendar()
{
	let $calendar = $('.calendar-month');

	$calendar.removeClass('col-lg-6 col-md-8 col-12').addClass('col-lg-3 col-md-4 col-6');
	$calendar.find('.composer-list').addClass('d-flex flex-wrap').find('.composer-item').removeClass('bg-light').addClass('offset-composer').find('img').tooltip('enable');
	$calendar.find('.composer-info').hide();
}

function isClosed($month)
{
	return ! $month.hasClass('col-12');
}
</script>
@endpush