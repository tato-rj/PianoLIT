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

<section id="pieces-list">
</section>

@include('webapp.components.spinner')

<div id="empty" class="search-empty text-center" role="status" style="display: none;">
	<img class="search-empty__image" src="{{ asset('images/webapp/empty-search.png') }}" alt="" width="960" height="576">
	<h2 class="search-empty__title">No results found</h2>
	<p class="search-empty__message" data-empty-message></p>
	<p class="search-empty__hint text-muted">Try a different keyword, or explore our library to discover new pieces.</p>
	<div class="search-empty__actions">
		<a href="{{ route('webapp.search.results', ['catalogue' => 1]) }}" class="btn btn-secondary">
			@icon('search') Clear search
		</a>
		<div class="search-empty__destinations">
			<a href="{{ route('webapp.explore') }}" class="btn btn-secondary text-primary">
				@icon('music') Explore our library
			</a>
			<a href="{{ route('webapp.playlists') }}" class="btn btn-secondary text-primary">
				@icon('layers') Go to Collections
			</a>
		</div>
	</div>
</div>

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
            if (empty) {
                if (window.page == 1) {
                    $('#empty [data-empty-message]').text(query ? 'We couldn’t find any pieces matching “' + query + '”.' : 'We couldn’t find any pieces matching your filters.');
                    $('#empty').show();
                } else {
                    $('#search-feedback strong').text('We found a total of ' + $('.piece-result').length + ' results');
                    $('#search-feedback').show();
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
	url.searchParams.set('page', window.page);
	return url.toString();
}

function reset() {
	$('#spinner').show();
	$('#pieces-list').empty();
	$('#empty').hide();
	$('#search-feedback').hide();
}

function applyFilters(filters) {
	window.page = 1;
	window.loading = window.done = false;
	window.filters = filters;

	loadResults();
}
</script>
@endpush
