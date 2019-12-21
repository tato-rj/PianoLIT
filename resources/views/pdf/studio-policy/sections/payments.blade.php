<section>
	<p class="section-title">Payments</p>
	@if($policy->has('payment_due'))
	<p>Payment is due {{arrayToSentence($policy->get('payment_due'), 'or')}}.</p> 
	@endif
	
	@if($policy->has('payment_methods'))
	<p>I accept the following payment methods:</p>
	<ul>
		@foreach($policy->get('payment_methods') as $method)
		<li>{{$method}}</li>
		@endforeach
	</ul>
	@endif
	
	@if($policy->has('payment_note'))
	<p>{{$policy->get('payment_note')}}</p>
	@endif
</section>