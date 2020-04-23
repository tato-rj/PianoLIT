@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header', ['subtitle' => 'Results for <i>"' . request('search') . '"</i>'])

<section id="search-results" data-query="{{request('search')}}">
	<div id="spinner" class="text-center text-grey" style="padding-top: 8em">
		<p><strong>Loading results...</strong></p>
		<div class="spinner-border" role="status">
			<span class="sr-only">Loading...</span>
		</div>
	</div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
	axios.get(window.location.href)
	.then(function(response) {
		$('#search-results').append(response.data);
		console.log('Showing results');
	})
	.catch(function(error) {
		console.log(error);
	})
	.then(function() {
		$('#spinner').remove();
	});
});
</script>
@endpush