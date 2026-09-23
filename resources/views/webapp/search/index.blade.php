@extends('webapp.layouts.app', ['title' => 'Search results'])

@push('header')
<style type="text/css">

</style>
<script type="text/javascript">
window.page = 1;
window.loading = window.done = false;
window.filters = [];
window.searchRequestId = 0;
</script>
@endpush

@section('content')
@include('webapp.layouts.header', ['subtitle' => 'Results for <i>"' . request('search') . '"</i>'])

<section class="mb-2">
	@include('webapp.search.form')
</section>

@include('webapp.components.sorting', ['disabled' => true])

<section id="pieces-list">
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

	$(window).on('scroll', function() {
		let scrollHeight = $(document).height();
		let scrollPosition = $(window).height() + $(window).scrollTop();
		let endOfScreen = Math.floor((scrollHeight - scrollPosition) / scrollHeight) === 0;
		let notLoading = ! window.loading;

		if (endOfScreen && notLoading)
		    loadResults();
	});
});

function loadResults() {
    if (window.done || window.loading) return;
    window.loading = true;
    const requestId = ++window.searchRequestId;

    axios.get(makeUrl(), {params: {filters: window.filters}})
        .then(function(response) {
            if (requestId !== window.searchRequestId) return;
            const empty = response.data.trim() === '';
            window.done = empty || {{ auth('web')->guest() ? 'true' : 'false' }};
            if (empty) {
                $('#empty strong').text(window.page == 1 ? 'Sorry, nothing to show!' : 'We found a total of '+$('.piece-result').length+' results');
                $('#empty').show();
            } else {
                $('#pieces-list').append(response.data);
                window.page++;
            }
        })
        .catch(function(error) {
            if (requestId !== window.searchRequestId) return;
            $('#empty strong').text('Sorry, results could not be loaded. Please try again.');
            $('#empty').show();
        })
        .then(function() {
            if (requestId !== window.searchRequestId) return;
            window.loading = false;
            $('#spinner').hide();
            $('#options button, .options-columns input').enable();
        });
}
</script>

<script type="text/javascript">
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
	const url = new URL(window.location.href);
	url.searchParams.set('lazy-load', '');
	url.searchParams.set('page', window.page);
	return url.toString();
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