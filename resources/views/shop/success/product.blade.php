<div class="col-lg-10 col-12 mx-auto text-center">
	<div class="mb-4">
		<img src="{{storage($purchase->item->shelf_cover_path)}}" style="width: 200px" class="mb-2">
		<h5>{{$purchase->item->title}}</h5>
	</div>
	<div>
		@foreach($purchase->item->links() as $label => $hash)
		<a href="{{route('shop.download', ['purchase' => $purchase, 'path' => $hash])}}" class="btn btn-wide btn-outline-secondary" target="_blank">@fa(['icon' => 'cloud-download-alt']){{$label}}</a>
		@endforeach
	</div>
</div>