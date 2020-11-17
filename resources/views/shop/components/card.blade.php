<div class="grid-item rounded-0 thumbnail p-2 {{isset($sm) && $sm ? 'col-lg-2 col-md-4' : 'col-lg-4 col-md-6'}} col-6">
	<div class="position-relative no-click">
		<a href="{{$item->showRoute()}}" class="link-none">
			@include('shop.components.cover', ['product' => $item])
		</a>
	</div>
</div>