@if(auth()->check() && auth()->user()->purchasesOf($product)->exists())
<p class="text-muted mb-2">You've downloaded this on {{auth()->user()->purchasesOf($product)->first()->created_at->toFormattedDateString()}}</p>
<a href="{{route('users.purchases')}}" class="btn btn-wide btn-primary mb-2">@fa(['icon' => 'cloud-download-alt'])Download it again</a>
@else
	@if($product->isFree())
	<form method="POST" action="{{$product->purchaseRoute()}}" class="d-inline">
		@csrf
		<button class="btn btn-wide btn-primary mb-2">@fa(['icon' => 'cloud-download-alt'])Download now</button>
	</form>
	@else
	<a href="{{$product->checkoutRoute()}}" class="btn btn-wide btn-primary mb-2">@fa(['icon' => 'shopping-cart'])Buy now</a>
	@endif
@endif