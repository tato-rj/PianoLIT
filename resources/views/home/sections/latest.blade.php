		<div class="col-12">
			<h5 class="ml-2 mb-3" id="pieces-label">Latest pieces</h5>
			<div class="row" id="pieces-container" data-url="{{route('load-pieces')}}">
				@include('search.components.results.pieces', ['pieces' => $latest])
			</div>
		</div>