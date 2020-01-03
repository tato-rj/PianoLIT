@if($policy->data['parent_agreement'] || $policy->data['student_agreement'])
<div class="page-break"></div>
<section>
	@if($policy->data['parent_agreement'])
	<div class="signature-box">
		<h3 class="section-title">Parent/Guardian Agreement</h3>

		<p><span class="square"></span>I have a copy of the Studio Policy Handbook. I have read it, and agree to adhere to all policies,
		including:</p>
		<ul class="list-style-none">
			<li><span class="square"></span>Payment policy</li>
			<li><span class="square"></span>Absence & Make-up Policy</li>
			@if($policy->has('withdrawal_fee'))
			<li><span class="square"></span>Withdrawal & Discontinuation Policy</li>
			@endif
		</ul>

		<p><span class="square"></span>I understand that my lesson time is set for the semester, and that changing my lesson time is not
		guaranteed.</p>

		<div class="signature">
			<p>Parent/Guardian Signature & Date</p>
		</div>
	</div>
	@endif

	@if($policy->data['student_agreement'])
	<div class="signature-box">
		<h3 class="section-title">Student Agreement</h3>

		<p><span class="square"></span>Practice is my responsibility.</p>
		<p><span class="square"></span>I agree to practice what is in my assignment book every day. This is	how I will learn how to be a great pianist!</p>
		<p><span class="square"></span>I agree to be respectful and to always be well prepared for my piano lesson.</p>

		<div class="signature">
			<p>Student Signature</p>
		</div>
	</div>
	@endif
</section>
@endif