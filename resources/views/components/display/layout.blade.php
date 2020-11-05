<div class="row">
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
		@if(count($topics) > 0)
		<div class="mb-5">
			<div class="d-flex d-apart mb-1 pb-1 border-bottom">
				<p class="text-muted mb-0"><strong>TOPICS</strong></p>
				<a href="{{route(request()->route()->getName())}}" class="text-muted"><small>reset</small></a>
			</div>
	        <div>
	          @foreach($topics as $topic)
	          <a href="{{route(request()->route()->getName(), request('topics') == $topic->slug ? null : ['topics' => $topic->slug])}}" class="btn btn-{{request('topics') == $topic->slug ? 'primary' : 'light'}} m-1 btn-sm text-muted">{{ucfirst($topic->name)}}</a>
	          @endforeach
	        </div>
	    </div>
	    @endif

		{{$extra ?? null}}
		

		@include('components.display.ads', ['vertical' => true, 'mobile' => false])
	</div>
	<div class="col-12 order-3">
		@include('components.display.ads', ['vertical' => true, 'mobile' => true])
	</div>
</div>
