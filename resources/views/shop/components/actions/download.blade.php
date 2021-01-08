@if(auth()->check() && auth()->user()->purchasesOf($product)->exists())
<div class="mb-{{$mb ?? 2}} text-center">
	<a href="{{route('users.purchases')}}" class="btn btn-block btn-primary">@fa(['icon' => 'cloud-download-alt'])Download it again</a>
	<p class="text-muted m-0"><small>Downloaded on {{auth()->user()->purchasesOf($product)->first()->created_at->toFormattedDateString()}}</small></p>
</div>
@else
	@if($product->isFree())
	<form method="POST" action="{{$product->purchaseRoute()}}" class="d-inline">
		@csrf
		<button class="btn btn-block btn-primary mb-2">@fa(['icon' => 'cloud-download-alt'])Download now</button>
	</form>
	@else
	<a href="{{$product->checkoutRoute()}}" class="btn btn-block btn-primary mb-2">@fa(['icon' => 'shopping-cart'])Buy now</a>
	@endif
@endif