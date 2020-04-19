@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
	    @return(['url' => route('admin.playlists.index'), 'to' => 'view all playlists'])

	    <div class="row mb-4">
	      <div class="col-12">
			@include('admin.pages.playlists.edit.form')
	      </div>
	    </div>

	    <div class="row mb-4">
	    	<div class="col-12">
	    		<div class="border-bottom mb-3">
	    			<label><strong>Select pieces here</strong></label>
	    		</div>

   				@datatable(['table' => 'pieces', 'rows' => 'admin.pages.playlists.edit.row', 'columns' => ['Piece', 'Composer', 'Level', 'Ranking', '']])

	    	</div>
	    </div>
	</div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).on('click', '.selected-piece .remove', function(event) {
	event.preventDefault();
	$(this).parent('.edit-control').remove();
});

$(document).on('click', '.add-piece', function(event) {
	event.preventDefault();
	let $container = $('#playlist-pieces');
	let $piece = $(this).find('.selected-piece').clone();
	let id = $(this).attr('data-id');

	if ($container.has('input[value="'+id+'"]').length) {
		alert('This pieces is already in this playlist');
	} else {
		console.log($piece);
		$piece.attr('name', 'pieces[]');
		$container.append($piece);
	}
});

</script>
<script type="text/javascript">
$('#playlist-pieces').sortable({handle: '.sort-handle'});
</script>
<script type="text/javascript">
(new DataTable('#pieces-table')).columns([
  {data: 'name', name: 'pieces.name', class: 'dataTables_main_column'},
  {data: 'composer.short_name', name: 'composer.name', class: 'text-nowrap'},
  {data: 'level', name: 'tags.name', orderable: false},
  {data: 'ranking', name: 'tags.name', orderable: false},
  {data: 'actions', orderable: false, searchable: false},
]).dontSort().create();
</script>
@endsection
