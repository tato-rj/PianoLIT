<section>
	<p class="section-title">Scheduling</p>

	<p>From {{getMonthName($policy->get('start_month'))}} through {{getMonthName($policy->get('end_month'))}}, we have weekly lessons{{count($policy->events()) > 0 ? ' plus ' . arrayToSentence($policy->events()) : null}}{{$policy->get('provide_calendar') ? ' according to the calendar' : null}}. If your lesson day/time is not ideal or it needs to change during the year, it may be possible to switch with another student, but that is not guaranteed.</p>

	@if($policy->get('provide_calendar'))
	<p>I provide all students with a Studio Calendar that lists all lessons, make-up days, vacation days, special events, etc. School district calendars in our area may be different, so please refer to our Studio Calendar to know when lessons are scheduled.</p>
	@endif

	<p class="section-title">Summer schedule</p>
	@if($policy->has('summer_teaching'))
		<p>The summer schedule starts in {{getMonthName($policy->get('end_month') + 1)}} and goes until {{getMonthName($policy->get('start_month') - 1)}}.</p>

		@if($policy->has('summer_description'))
		<p>{{$policy->get('summer_description')}}{{str_ends_with($policy->get('summer_description'), ['.', '!']) ? null : '.'}}</p>
		@else
		<p>We decide what is the best schedule and payment method for the summer months at the end of each year.</p>
		@endif
	@else
		<p><u>I do not teach during the summer months.</u></p>
	@endif
</section>