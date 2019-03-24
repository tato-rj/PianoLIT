<section class="container mb-5">
	<div class="row">
		<div class="col-6 mx-auto mb-4">
			<p class="text-muted"><small>DISCOVER</small></p>
			<h3 class="accent-bottom mb-4">Finding new pieces has never been so easy</h3>
			<p class="text-muted">Select below the ideas that match the kind of pieces you would like to find. Let's see what you'll discover!</p>
		</div>
		<div class="col-12 text-center">
			<div id="tags-search" class="d-flex flex-wrap justify-content-center mb-5">
				@foreach($tags as $tag)
			    <span 
			      class="tag bg-grey-lightest m-2 px-3 py-1 cursor-pointer"
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
			<button id="find-results" class="btn btn-primary btn-wide shadow"><i class="fas fa-lightbulb mr-3"></i>Find my pieces</button>
		</div>
	</div>
</section>