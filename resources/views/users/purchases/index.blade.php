@extends('layouts.app')

@push('header')
@endpush

@section('content')
<div class="container mb-6">
	<div class="row">
	  <div class="col-lg-8 col-md-10 col-12 mx-auto">
		<div class="text-center mb-4">
			<h5>My downloads</h5>
			@if($purchases->isEmpty())
			@include('users.purchases.empty')
			@else
			<p>Here is a list of all the products you've downloaded/purchased so far</p>
			@endif
		</div>
		@paginationCount(['collection' => $purchases])

		@include('users.purchases.list')

		@pagination(['collection' => $purchases])
	  </div>
	</div>
</div>
@endsection

@push('scripts')
@endpush