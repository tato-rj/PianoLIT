<section>
	<p class="section-title">Withdrawal from lessons</p>
	<p>If you need to withdraw from lessons, please provide as much notice as possible.
		@if($policy->has('withdrawal_fee'))
		I charge a <strong>${{$policy->get('withdrawal_fee')}} fee</strong> for withdrawal requests
			@if($policy->has('withdrawal_month'))
			 <u>unless it is done during the month of {{getMonthName($policy->get('withdrawal_month'))}}</u>. In that case, you may request a withdrawal with no extra fee.
			@else
			regardless of when the request is made.
			@endif
		 This fee is necessary because it may be unlikely that the spot you reserved will be filled by someone else immediately following the withdrawal.
		@endif
	</p>
</section>