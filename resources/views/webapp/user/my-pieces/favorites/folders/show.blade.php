@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')
<div class="text-center mb-3 position-relative">
	@include('webapp.components.back')
	
	<p class="text-muted mb-1"><small><i>last updated on {{$folder->updated_at->toFormattedDateString()}}</i></small></p>
	<h3 class="px-3">@fa(['icon' => 'folder-open', 'color' => 'grey']){{$folder->name}}</h3>
	@include('webapp.user.my-pieces.favorites.folders.pieces-count')
</div>

@include('webapp.components.sorting', ['disabled' => false, 'env' => 'local'])

<section id="pieces-list">
	@forelse($folder->favorites as $favorite)
		@include('webapp.components.piece', ['piece' => $favorite->piece, 'hasFullAccess' => $hasFullAccess])
	@empty
		<h5 class="text-grey text-center mt-6 mb-3"><i>This folder is emtpy</i></h5>
	@endforelse
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