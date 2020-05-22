@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.list-group {
	max-width: 320px;
	margin: 0 auto;
	background-color: #f1f2f2;
}

.list-group .btn {
	z-index: 1;
}

.list-group .btn:not(.active) {
	background: transparent;
	border-color: transparent; 
}

.list-group .btn.active {
	color: #fff !important;
	background-color: transparent;
	border-color: transparent;
}

.clone {
	position: absolute;
	bottom: 0;
	left: 0;
	z-index: 0 !important;
	background-color: #6c757d !important;
	border-color: #6c757d !important;
}
/*
.list-group .btn.active {
	color: #fff !important;
	background-color: #6c757d;
	border-color: #6c757d;
}*/
</style>
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'My Pieces', 'subtitle' => 'Quickly access your favorites or see your tutorial requests'])
<div class="list-group flex-row rounded-pill position-relative">
	<button class="btn btn-wide rounded-pill list-group-item list-group-item-action active" data-anchor="tutorials" data-toggle="list" href="#list-tutorials">TUTORIALS</button>
	<button class="btn btn-wide rounded-pill list-group-item list-group-item-action" data-anchor="favorites" data-toggle="list" href="#list-favorites">FAVORITES</button>
</div>
@endcomponent
    <div class="tab-content" id="nav-tabContent">
      <div class="tab-pane fade show active" id="list-tutorials" role="tabpanel" aria-labelledby="list-home-list">
		@include('webapp.user.my-pieces.tutorial-requests')
      </div>
      <div class="tab-pane fade" id="list-favorites" role="tabpanel" aria-labelledby="list-profile-list">
		@include('webapp.user.my-pieces.favorites')
      </div>
    </div>
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

<script type="text/javascript">
jQuery.fn.match = function(element) {
	let pos = element.index() * 160;
	return this.css({
		width: element.outerWidth(), 
		height: element.outerHeight(),
		transform: 'translateX('+pos+'px)'
	}).empty();
};

let $active = $('.list-group button.active');
let $clone = $active.clone().removeClass('list-group-item list-group-item-action').addClass('clone t-2').match($active);

$active.parent().append($clone);

$('button[data-toggle="list"]').on('show.bs.tab', function (e) {
	$clone.match($(e.target));
   // newly activated tab
  e.relatedTarget // previous active tab
});
</script>
@endpush