<section class="mb-3">
	<div class="row">
		@each('webapp.membership.pricing.components.plan', $plans, 'plan')
	</div>
	<div class="text-right text-muted"><small>All prices are listed in US Dollars</small></div>
</section>

<section class="">
	<div class="row p-2">
		<div class="col-lg-12 col-md-12 col-11 mx-auto p-5 rounded border mb-5 text-center">
			<h3 class="mb-4">@fa(['icon' => 'graduation-cap'])Institutional pricing</h3>
			<p class="m-0">Looking to get PianoLIT for your entire studio, department, or school? Send an email to <a target="_blank" href="mailto:contact@pianolit.com?subject=Institutional pricing inquiry" class="link-primary">contact@pianolit.com</a> and we'll create a plan perfectly suited for your needs.</p>
		</div>
	</div>
</section>