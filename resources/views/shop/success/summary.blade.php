<div class="col-lg-10 col-12 mx-auto text-center mb-3">
	<h2 class="mb-4">Thank You, {{auth()->user()->first_name}}!</h2>
	<p>Your purchase of <u>{{$purchase->item->title}}</u> was successful.</p>
	
	@unless($purchase->item->isFree())
	<p>We sent an email to <strong>{{auth()->user()->email}}</strong> with your purchase confirmation. If the email hasn't arrive within a few minutes, please check your spam folder to see if the email was sent there.</p>
	@endunless

	<div><small>@fa(['icon' => 'clock', 'color' => 'blue', 'mr' => 1])<strong>Time placed:</strong> {{now()->toDayDateTimeString()}}</small></div>
</div>

