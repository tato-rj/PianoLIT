@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')
<div class="text-center mb-3">
	@if($playlist->cover_image)
	<img src="{{$playlist->cover_image}}" style="width: 180px" class="rounded mb-4">
	@endif
	<h3>{{$playlist->name}}</h3>
	<p>{{$playlist->description}}</p>
</div>

@include('webapp.components.sorting', ['disabled' => false, 'env' => 'local'])

<section id="pieces-list">
@foreach($playlist->pieces()->has('tutorials')->get() as $piece)
	@include('webapp.components.piece', compact('hasFullAccess'))
@endforeach
</section>

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