@extends('webapp.layouts.app', ['title' => 'Search results'])

@push('header')
<script type="text/javascript">
window.page = 1;
window.loading = window.done = false;
window.filters = @json(request('filters', []));
window.searchRequestId = 0;
</script>
@endpush

@section('content')
@include('webapp.layouts.header', ['subtitle' => 'Results for <i>"' . request('search') . '"</i>'])

<section class="mb-2 mt-4">
	@include('webapp.search.form')
</section>

<p id="search-results-count" class="text-muted small mb-2" role="status" style="display: none;"></p>

<section id="pieces-list">
</section>

@include('webapp.components.spinner')

@include('webapp.search.empty')

<div id="search-feedback" class="text-grey text-center pt-5 pb-4" role="status" style="display: none;">
	<strong></strong>
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
    const query = new URL(window.location.href).searchParams.get('search') || '';

    axios.get(makeUrl(), {params: {filters: window.filters}})
        .then(function(response) {
            if (requestId !== window.searchRequestId) return;
            const empty = response.data.trim() === '';
            window.done = empty || {{ auth('web')->guest() ? 'true' : 'false' }};
            $('#search-feedback').hide();
            const total = response.headers && response.headers['x-search-total'];
            if (total !== undefined && /^\d+$/.test(String(total))) showResultCount(Number(total));
            if (empty) {
                if (window.page == 1) {
                    $('#empty [data-empty-message]').text(query ? 'We couldn’t find any pieces matching “' + query + '”.' : 'We couldn’t find any pieces matching your filters.');
                    $('#empty').show();
                } else {
                    if (!$('#search-results-count').text()) showResultCount($('.piece-result').length);
                }
            } else {
                $('#pieces-list').append(response.data);
                window.page++;
            }
        })
        .catch(function(error) {
            if (requestId !== window.searchRequestId) return;
            $('#empty').hide();
            $('#search-feedback strong').text('Sorry, results could not be loaded. Please try again.');
            $('#search-feedback').show();
        })
        .then(function() {
            if (requestId !== window.searchRequestId) return;
            window.loading = false;
            $('#spinner').hide();
        });
}
</script>

<script type="text/javascript">
function makeUrl() {
	const url = new URL(window.searchControlsUrl ? window.searchControlsUrl(window.location.href) : window.location.href);
	url.searchParams.set('lazy-load', '');
	url.searchParams.set('include_total', '1');
	url.searchParams.set('page', window.page);
	return url.toString();
}

function showResultCount(total) {
	$('#search-results-count').text(total.toLocaleString() + (total === 1 ? ' result' : ' results')).show();
}

function reset() {
	$('#spinner').show();
	$('#pieces-list').empty();
	$('#empty').hide();
	$('#search-feedback').hide();
	$('#search-results-count').text('').hide();
}

function applyFilters(filters) {
	window.page = 1;
	window.loading = window.done = false;
	window.filters = filters;

	loadResults();
}
</script>
@endpush
