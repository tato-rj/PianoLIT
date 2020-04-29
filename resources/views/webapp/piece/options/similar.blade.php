@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<div class="text-center">
	<p class="mb-1">We found {{$similar->count()}} {{str_plural('piece', $similar->count())}} similar to</p>
	<h5 class="m-0">{{$piece->medium_name}}</h5>
	<p class="text-muted">by {{$piece->composer->name}}</p>
</div>

@include('webapp.components.sorting', ['disabled' => false, 'env' => 'local'])

<section id="pieces-list">
@each('webapp.components.piece', $similar, 'piece')
</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">
$('#local-filter input[type="checkbox"]').change(function() {
	let filters = [];

	$('#local-filter .options-columns > div').each(function(index) {
		let arr = $(this).find('input[type="checkbox"]:checked').attrToArray('value');

		if (arr.length)
			filters.push(arr);
	});

	reset();

	if (filters.length)
	    applyFilters(filters);
});

function reset() {
	$('.piece-result').show();
}

function applyFilters(filters) {
	$('.piece-result').hide();

	$('.piece-result').each(function() {
		let tags = $(this).data('tags');
		let valid = 0;

		for (i=0; i < filters.length; i++) {
			if (filters[i].some(tags.includes.bind(tags)))
				valid += 1;
		}

		if (valid == filters.length) $(this).show();
	});
}
</script>
@endpush