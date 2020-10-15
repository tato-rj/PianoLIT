<div class="col-lg-10 col-12 mx-auto text-center">
	<div class="mb-4">
		@include('shop.components.cover', ['product' => $purchase->item, 'maxWidth' => '280px'])
	</div>
	<div class="text-center">
		<p class="text-muted">Download your product below</p>
		@foreach($purchase->item->links() as $label => $hash)
		<a href="{{route('shop.download', ['purchase' => $purchase, 'path' => $hash])}}" class="btn btn-wide btn-outline-secondary" target="_blank">@fa(['icon' => 'cloud-download-alt']){{$label}}</a>
		@endforeach
	</div>
</div>