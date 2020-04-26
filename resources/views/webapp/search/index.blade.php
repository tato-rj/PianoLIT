@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.options-columns > div:not(:last-child) {
	margin-right: 1.5rem;
	padding-right: 1.5rem;
	border-right: 1px solid lightgrey;
}

.options-columns > div:last-child {
	padding-right: 2rem;
}
</style>
<script type="text/javascript">
window.page = 1;
window.loading = window.done = false;
</script>
@endpush

@section('content')
@include('webapp.layouts.header', ['subtitle' => 'Results for <i>"' . request('search') . '"</i>'])
<div class="mb-2 d-flex justify-content-end" style="display: none;" id="options">
	@button(['disabled' => true, 'label' => '<i class="fas fa-sort mr-1"></i> Sort by', 'attr' => 'data-target=#sort-container', 'size' => 'sm', 'theme' => 'outline-secondary', 'classes' => 'mr-2'])
	@button(['disabled' => true, 'label' => '<i class="fas fa-filter mr-1"></i> Filter by', 'attr' => 'data-target=#filters-container', 'size' => 'sm', 'theme' => 'outline-secondary'])
</div>

<div>
	@include('webapp.search.options.sort.index')
	@include('webapp.search.options.filters.index')
</div>

<section id="search-results">
	<div id="spinner" class="text-center text-grey pt-5">
		<p><strong>Loading results...</strong></p>
		<div class="spinner-border" role="status">
			<span class="sr-only">Loading...</span>
		</div>
	</div>
</section>
<div id="empty" class="text-grey text-center pt-5 pb-4" style="display: none;">
	@fa(['icon' => 'box-open', 'mr' => 0, 'size' => 'lg'])
	<div><strong></strong></div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
	loadResults();

	$(window).on('scroll', function() {
		var scrollHeight = $(document).height();
		var scrollPosition = $(window).height() + $(window).scrollTop();

		if (Math.floor((scrollHeight - scrollPosition) / scrollHeight) === 0 && ! window.loading)
		    loadResults();
	});
});

function loadResults() {
	window.loading = true;

	if (! window.done) {
		axios.get(window.location.href + '&lazy-load&page=' + window.page)
		.then(function(response) {
			window.loading = false;
			window.done = response.data == '';

			if (window.done) {
				console.log(window.page);
				$('#empty strong').text(window.page == 1 ? 'Sorry, nothing to show!' : 'We found a total of '+$('.piece-result').length+' results')
				$('#empty').show();
			} else {
				$('#search-results').append(response.data);
				window.page++;
			}
		})
		.catch(function(error) {
			console.log(error);
		})
		.then(function() {
			$('#spinner').remove();
			$('#options button').enable();
		});
	}
};
</script>

<script type="text/javascript">
$('#options button').click(function() {
	$($(this).attr('data-target')).fadeToggle('fast').siblings().hide();
});
</script>

<script type="text/javascript">
$('#sort-container input[type="radio"]').click(function() {
	sort($(this).data('filter'), $(this).val());
});

function sort(filter, direction = 'asc')
{
    let results = $('.piece-result');

    results.sort(function(a, b){ 
    	let first = $(a).data("sort-" + filter);
    	let second = $(b).data("sort-" + filter);

    	return direction == 'asc' ? first - second : second - first;
    });

    $("#search-results").html(results);
}
</script>
@endpush