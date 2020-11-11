@empty($raw)
<footer class="bg-light py-6">
	<div class="container">
		<div class="col-lg-8 col-md-10 col-12 mx-auto text-center">
			<h2>Subscribe to our newsletter</h2>
			<p class="text-muted mb-4">We'll send you one monthly email with news, updates and fun piano resources we discover</p>
			<div class="row mb-3">
				<div class="col-lg-6 col-md-8 col-10 mx-auto">
					@include('components.form.subscription', ['id' => 'footer-subscription-form'])
				</div>
			</div>
			<div class="mb-5">
				@include('layouts.footer.links')
				@include('layouts.footer.social')
			</div>
			<p class="text-muted m-0"><small>MADE WITH ❤ BY LEFTLANE</small></p>
		</div>
	</div>
</footer>
@endempty