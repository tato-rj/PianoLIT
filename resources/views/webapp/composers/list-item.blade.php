<div class="col-lg-3 col-md-4 col-6 text-center mb-3 composer-card">
<a href="{{route('webapp.composers.show', $composer)}}" class="link-none px-2">
	<div class="text-center mb-4">
		<img src="{{$composer->cover_image}}" style="width: 110px" class="rounded-circle shadow mb-3">
		<h6 class="mb-0">{{$composer->name}}</h6>
		<div>
			
			<small class="text-muted">@flag(['code' => $composer->country->flag_code]){{$composer->country->name}}</small>
		</div>
	</div>
</a>
</div>