<div class="d-flex flex-center mb-2" style="">
	<div class="d-flex" style="margin-left: 28px">
		@foreach($folder->composers as $composer)
		<img src="{{$composer->cover_image}}" class="rounded-circle border border-white" style="width: 40px; height: 40px; margin-left: -28px">
		@endforeach
	</div>
</div>