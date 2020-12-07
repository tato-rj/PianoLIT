@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
</style>
@endpush

@section('content')
@include('webapp.layouts.header')

<section class="text-center">
	<div class="mb-5 pb-5 border-bottom">
		<h1>@fa(['icon' => 'check-circle', 'fa_type' => 'r', 'color' => 'green', 'mr' => 0, 'size' => 'lg'])</h1>
		<h2>CONGRATULATIONS!</h2>
		<p class="mb-4">You have successfully subscribed to PianoLIT</p>
		@button(['label' => 'LET\'S GET STARTED', 'href' => route('webapp.discover'),
			'styles' => [
				'size' => 'wide', 
				'theme' => 'outline-secondary'
			], 'classes' => 'rounded-pill', 'modal' => 'tour-modal'])
	</div>
	@include('webapp.membership.pricing.features')
</section>
@endsection

@push('scripts')
@endpush