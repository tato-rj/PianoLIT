@extends('webapp.layouts.app')

@push('header')
<style type="text/css">

</style>
<script type="text/javascript">
window.page = 1;
window.loading = window.done = false;
</script>
@endpush

@section('content')
@include('webapp.layouts.header', ['subtitle' => 'Results for <i>"' . request('search') . '"</i>'])

@include('webapp.components.sorting', ['disabled' => true])

<section id="pieces-list">
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
		let scrollHeight = $(document).height();
		let scrollPosition = $(window).height() + $(window).scrollTop();
		let endOfScreen = Math.floor((scrollHeight - scrollPosition) / scrollHeight) === 0;
		let notLoading = ! window.loading;

		if (endOfScreen && notLoading)
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
			$('#spinner').remove();
			$('#options button').enable();
		});
	}
};
</script>

<script type="text/javascript">

</script>
@endpush