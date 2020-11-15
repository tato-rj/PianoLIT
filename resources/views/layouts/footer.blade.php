@empty($raw)
<footer class="bg-light py-6">
	<div class="container">
		<div class="col-lg-8 col-md-10 col-12 mx-auto text-center">
			@icon(['mb' => 2])
			<p class="text-muted">{{seo()->about('moto')}}</p>
			<div class="mb-4">
				@cta(['type' => 'ios', 'classes' => 'mb-2'])
				@cta(['type' => 'webapp', 'classes' => 'mb-2'])
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