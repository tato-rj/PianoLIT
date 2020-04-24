@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Explore', 'subtitle' => 'Search or explore the repertoire by moods, genres, levels and more'])
	<button class="btn btn-wide rounded-pill btn-outline-secondary">@fa(['icon' => 'algolia', 'fa_type' => 'b'])SEARCH HERE</button>
@endcomponent

<section id="tags-search" data-url="{{route('webapp.search.count', ['count'])}}">
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

  	axios.get($('#tags-search').attr('data-url'), {params: {search: tags.join(' ')}})
  		.then(function(response) {
  			showBottomPopup(response.data);
  		})
  		.catch(function(error) {
  			$('#bottom-popup').fadeOut('fast');
  		})
  		.then(function() {
  			$('#tags-search button').enable();
  		});

  // $('#tags-search form').submit();

  // axios.get($container.attr('data-url'), {params: {
  //   'ids': $('#tags-search .btn-teal').attrToArray('data-id'), 
  //   'names': $('#tags-search .btn-teal').attrToArray('data-name')
  // }}).then(function(response) {
  //     $container.html(response.data.view); 
  //     $container.parent().removeClass('opacity-4');
  //     $label.text(response.data.label);
  //   })
  //   .catch(function(error) {
  //     console.log(error);
  //   })
  //   .then(function() {
  //     $btn.enable();
  //   });
});
</script>
@endpush