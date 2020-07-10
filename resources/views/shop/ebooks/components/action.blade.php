@if(auth()->check() && auth()->user()->purchasesOf($ebook)->exists())
<p class="text-muted mb-2">You've downloaded this ebook on {{auth()->user()->purchasesOf($ebook)->first()->created_at->toFormattedDateString()}}</p>
<a href="{{route('users.purchases')}}" class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'cloud-download-alt'])Download it again</a>
@else
	@if($ebook->isFree())
	<form method="POST" action="{{route('ebooks.purchase', $ebook)}}" class="d-inline">
		@csrf
		<button class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'cloud-download-alt'])Download now</button>
	</form>
	@else
	<a href="{{route('ebooks.checkout', $ebook)}}" class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'shopping-cart'])Buy now</a>
	@endif
@endif