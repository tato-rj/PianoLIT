@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.input-icon {
	position: relative;
}

.input-icon i:first-of-type {
	position: absolute;
	left: 12px;
	bottom: 16px;
}

.input-icon i:last-of-type {
	position: absolute;
	right: 10px;
	bottom: 16px;
}

.input-icon input {
	padding-left: 44px !important;
	padding-right: 44px !important;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Explore', 'subtitle' => 'Search or explore the repertoire by moods, genres, levels and more'])

<section class="mb-4">
	<form method="GET" action="{{route('webapp.search.results', ['lazy-load'])}}">
	  <div class="input-icon">
	    @fa(['icon' => 'search', 'color' => 'grey', 'size' => 'lg'])
	    <input type="text" name="search" class="form-control border-bottom p-4 rounded-0 bg-transparent border-grey w-100" style="border: 0;" placeholder="Search here...">
	    @fa(['icon' => 'algolia', 'fa_type' => 'b', 'color' => 'grey', 'size' => 'lg'])
	  </div>
	</form>
</section>

<section id="tags-search">
@foreach($categories as $title => $category)
	<div class="mb-3">
		<h5>{{ucfirst($title)}}</h5>
		<div class="d-flex flex-wrap">
		@foreach($category as $tag)
		    <button data-name="{{$tag->name}}" data-id="{{$tag->id}}" class="tag btn btn-light badge-pill m-2 px-3 py-1 text-nowrap">
		      {{$tag->name}}
		    </button>
		@endforeach
		</div>
	</div>
@endforeach
</section>

@endsection

@push('scripts')
<script type="text/javascript">
function showBottomPopup(html) {
	$('#bottom-popup > div').css('bottom', $('#bottom-popup').siblings('.row').outerHeight() + 14);
	$('#bottom-popup-content').html(html);
	$('#bottom-popup').show();
}
</script>
<script type="text/javascript">
$('#tags-search .tag').on('click', function() {
	$('#tags-search button').disable();
	$('#tags-search .tag').not(this).removeClass('btn-teal');
	$(this).toggleClass('btn-teal');
	
	let tags = $('#tags-search .tag.btn-teal').attrToArray('data-name');

  	axios.get(window.urls.searchCount, {params: {search: tags.join(' ')}})
  		.then(function(response) {
  			showBottomPopup(response.data);
  		})
  		.catch(function(error) {
  			$('#bottom-popup').fadeOut('fast');
  		})
  		.then(function() {
  			$('#tags-search button').enable();
  		});
});
</script>
@endpush