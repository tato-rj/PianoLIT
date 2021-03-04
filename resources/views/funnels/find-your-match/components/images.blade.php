<div class="container-fluid">
	<div class="carousel-answers row"> 
		@foreach($images as $filename => $info)
		<div class="col-lg-4 col-md-4 col-6 p-2">
			<div class="rounded cursor-pointer list-group-item list-group-item-action border-0 w-100 p-3" data-carousel="answer" data-type="multi" value="{{$info[1]}}">
				<div class="p-3">
					<img src="{{$filename}}" class="rounded w-100">
				</div>
				<div class="text-center">
					<div style="line-height: 1" class="mb-1">{{$info[0]}}</div>
				</div>
			</div>
		</div>
		@endforeach
	</div>
</div>