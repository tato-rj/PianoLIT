@component('layouts.overlay', ['name' => 'subscribe', 'light' => false, 'position' => 'center', 'background' => '0,0,0,0.8'])
<div class="mx-3" style="max-width: 512px">
	<div class="rounded text-center shadow-light">
		<div class="px-5 py-4 bg-white rounded">
			<form method="POST" action="{{route('subscriptions.store')}}" autocomplete="off">
				@csrf
				@include('components.form.subscription.hidden')

				<div class="form-group position-relative">
					<img src="{{asset('images/misc/paper-plane.svg')}}" class="w-100">
					<input required type="email" name="email" placeholder="EMAIL ADDRESS" 
						class="position-absolute input-invisible form-control w-100 input-center" 
						autocomplete="off" 
						style="bottom: 0; left: 0">
				</div>
				@include('components/form/error', ['field' => 'email'])
				<button type="submit" class="btn btn-primary shadow btn-wide mb-2 mt-3">SIGN UP</button>
				<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>

			</form>
		</div>
	</div>
</div>
@endcomponent