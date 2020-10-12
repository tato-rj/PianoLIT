<div class="col-lg-3 col-md-4 col-6">
	<div style="width: 80%" class="mx-auto d-block">
		<a href="{{$suggestion->showRoute()}}" class="link-none text-center">
			@include('shop.components.cover', ['product' => $suggestion])
			<p class="clamp-1 alert-grey p-1" style=""><strong>{{$suggestion->title}}</strong></p>
		</a>
	</div>
</div>