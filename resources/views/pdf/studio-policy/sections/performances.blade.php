<section>
	@if($policy->has('group_classes_description'))
	<p class="section-title">Group classes</p>
	<p>{{$policy->get('group_classes_description')}}</p>
	@endif

	@if($policy->has('recitals_description'))
	<p class="section-title">Recitals</p>
	<p>{{$policy->get('recitals_description')}}</p>
	@endif

	@if($policy->has('extra_performances'))
	<p class="section-title">Extra performance opportunities</p>
	<p>{{$policy->get('extra_performances')}}</p>
	@endif
</section>