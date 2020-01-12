<header>
	<div>
		<div id="pdf-title">
			<div>
				<h2 class="bold m-0" style="text-transform: uppercase;"><span>{{$policy->data['name']}}</span> PIANO STUDIO</h2>
				<h3 class="m-0">POLICY HANDBOOK FOR STUDENTS & FAMILIES</h3>
			</div>
		</div>
		<h3 id="pdf-year">{{$policy->has('start_year') ? $policy->data['start_year'].'/' : 'until'}} {{$policy->has('end_year') ? $policy->data['end_year'] : 'current season'}}</h3>
	</div>

	@if($policy->has('welcome'))
	<p id="welcome-message">{{$policy->get('welcome')}}</p>
	@endif
</header>

<section>
	<p class="section-title">Lessons overview</p>
		@if($policy->has('start_month') && $policy->get('end_month'))
		@php($weeks = carbon($policy->get('start_month') . '-1-2000')->diffInWeeks(carbon($policy->get('end_month') + 1 . '-1-2001')))
		@else
		@php($weeks = null)
		@endif

		@if($weeks)
		<p>Piano students receive {{$weeks - $policy->get('vacation_weeks') - $policy->get('makeup_weeks')}} scheduled weekly lessons from {{getMonthName($policy->get('start_month'))}} through {{getMonthName($policy->get('end_month'))}}{{count($policy->events()) > 0 ? ' with ' . arrayToSentence($policy->events()) : null}}.{{$policy->get('provide_calendar') ? ' Please see the Studio Calendar for this year’s complete list of lessons, recitals and other important dates.' : null}}</p>
		@endif

		@if($policy->fees())
		<p>Lessons are {{arrayToSentence(array_keys($policy->fees()), 'or')}} long.
		@endif
	</p>
	
	@if($policy->has('contact_methods'))
	<p>The best {{str_plural('way', count($policy->get('contact_methods')))}} to contact me {{count($policy->get('contact_methods')) > 1 ? 'are' : 'is'}} by <u>{{arrayToSentence($policy->get('contact_methods'), 'or')}}</u>.</p>
	@endif
	
	@if($policy->has('observation'))
	<p class="section-title">Parental involvement</p>
	<p>{{$policy->get('observation')}}</p>
	@endif
</section>