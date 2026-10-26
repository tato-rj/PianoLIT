<div class="col-lg-10 col-12 mx-auto text-center mb-4">
	<div class="mb-4">
		@include('shop.components.cover', ['product' => $purchase->item, 'maxWidth' => '280px'])
	</div>
	<div class="text-center">
		<p class="text-muted"><strong>Download your product here</strong></p>
		@if($infographicDownload ?? false)
		<a href="{{ $purchase->item->autoDownload() }}" class="btn btn-primary">@icon('file-down')Download {{ strtoupper(extension($purchase->item->cover_path) ?: 'JPG') }}</a>
		@else
		@foreach($purchase->item->links() as $label => $hash)
		<a href="{{route('shop.download', ['purchase' => $purchase, 'path' => $hash])}}" class="btn btn-wide btn-outline-secondary" target="_blank">@icon('cloud-download'){{$label}}</a>
		@endforeach
		@endif
	</div>
</div>
