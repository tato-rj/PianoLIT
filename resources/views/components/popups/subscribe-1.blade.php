@component('layouts.overlay', ['name' => 'subscribe'])
<div class="row flex-center w-100 h-100">
	<div class="col-lg-4 col-md-8 col-10 mx-auto rounded text-center p-6 bg-white shadow-light">
		<img src="{{asset('images/misc/subscribe-flow.svg')}}" width="200" class="mb-4">
		<div class="mb-4">
			<div>Enjoy our latest posts directly in your inbox</div>
		</div>
		<form method="POST" action="{{route('subscriptions.store')}}">
			@csrf
			<div class="input-group mb-2">
				<input type="email" name="email" class="form-control p-3" style="border-top-left-radius: 32px; border-bottom-left-radius: 32px;" placeholder="Your email address">
				<div class="input-group-append">
					<button type="submit" class="btn btn-primary btn-wide" style="border-top-right-radius: 32px; border-bottom-right-radius: 32px;">Sign up</button>
				</div>
			</div>
			<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
		</form>
	</div>
</div>
@endcomponent