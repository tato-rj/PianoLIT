@extends('layouts.app')

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
@endpush