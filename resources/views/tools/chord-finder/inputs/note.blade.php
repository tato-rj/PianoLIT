<div class="col-lg-1 col-md-2 col-sm-3 col-4 p-1" style="max-width: 110px">
	<div class="text-center note note-inactive t-2" data-name="{{$note}}" data-octave="{{$octave ?? null}}">
		<h1 class="border rounded-top border-2x fw-bold m-0 cursor-pointer">{{$note}}<span data-steps="0"></span></h1>
		<div class="d-flex justify-content-between border-bottom border-2x rounded-bottom px-2 pb-1">
			<button class="fw-bold control text-white" data-symbol="b" disabled>@icon('circle-minus', ['mr' => 0, 'classes' => 'icon-size-lg'])</button>
			<button class="fw-bold control text-white" data-symbol="#" disabled>@icon('circle-plus', ['mr' => 0, 'classes' => 'icon-size-lg'])</button>
		</div>
	</div>
</div>