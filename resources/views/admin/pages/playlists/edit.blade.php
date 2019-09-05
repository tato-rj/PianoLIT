@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Playlists',
    'description' => 'Edit the playlist'])
    	<div class="text-center mb-2">
    		<a href="{{route('api.playlists.show', $playlist->id)}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    	</div>
	    <div class="row mb-4">
	      <div class="col-12">
	        <div class="bg-light rounded px-4 py-3">
	        	<form method="POST" action="{{route('admin.playlists.update', $playlist->id)}}">
	        		@csrf
	        		@method('PATCH')
	        		<div class="row mb-2">
	        			<div class="col-5">
	        				<label class="text-muted">Basic information</label>
	        				<input type="text" name="name" placeholder="Name" class="form-control mb-2" value="{{$playlist->name}}" required>
	        				<input type="text" name="subtitle" placeholder="Subtitle" class="form-control mb-2" value="{{$playlist->subtitle}}" required>
	        				<select name="group" class="form-control mb-2" required>
	        					<option selected disabled>Select the group</option>
	        					<option value="journey" {{ $playlist->group == 'journey' ? 'selected' : ''}}>Journey</option>
	        				</select>
	        				<textarea name="description" placeholder="Description" class="form-control" rows="6" maxlength="255" required>{{$playlist->description}}</textarea>
	        			</div>
	        			<div class="col-7">
	        				<label class="text-muted">Pieces</label>
	        				<div id="playlist-pieces" class=""> 
	        					@foreach($playlist->pieces as $piece)
	        					@include('admin.pages.playlists.piece', ['is_model' => false])
	        					@endforeach
	        				</div>
	        			</div>
	        		</div>
					<div class="row">
	        			<div class="col-12 text-right">
	        				<button type="submit" class="btn btn-sm btn-default">Update playlist</button>
	        			</div>
					</div>
	        	</form>
	        </div>
	      </div>
	    </div>
	    <div class="row mb-4">
	    	<div class="col-12">
	    		<div class="border-bottom mb-3">
	    			<label><strong>Select pieces here</strong></label>
	    		</div>
		        <table class="table table-hover" id="pieces-table" style="display: none;">
		          <thead>
		            <tr>
		              <th class="border-0" scope="col">Piece</th>
		              <th class="border-0" scope="col">Composer</th>
		              <th class="border-0" scope="col">Level</th>
		              <th class="border-0" scope="col"></th>
		            </tr>
		          </thead>
		          <tbody>
		            @foreach($pieces as $piece)
		            @include('admin.pages.playlists.row', ['is_model' => true])
		            @endforeach
		          </tbody>
		        </table>
	    	</div>
	    </div>
	</div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
{{-- <script src="{{asset('js/sortable.js')}}"></script> --}}
<script type="text/javascript">
$(document).on('click', '.selected-piece .remove', function(event) {
	event.preventDefault();
	$(this).parent('.edit-control').remove();
});

$('.add-piece').on('click', function(event) {
	event.preventDefault();
	let $container = $('#playlist-pieces');
	let $piece = $(this).find('.selected-piece').clone();
	let id = $(this).attr('data-id');

	if ($container.has('input[value="'+id+'"]').length) {
		alert('This pieces is already in this playlist');
	} else {
		$piece.attr('name', 'pieces[]');
		$container.append($piece);
	}
});

</script>
<script type="text/javascript">
$('#playlist-pieces').sortable({
handle: '.sort-handle',
update: function() {

}
});
</script>
<script type="text/javascript">
$(document).ready( function () {
  $('#pieces-table').DataTable({
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [3] } ],
  });

  $('#pieces-table').fadeIn();
});
</script>
@endsection
