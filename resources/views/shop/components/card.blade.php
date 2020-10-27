<div class="grid-item rounded-0 thumbnail p-2 col-lg-4 col-md-6 col-6">
	<div class="position-relative no-click border">
		<a href="{{$item->showRoute()}}" class="link-none">
			@include('shop.components.cover', ['product' => $item])
		</a>
	</div>
</div>