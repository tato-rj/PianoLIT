<form method="POST" action="{{route('subscriptions.store')}}">
	@csrf
	<input type="hidden" name="subscription_name" placeholder="Your name here">
	<input type="hidden" name="started_at" value="{{now()}}">

	<div class="form-group">
		<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 input-light">
	</div>
	@include('components/form/error', ['field' => 'email'])
	<button type="submit" class="btn btn-primary shadow btn-block mb-2">{{$label ?? 'SIGN UP'}}</button>
	<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>

</form>