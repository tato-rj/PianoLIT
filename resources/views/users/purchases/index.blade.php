@extends('layouts.app')

@push('header')
@endpush

@section('content')
<div class="container mb-6">
	<div class="row">
	  <div class="col-lg-8 col-md-8 col-sm-6 col-12 mx-auto">
		<div class="text-center mb-4">
			<h5>My downloads</h5>
			<p>Here is a list of all the products you've downloaded/purchased so far</p>
		</div>
		@if($purchases)
		<div class="text-center text-muted">Showing {{$purchases->perPage()}} of {{$purchases->total()}} {{str_plural('download', $purchases->total())}}</div>
		@endif

		@include('users.purchases.list')

		@pagination(['collection' => $purchases])
	  </div>
	</div>
</div>
@endsection

@push('scripts')
@endpush