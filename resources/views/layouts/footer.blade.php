<footer class="bg-light py-6">
	<div class="container">
		<div class="col-lg-8 col-md-10 col-12 mx-auto text-center">
			<h2 class="mb-5">Subscribe for updates</h2>
			<form method="POST" action="{{route('subscriptions.store')}}">
				@csrf
				<div class="form-row mb-5">
					<div class="col-lg-6 col-md-8 col-10 mx-auto">
						<div class="form-group">
							<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 border-0 bg-grey-lighter">
						</div>
						@include('components/form/error', ['field' => 'email'])
						<button type="submit" class="btn btn-primary shadow btn-block">SIGN UP</button>
					</div>
				</div>
			</form>
			<div class="mb-5">
				<ul class="d-flex list-flat justify-content-center">
					<li class="m-2 text-muted"><a href="#" class="link-inherit">blog</a></li>
					<li class="m-2 text-muted"><a href="#" class="link-inherit">press</a></li>
					<li class="m-2 text-muted"><a href="#" class="link-inherit">privacy</a></li>
					<li class="m-2 text-muted"><a href="#" class="link-inherit">contact us</a></li>
				</ul>
				<ul class="d-flex list-flat justify-content-center social-icons">
					<li class="m-2"><a href="#" class="link-inherit"><i class="fab fa-twitter"></i></a></li>
					<li class="m-2"><a href="#" class="link-inherit"><i class="fab fa-youtube"></i></a></li>
					<li class="m-2"><a href="#" class="link-inherit"><i class="fab fa-angellist"></i></a></li>
				</ul>
			</div>
			<p class="text-muted m-0"><small>MADE WITH ❤ BY LEFTLANE</small></p>
		</div>
	</div>
</footer>