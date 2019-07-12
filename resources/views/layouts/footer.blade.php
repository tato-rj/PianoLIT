<footer class="bg-light py-6">
	<div class="container">
		<div class="col-lg-8 col-md-10 col-12 mx-auto text-center">
			<h2 class="mb-5">Subscribe for updates</h2>
			<div class="row mb-5">
				<div class="col-lg-6 col-md-8 col-10 mx-auto">
					@include('components.form.subscription')
				</div>
			</div>

			<div class="mb-5">
				<ul class="d-flex list-flat justify-content-center">
					<li class="m-2 text-muted"><a href="{{route('posts.index')}}" class="link-inherit">blog</a></li>
					<li class="m-2 text-muted"><a href="{{route('youtube')}}" class="link-inherit">youtube</a></li>
					{{-- <li class="m-2 text-muted"><a href="#" class="link-inherit">press</a></li> --}}
					{{-- <li class="m-2 text-muted"><a href="#" class="link-inherit">privacy</a></li> --}}
					<li class="m-2 text-muted"><a href="mailto:contact@leftlaneapps.com" class="link-inherit">contact us</a></li>
				</ul>
				<ul class="d-flex list-flat align-items-center justify-content-center social-icons">
					<li class="m-2"><a target="_blank" href="{{config('services.channels.facebook')}}" class="link-inherit"><i class="fab fa-facebook-f"></i></a></li>
					<li class="m-2"><a target="_blank" href="{{config('services.channels.youtube')}}" class="link-inherit"><i class="fab fa-youtube"></i></a></li>
					<li class="m-2"><a target="_blank" href="{{config('services.channels.twitter')}}" class="link-inherit"><i class="fab fa-twitter"></i></a></li>
					<li class="m-2"><a target="_blank" href="{{config('services.channels.pinterest')}}" class="link-inherit"><i class="fab fa-pinterest"></i></a></li>
					<li class="m-2"><a target="_blank" href="{{config('services.channels.reddit')}}" class="link-inherit"><i class="fab fa-reddit"></i></a></li>
					<li class="m-2"><a href="{{config('services.channels.instagram')}}" class="link-inherit"><i class="fab fa-instagram"></i></a></li>
				</ul>
			</div>
			<p class="text-muted m-0"><small>MADE WITH ❤ BY LEFTLANE</small></p>
		</div>
	</div>
</footer>