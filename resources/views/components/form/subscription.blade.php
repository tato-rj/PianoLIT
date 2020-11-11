<form method="POST" action="{{route('subscriptions.store')}}" id="{{$id ?? null}}">
	@csrf

	@include('components.form.subscription.hidden')

	<div class="form-group">
		<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 input-light">
	</div>
	@include('components/form/error', ['field' => 'email'])
	
	@isset($id)
	@env('production')
	@include('auth.fields.recaptcha')
	@endenv
	@endisset

	@button([
		'label' => $label ?? 'SIGN UP',
		'submit' => true,
		'styles' => [
			'shadow' => true,
			'size' => 'block',
			'mb' => 2,
			'theme' => 'primary',
		]])

	<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>

</form>