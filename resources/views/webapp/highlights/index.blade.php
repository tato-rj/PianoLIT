@extends('webapp.layouts.app', ['title' => 'Highlights'])

@push('header')
<script type="text/javascript">
window.page = 1;
window.loading = window.done = false;
window.filters = [];
</script>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Highlights', 'subtitle' => 'Highlights from our database'])

@include('webapp.components.sorting', ['disabled' => true])

<section id="pieces-list" class="row">
</section>

@include('webapp.components.spinner')

<div id="empty" class="text-grey text-center pt-5 pb-4" style="display: none;">
	@fa(['icon' => 'box-open', 'mr' => 0, 'size' => 'lg'])
	<div><strong></strong></div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
	loadResults();

	// $(window).on('scroll', function() {
	// 	let scrollHeight = $(document).height();
	// 	let scrollPosition = $(window).height() + $(window).scrollTop();
	// 	let endOfScreen = Math.floor((scrollHeight - scrollPosition) / scrollHeight) === 0;
	// 	let notLoading = ! window.loading;

	// 	if (endOfScreen && notLoading)
	// 	    loadResults();
	// });
});

function loadResults() {
	window.loading = true;
	
	if (! window.done) {
		axios.get(makeUrl(), {params: {filters: window.filters}})
		.then(function(response) {
			window.loading = false;
			window.done = response.data == '';

			if (window.done) {
				$('#empty strong').text(window.page == 1 ? 'Sorry, nothing to show!' : 'We found a total of '+$('.piece-result').length+' results')
				$('#empty').show();
			} else {
				$('#pieces-list').append(response.data);
				window.page++;
			}
		})
		.catch(function(error) {
			console.log(error);
		})
		.then(function() {
			$('#spinner').hide();
			$('#options button, .options-columns input').enable();
		});
	}
};

$('#server-filter input[type="checkbox"]').change(function() {
	let filters = [];

	$('#server-filter .options-columns > div').each(function(index) {
		let arr = $(this).find('input[type="checkbox"]:checked').attrToArray('value');

		if (arr.length)
			filters.push(arr);
	});

	reset();

    applyFilters(filters);
});
</script>

<script type="text/javascript">
function makeUrl() {
	return window.location.href + '?lazy-load&page=' + window.page;
}

function reset() {
	$('#spinner').show();
	$('#options button, .options-columns input').disable();
	$('#pieces-list').empty();
	$('#empty').hide();
}

function applyFilters(filters) {
	window.page = 1;
	window.loading = window.done = false;
	window.filters = filters;

	loadResults();
}
</script>
@endpush