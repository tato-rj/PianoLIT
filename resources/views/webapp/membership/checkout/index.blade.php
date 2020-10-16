@extends('webapp.layouts.app')

@push('header')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Checkout', 'subtitle' => 'Almost there! Please review carefully the details below.'])

<section class="row">
	<div class="col-lg-6 col-md-6 col-12"> 
		@include('webapp.membership.checkout.summary')
	</div>

	<div class="col-lg-6 col-md-6 col-12"> 
		@component('shop.components.forms.layout')
			@include('shop.components.forms.new', [
				'action' => route('webapp.membership.purchase', $plan),
				'label' => 'Subscribe now for $' . $plan->formattedPrice(),
				'comments' => 'Your free trial will start today and end on ' . now()->addDays(7)->toFormattedDateString() . '. Unless you cancel during this duration, you’ll be charged $' . $plan->formattedPrice() . ' after ' . $plan->trial_period_days . ' days. Afterwards your subscription will renew automatically every ' . $plan->interval . ', but you can cancel anytime.'
				])
		@endcomponent
	</div>
</section>

@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('js/views/checkout.js')}}"></script>
@endpush