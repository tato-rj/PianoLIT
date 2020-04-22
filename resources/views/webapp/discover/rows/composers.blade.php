<div class="mb-4">
	<h5>Composers</h5>
	<div class="custom-scroll dragscroll dragscroll-horizontal">
		<div class="d-flex pb-2">
			@foreach($row['content'] as $composer)
			<div class="cursor-pointer mr-3 text-center composers-card">
				<img src="{{$composer->cover_image}}" class="rounded-circle mb-2" style="width: 114px">
				<p class="m-0 clamp-2" style="line-height: 1"><small><strong>{{$composer->name}}</strong></small></p>
				<p class="text-muted m-0"><small>{{$composer->pieces_count}} pieces</small></p>
			</div>
			@endforeach
		</div>
	</div>
</div>