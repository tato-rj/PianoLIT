@extends('layouts.app')

@section('content')
<div class="container mb-5">
	@pagetitle([
		'title' => 'Birthdays', 
		'subtitle' => 'Don\'t miss out on the birthday of any of your favorite composers'])

	<div class="row">
		@for($i=1; $i<=12; $i++)
		@include('composers.birthdays.month')
		@endfor
	</div>
</div>

@endsection
