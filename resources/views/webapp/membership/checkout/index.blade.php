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
            @include('webapp.membership.checkout.card')
		@endcomponent
	</div>
</section>

@endsection

@push('scripts')
<script src="{{ mix('js/views/membership-billing.js') }}" defer></script>
@endpush
