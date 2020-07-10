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

		@if(auth()->user()->purchases()->exists())
		@table([
			'id' => 'purchases-table',
			'sortable' => true,
			'headers' => ['Date <i class="fas fa-sort"></i>', 'Type <i class="fas fa-sort"></i></th>', 'Name <i class="fas fa-sort"></i>', 'Download <i class="fas fa-sort"></i>'],
			'more' => route('users.load-purchases'),
			'rows' => view('users.purchases.rows', [
				'purchases' => auth()->user()->purchases->take(5)
			])
		])
		@else
		<div class="py-5 text-center">
			<p class="text-muted m-0"><i>Looks like you haven't made any downloads so far...</i></p>
		</div>
		@endif
	  </div>
	</div>
</div>
@endsection

@push('scripts')
@endpush