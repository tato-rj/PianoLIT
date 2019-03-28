@component('layouts.overlay', ['name' => 'subscribe', 'position' => 'center'])
<div class="mx-2">
	<div class="rounded text-center shadow-light">
		<img src="{{asset('images/gifts/circle-of-fifths-wall.jpg')}}" width="512">
		<div class="px-4 py-3 bg-white">
			<div class="mb-4">
				<div>Subscribe today and get a <strong><u>FREE</u></strong> poster in your inbox!</div>
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
</div>
@endcomponent