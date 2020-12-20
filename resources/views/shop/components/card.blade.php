@php($isSmall = isset($sm) && $sm)
<div class="grid-item rounded-0 thumbnail p-2 {{$isSmall ? 'col-lg-2 col-md-4' : 'col-lg-4 col-md-6'}} col-6">
	<div class="position-relative no-click">
		<a href="{{$item->showRoute()}}" class="link-none">
			@include('shop.components.cover', ['product' => $item])
			<div class="mt-2">
				<div class="clamp-1 font-weight-bold mb-1">{{$item->title}}</div>
				@include('shop.components.reviews.stars', ['product' => $item, 'sm' => true])
				@include('shop.components.price', ['product' => $item, 'sm' => true])
			</div>
		</a>
	</div>
</div>