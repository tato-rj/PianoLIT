@extends('layouts.app', ['title' => $product->title . ' | ' . config('app.name')])

@push('header')
<script src="https://js.stripe.com/v3/"></script>
<style type="text/css">
</style>
@endpush

@section('content')

<section class="container mb-5">
	<div class="row">
		<div class="col-lg-7 col-md-5 col-12"> 
	  		@include('shop.checkout.summary')
	  	</div>

		<div class="col-lg-5 col-md-7 col-12"> 
		  @component('shop.components.forms.layout')
			  @include('shop.components.forms.returning', [
			    'comments' => 'After your payment is complete, you will receive an email with the link to download the eBook. You can also access it from your purchases page, located under the main menu.'
			  ])
		  @endcomponent
		</div>
	</div>
</section>

@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('js/views/checkout.js')}}"></script>

<script type="text/javascript">
$('input[name="payment-method"]').change(function() {
	$('.payment-forms').hide();
	$('.payment-forms input:visible').val('');
	$('.coupon-feedback').html('');
	$($(this).attr('target')).show();
});
</script>
@endpush