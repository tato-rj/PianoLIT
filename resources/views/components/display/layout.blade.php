<div class="row">
	@isset($content)
		<div class="col-lg-9 col-md-8 col-12 mb-2 order-lg-1 order-md-1 order-2">
			{{$content}}
		</div>
		<div class="col-lg-3 col-md-4 col-12 order-lg-2 order-md-2 order-1">
			@include('components.display.topics')
			@include('components.display.ads', ['vertical' => true, 'mobile' => false])
		</div>
	@else
		<div class="col-lg-9 col-md-8 col-12 mb-2 order-lg-1 order-md-1 order-2">
			<div class="position-absolute pt-7 w-100" style="top: 0; left: 50%; transform: translateX(-50%);">
				@include('components.animations.loading')
			</div>
			@paginationCount(['align' => 'left'])

			<div class="grid row mb-4">
				{{$items}}
			</div>

	      @pagination
		</div>
		<div class="col-lg-3 col-md-4 col-12 order-lg-2 order-md-2 order-1">

			@include('components.display.topics')

			{{$extra ?? null}}
			

			@include('components.display.ads', ['vertical' => true, 'mobile' => false])
		</div>
	@endisset
	<div class="col-12 order-3">
		@include('components.display.ads', ['vertical' => true, 'mobile' => true])
	</div>
</div>
