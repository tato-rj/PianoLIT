<section>
	@if(! $policy->has('hide_fees'))
	<p class="bold">TUITION & FEES</p>
	<ul>
		@foreach($policy->lessonFees() as $duration => $fee)
		<li>{{$duration}}-minute lessons: ${{"$fee[0]/$fee[1]"}}</li>
		@endforeach
	</ul>
	@endif
	
	@if($policy->has('extra_fees'))
		@if($policy->has('hide_fees'))
		<p class="bold">TUITION & FEES</p>
		@endif
		<p>{{$policy->get('extra_fees')}}</p>
	@endif
	
	<p class="bold">PAYMENT</p>
	<p>Invoices are sent by email in the middle of each month and payment is due by the first of the month. You
	can pay online or bring cash or check to the lesson. You may also save a credit card for automatic
	payments.</p>
</section>