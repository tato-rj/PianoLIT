@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Pick the 3 composers you like the most'])

<div class="container-fluid">
	<div class="carousel-answers row"> 
		@foreach($composers as $composer)
		<div class="col-lg-3 col-md-4 col-6 p-2">
			<div class="rounded cursor-pointer list-group-item list-group-item-action border-0 w-100 p-3" data-carousel="answer" data-type="multi" value="{{$composer->mood}}">
				<div class="p-3">
					<img src="{{$composer->cover_image}}" class="rounded w-100">
				</div>
				<div class="text-center">
					<div style="line-height: 1" class="mb-1">{{$composer->short_name}}</div>
				</div>
			</div>
		</div>
		@endforeach
	</div>
</div>

@endcomponent