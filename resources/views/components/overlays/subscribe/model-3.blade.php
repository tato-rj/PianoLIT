@component('layouts.overlay', ['name' => 'subscribe', 'light' => true, 'position' => 'center', 'background' => '255,255,255,0.9'])
<div class="mx-3">
	<div class="rounded text-center p-6 bg-white shadow-light">
		<img src="{{asset('images/misc/subscribe-flow.svg')}}" width="200" class="mb-4">
		<div class="mb-4">
			<div>Get news from our latest quizzes directly in your inbox</div>
		</div>
		
		<form method="POST" action="{{route('subscriptions.store')}}">
			@csrf
			@include('components.form.subscription.hidden')
			<div class="form-group">
				<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 input-light">
			</div>
			@include('components/form/error', ['field' => 'email'])
			<button type="submit" class="btn btn-primary shadow btn-block mb-2">{{$label ?? 'SIGN UP'}}</button>
			<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
		</form>
	</div>
</div>
@endcomponent