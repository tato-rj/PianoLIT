<section>
	<p class="bold">ABSENCE & MAKE UP POLICY</p>
	<p>Please take special note of this policy in order to avoid any misunderstanding.</p>

	<div class="box mb-3"> 
		<p class="text-center border-bottom pb-2"><strong>IMPORTANT</strong></p>
		@if($policy->get('absence_notice') == 'always')
		<p class="text-center m-0">ANY MISSED LESSONS WILL BE OFFERED A {{strtoupper($policy->cancellationOffers())}}</p>
		@elseif($policy->get('absence_notice') == 'never')
		<p class="text-center m-0">MISSED LESSONS WILL <strong>NOT</strong> BE OFFERED A REFUND OR A MAKE UP</p>
		@else
		<p class="text-center m-0">MISSED LESSONS WILL BE OFFERED A {{strtoupper($policy->cancellationOffers())}} <u>IF CANCELLED WITH MORE THAN {{strtoupper($policy->get('absence_notice'))}} HOURS NOTICE</u></p>
		@endif
	</div>

	@if($policy->acceptCancellations())
	<p>No-show or cancellation with less than {{$policy->get('absence_notice')}} hours’ notice: You have forfeited the lesson and it will not be
	made up.</p>
	<p>Advance reschedule or cancellation: I provide one Student Make Up Day per semester (see Studio
		Calendar). I can sometimes facilitate a conversation between students to switch times with each other, but
	this is at my discretion and not guaranteed.</p>
	@endif

	<p><u>Teacher cancellation</u>: In the event that I must cancel, I will offer 3 options for an additional make up day.
	Students who do not sign up for a make up lesson will not be offered additional options.</p>
	
	@if($policy->has('sickness_policy'))
	<p><u>Sickness</u>: Please do not come to your lesson sick. Many students share the piano during the day.
		{!! $policy->get('sickness_policy') == 'make up' ? 
			'If the student is sick <strong>we will add a make up</strong> lesson.' :
			'We <strong>will not add a make up</strong> for the missed lesson in this case.' !!}
	</p>
	@endif

	<p><u>Summer Absence Policy</u></p>
	<p>Even though sign-up is flexible during the summer, the absence policy is not. Please choose carefully! When
		you sign up for a lesson, it is reserved for you and is not shown as an available time for other interested
	students. For that reason, I do not reschedule and/or refund missed lessons, even in summer.</p>

	<p><u>Teaching Artist Commitment</u></p>
	<p>I balance my teaching schedule with an active freelancing career. Your
		scheduled lessons are yours, and I do not accept conflicting gigs or rehearsals. However, for very
		extraordinary opportunities such as a tour or residency, I reserve the right to temporarily change my
		teaching schedule. In this case, I work with students individually to come up with the best option for that
		period of time, which may include prorated tuition, additional make ups, or arranging for a sub. This is very
		rare. I always do my best to ensure that these two branches of my work dovetail with each other and
	inform one another.</p>
</section>