@component('layouts.overlay', ['name' => 'subscribe'])
<div class="row align-items-center h-100">
	<div class="col-lg-4 col-md-8 col-md-8 col-11 mx-auto rounded text-center p-6 bg-white shadow-light">
		<img src="{{asset('images/misc/subscribe-flow.svg')}}" width="200" class="mb-4">
		<div class="mb-4">
			<div>Enjoy our latest posts directly in your inbox</div>
		</div>
		<form method="POST" action="{{route('subscriptions.store')}}">
			@csrf
			<div class="form-group">
				<input type="email" name="email" class="form-control input-center" placeholder="Your email address">
			</div>

			<button type="submit" class="btn btn-primary btn-block mb-2">Sign up</button>
			
			<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
		</form>
	</div>
</div>
@endcomponent