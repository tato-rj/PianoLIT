<section>
	<p class="section-title">Absence & make up policy</p>
	<p>Please take special note of this policy in order to avoid any misunderstanding.</p>

	<div class="highlight-box mb-3 important"> 
		<p class="text-center"><strong>IMPORTANT</strong></p>
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

	<p><u>Teacher cancellation</u></p>
	<p>If I have to cancel a lesson {!! $policy->get('teacher_absence') ? '<strong>we will schedule a make up</strong>' : 'there will be no make up, <strong>it will be refunded</strong> instead' !!}.</p>
	
	@if($policy->has('sickness_policy'))
	<p><u>Sickness</u></p> 
	<p>Please do not come to your lesson sick. Many students share the piano during the day.
		{!! $policy->get('sickness_policy') == 'make up' ? 
			'If the student is sick <strong>we will add a make up</strong> lesson.' :
			'We <strong>will not add a make up</strong> for the missed lesson in this case.' !!}
	</p>
	@endif

	@if($policy->get('summer_policy'))
	<p><u>Summer Absence Policy</u></p>
	<p>Even though sign-up is flexible during the summer, the make up policy is not. The same absence and make up rules listed in this section apply during the summer months.</p>
	@endif
</section>