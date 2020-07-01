@extends('webapp.layouts.app')

@push('header')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Checkout', 'subtitle' => 'Almost there! Please review carefully the details below.'])

<section class="row">
  @include('webapp.membership.checkout.summary')

  @include('shop.checkout.form', [
    'action' => route('webapp.membership.purchase', $plan),
    'label' => 'Subscribe now for $' . $plan->formattedPrice(),
    'comments' => 'Your free trial will start today and end on ' . now()->addDays(7)->toFormattedDateString() . '. Unless you cancel during this duration, you’ll be charged $' . $plan->formattedPrice() . ' after ' . $plan->trial_period_days . ' days. Afterwards your subscription will renew automatically every ' . $plan->interval . ', but you can cancel anytime.'
  ])
</section>

@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('js/views/checkout.js')}}"></script>
@endpush