<section>
	@if(! $policy->has('hide_fees'))
	<p class="section-title">Tuition & fees</p>
	<ul>
		@foreach($policy->lessonFees() as $duration => $fee)
		<li>{{$duration}}-minute lessons: ${{"$fee[0]/$fee[1]"}}</li>
		@endforeach
	</ul>
	@endif
	
	@if($policy->has('extra_fees'))
		@if($policy->has('hide_fees'))
		<p class="section-title">Tuition & fees</p>
		@endif
		<p>{{$policy->get('extra_fees')}}</p>
	@endif
</section>