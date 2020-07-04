@extends('webapp.layouts.app')

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Explore', 'subtitle' => 'Search or explore the repertoire by moods, genres, levels and more'])

<section class="mb-4">
	@include('webapp.search.form')
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
$('#tags-search .tag').on('click', function() {
	$('#tags-search button').disable();
	$('#tags-search .tag').not(this).removeClass('btn-teal');
	$(this).toggleClass('btn-teal');
	
	let tags = $('#tags-search .tag.btn-teal').attrToArray('data-name');

  	axios.get(window.urls.searchCount, {params: {search: tags.join(' ')}})
  		.then(function(response) {
  			$('#bottom-popup-content').html(response.data)
  			$('#bottom-popup').show();
  		})
  		.catch(function(error) {
  			$('#bottom-popup').fadeOut('fast');
  		})
  		.then(function() {
  			$('#tags-search button').enable();
  		});
});
</script>

<script type="text/javascript">
let recent = getRecent();

showRecent();

$('#search-form').on('submit', function() {
	let query = $(this).find('input[name="search"]').val();
	saveRecent(query, recent);
});

$('.recent-query').on('click', function() {
	submitRecent($(this).text());
});

function getRecent() {
	let cookie = getCookie('pl_recent');

	return cookie.length ? JSON.parse(cookie) : [];
}

function showRecent() {
	if (recent.length) {
		let $recentContainer = $('#most-recent');

		for (let i=0; i< recent.length; i++) {
			$recentContainer.find('> div').append('<span class="recent-query cursor-pointer m-1 rounded-pill border border-grey px-2"><small style="line-height: 2">'+recent[i]+'</small></span>');
		}

		$recentContainer.show();
	}
}

function saveRecent(query, recent) {

	if (! recent.includes(query))
		recent.unshift(query);

	if (recent.length > 5)
		recent.pop();

	setCookie('pl_recent', JSON.stringify(recent), 60);
}

function submitRecent(recent) {
	let $form = $('#search-form');
	$form.find('input[name="search"]').val(recent);
	$form.submit();
}
</script>
@endpush