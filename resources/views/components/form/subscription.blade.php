<form method="POST" action="{{route('subscriptions.store')}}">
	@csrf
	<input type="hidden" name="subscription_name" placeholder="Your name here">
	<div class="form-row mb-5">
		<div class="col-lg-6 col-md-8 col-10 mx-auto">
			<div class="form-group">
				<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 input-light">
			</div>
			@include('components/form/error', ['field' => 'email'])
			<button type="submit" class="btn btn-primary shadow btn-block mb-2">{{$label ?? 'SIGN UP'}}</button>
			<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
		</div>
	</div>
</form>