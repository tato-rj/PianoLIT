<section>
	@if($policy->has('group_classes'))
	<p class="bold">GROUP CLASSES</p>
	<p>{{$policy->get('group_classes_description')}}</p>
	@endif

	@if($policy->has('recitals'))
	<p class="bold">RECITALS</p>
	<p>{{$policy->get('recitals_description')}}</p>
	@endif

	@if($policy->has('extra_performances'))
	<p class="bold">EXTRA PERFORMANCE OPPORTUNITIES</p>
	<p>{{$policy->get('extra_performances')}}</p>
	@endif
</section>