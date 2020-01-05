<section>
	@if($policy->fees())
		<p class="section-title">Tuition & fees</p>
		@if($policy->feesPer('lesson'))
		<p>My rates per lesson are</p>
		<ul>
			@foreach($policy->feesPer('lesson') as $duration => $fee)
			<li>{{$duration}} lessons: ${{"$fee"}}/lesson</li>
			@endforeach
		</ul>
		@endif
		@if($policy->feesPer('month'))
		<p>My rates per month are</p>
		<ul>
			@foreach($policy->feesPer('month') as $duration => $fee)
			<li>{{$duration}} lessons: ${{"$fee"}}/month</li>
			@endforeach
		</ul>
		@endif
	@endif
	
	@if($policy->has('extra_fees'))
		@if(! $policy->fees())
		<p class="section-title">Tuition & fees</p>
		@endif
		<p>{{$policy->get('extra_fees')}}</p>
	@endif
</section>