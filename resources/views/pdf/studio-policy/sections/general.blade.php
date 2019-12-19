<section>
	<p class="bold">LESSONS OVERVIEW</p>
	@php($weeks = carbon($policy->get('start_month') . '-1-' . $policy->get('start_year'))->diffInWeeks(carbon($policy->get('end_month') + 1 . '-1-' . $policy->get('end_year'))))

	<p>
		Piano students receive {{$weeks - $policy->get('vacation_weeks') - $policy->get('makeup_weeks')}} scheduled weekly lessons from {{getMonthName($policy->get('start_month'))}} through {{getMonthName($policy->get('end_month'))}}{{count($policy->events()) > 0 ? ' with ' . arrayToSentence($policy->events()) : null}}. Please see the Studio Calendar for this year’s complete list of	scheduled lessons, classes, and recitals. Lessons are {{arrayToSentence(array_keys($policy->lessonFees()), 'or')}} minutes long.
	</p>
	
	@if($policy->has('contact_methods'))
	<p>The best {{str_plural('way', count($policy->get('contact_methods')))}} to contact me {{count($policy->get('contact_methods')) > 1 ? 'are' : 'is'}}: <u>{{arrayToSentence($policy->get('contact_methods'))}}</u>.</p>
	@endif

	@if($policy->has('expectations'))
	<p class="bold">EXPECTATIONS</p>
	<ul>
		@foreach($policy->get('expectations') as $expectation)
		<li>{{$expectation}}</li>
		@endforeach
	</ul>
	@endif
	
	@if($policy->has('observation'))
	<p class="bold">PARENTAL INVOLVEMENT</p>
	<p>{{$policy->get('observation')}}</p>
	@endif
</section>