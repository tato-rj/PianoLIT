<section class="mb-5">
	<div class="row">
		@include('webapp.pricing.components.plan', [
			'bestvalue' => true,
			'plan' => 'yearly',
			'price' => [7.49, 89.99],
			'description' => 'Save big with our most popular plan! This starts with a 7-day free trial so you can explore Blinkist with zero risk.'
		])
		@include('webapp.pricing.components.plan', [
			'plan' => 'monthly',
			'price' => [9.99],
			'description' => 'Try it out with the monthly plan. With this option, you can see if Blinkist is for you and you can cancel anytime.'
		])
	</div>
</section>