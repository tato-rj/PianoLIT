<section class="container mb-5">
	<div class="row">
		<div class="col-lg-6 col-sm-8 col-12 mx-auto mb-4">
			<p class="text-muted"><small>DISCOVER</small></p>
			<h3 class="accent-bottom mb-4">Finding new pieces has never been so easy</h3>
			<p class="text-muted">Select below the ideas that match the kind of pieces you would like to find. Let's see what you'll discover!</p>
		</div>
		<div class="col-12">
			<div id="tags-search-container" class="mb-5 pb-3 custom-scroll dragscroll" style="overflow-x: scroll; cursor: grab">
				<div id="tags-search" class="d-flex " style="width: 2170px;">
					@foreach($tags as $type => $group)
					<div class="{{$loop->last ? null : 'border-right mr-4 pr-1'}}">
						<h6>{{ucfirst($type)}}</h6>
						<div class="d-flex flex-wrap ">
							@foreach($group as $tag)
						    <span 
						    	data-name="{{$tag->name}}"
						    	data-id="{{$tag->id}}"
						      class="tag bg-light m-2 px-3 py-1 cursor-pointer"
						      style="-moz-user-select: none; 
						          -webkit-user-select: none; 
						               -ms-user-select:none; 
						                   user-select:none;
						                -o-user-select:none;
						                border-radius: 100px">
						      {{$tag->name}}
						    </span>
							@endforeach
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>

		<div class="col-12">
			<h5 class="ml-2 mb-3" id="pieces-label">Latest pieces</h5>
			<div class="row" id="pieces-container">
				@include('components.pieces.display', ['pieces' => $latest])
			</div>
		</div>
	</div>
</section>