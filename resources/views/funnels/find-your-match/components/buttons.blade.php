<div class="container-fluid">
	<div class="row carousel-answers no-gutters">
	@foreach($buttons as $button)
	<div class="col-lg-4 col-md-4 col-6 p-1">
		<div data-carousel="answer" data-type="multi" value="{{$button}}" class="py-3 rounded cursor-pointer w-100 list-group-item list-group-item-action border-0">
			@fa(['icon' => 'tag']){{$button}}
		</div>
	</div>
	@endforeach
	</div>
</div>