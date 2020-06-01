<section class="mb-5">
	<div class="row">
		@each('webapp.membership.pricing.components.plan', $plans, 'plan')
	</div>
	<div class="text-right text-muted"><small>All prices are listed in US Dollars</small></div>
</section>