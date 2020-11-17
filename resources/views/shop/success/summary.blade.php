<div class="col-lg-10 col-12 mx-auto text-center mb-3">
	<h1>@fa(['icon' => 'check-circle', 'fa_type' => 'r', 'color' => 'green', 'mr' => 0, 'size' => 'lg'])</h1>
	<h2 class="mb-4">Thank You, {{auth()->user()->first_name}}!</h2>

	@if($purchase->item->autoDownload())
	<p>Your download will automatically start in <span id="countdown-seconds" class="font-weight-bold" data-href="{{$purchase->item->autoDownload()}}">5</span> seconds...<br>Download not starting? Try this <a href="{{$purchase->item->autoDownload()}}">direct download link</a>.</p>
	@else
	<p>Your purchase of <u>{{$purchase->item->title}}</u> was successful. Please click on the button below to download your item.</p>
	@endif
	
	@unless($purchase->item->isFree())
	<p>We sent an email to <strong>{{auth()->user()->email}}</strong> with your purchase confirmation. If the email hasn't arrive within a few minutes, please check your spam folder to see if the email was sent there.</p>
	@endunless

	<div><small>@fa(['icon' => 'clock', 'color' => 'blue', 'mr' => 1])<strong>Time placed:</strong> {{now()->toDayDateTimeString()}}</small></div>
</div>

