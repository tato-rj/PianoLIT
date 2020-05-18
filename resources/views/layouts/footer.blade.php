@empty($raw)
<footer class="bg-light py-6">
	<div class="container">
		<div class="col-lg-8 col-md-10 col-12 mx-auto text-center">
			<h2>Subscribe to our newsletter</h2>
			<p class="text-muted mb-4">We'll send you one monthly email with news, updates and fun piano resources we discover</p>
			<div class="row mb-3">
				<div class="col-lg-6 col-md-8 col-10 mx-auto">
					@include('components.form.subscription')
				</div>
			</div>
			<div class="mb-5">
				<ul class="d-flex list-flat justify-content-center">
					<li class="m-2 text-muted"><a href="{{route('privacy')}}" class="link-inherit">Privacy policy</a></li>
					<li class="m-2 text-muted"><a href="{{route('terms')}}" class="link-inherit">Terms of service</a></li>
					<li class="m-2 text-muted"><a href="{{route('contact')}}" class="link-inherit">Contact us</a></li>
				</ul>
				@include('layouts.footer.social')
			</div>
			<p class="text-muted m-0"><small>MADE WITH ❤ BY LEFTLANE</small></p>
		</div>
	</div>
</footer>
@endempty