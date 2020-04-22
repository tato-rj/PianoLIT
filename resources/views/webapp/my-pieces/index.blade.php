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
	<button class="btn btn-wide rounded-pill list-group-item list-group-item-action active" data-toggle="list" href="#list-tutorials">TUTORIALS</button>
	<button class="btn btn-wide rounded-pill list-group-item list-group-item-action" data-toggle="list" href="#list-favorites">FAVORITES</button>
</div>
@endcomponent
    <div class="tab-content" id="nav-tabContent">
      <div class="tab-pane fade show active" id="list-tutorials" role="tabpanel" aria-labelledby="list-home-list">TUTORIALS</div>
      <div class="tab-pane fade" id="list-favorites" role="tabpanel" aria-labelledby="list-profile-list">FAVORITES</div>
    </div>
@endsection

@push('scripts')
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