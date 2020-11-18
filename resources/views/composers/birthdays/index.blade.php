@extends('layouts.app')

@push('header')
<style type="text/css">
.offset-list {
	padding-left: 24px;
}
.offset-list .composer-item {
	margin-left: -24px;
}
</style>
@endpush

@section('content')
<div class="container mb-5">
	@pagetitle([
		'title' => 'Birthdays', 
		'subtitle' => 'Don\'t miss out on the birthday of any of your favorite composers'])

	<div class="border py-2 px-3 rounded">
		<div class="bg-light rounded py-1 pr-1 pl-3 d-flex d-apart mb-2">
			<h5 class="text-muted m-0">{{now()->year}} Calendar</h5>
			<div>
				<select name="composers-options" class="form-control form-control-sm rounded">
					<option value="famous">Most famous</option>
					<option value="all">Show all</option>
				</select>
			</div>
		</div>
		<div class="row" id="calendar">
			@include('composers.birthdays.calendar')
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
$('select[name="composers-options"]').on('change', function() {
	let option = $(this).val();

	$('#calendar > div').addClass('opacity-4');

	axios.get(window.location.href, {params: {option: option}})
		 .then(function(response) {
		 	console.log(response.data);
		 	$('#calendar').html(response.data).promise().done(function() {
				$('#calendar > div').show();
		 	});
		 })
		 .catch(function(error) {
		 	console.log(error);
		 });
});
</script>

<script type="text/javascript">
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>

<script type="text/javascript">
$(document).on('click', '.calendar-month', function() {
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
	$month.addClass('col-lg-6 col-md-8 selected-month').removeClass('col-lg-3 col-md-4');
	$month.find('.composer-list').removeClass('d-flex flex-wrap offset-list').find('.composer-item').addClass('bg-light').find('img').tooltip('disable');
	$month.find('.composer-info').fadeIn();
}

function resetCalendar()
{
	let $calendar = $('.calendar-month');

	$calendar.removeClass('col-lg-6 col-md-8 selected-month').addClass('col-lg-3 col-md-4');
	$calendar.find('.composer-list').addClass('d-flex flex-wrap offset-list').find('.composer-item').removeClass('bg-light').find('img').tooltip('enable');
	$calendar.find('.composer-info').hide();
}

function isClosed($month)
{
	return ! $month.hasClass('selected-month');
}
</script>
@endpush