<section class="container mb-5">
	<div class="row">
		<div class="col-lg-6 col-sm-8 col-12 mx-auto mb-4">
			<p class="text-muted"><small>DISCOVER</small></p>
			<h3 class="accent-bottom mb-4">Finding new pieces has never been so easy</h3>
			<p class="text-muted">Select below the ideas that match the kind of pieces you would like to find. Let's see what you'll discover!</p>
		</div>

		@include('home.sections.composers')

		@include('home.sections.freepicks')
		
		<div class="col-12">
			<div class="mb-5 pb-3 custom-scroll dragscroll dragscroll-horizontal">
				<div id="tags-search" class="d-flex " style="width: 2170px;">
					@foreach($tags as $type => $group)
					<div class="{{$loop->last ? null : 'border-right mr-4 pr-1'}}">
						<p class="h6">{{ucfirst($type)}}</p>
						<div class="d-flex flex-wrap ">
							@foreach($group as $tag)
						    <button 
						    	data-name="{{$tag->name}}"
						    	data-id="{{$tag->id}}"
						      class="tag btn btn-light badge-pill m-2 px-3 py-1 text-nowrap" style="font-weight: normal;">
						      {{$tag->name}}
						    </button>
							@endforeach
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>

		@include('home.sections.latest')
	</div>
</section>