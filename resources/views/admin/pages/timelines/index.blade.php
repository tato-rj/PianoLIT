@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
<style type="text/css">
.input-group-vertical .input-group:first-child {
    padding-bottom: 0;
}
.input-group-vertical .input-group:first-child * {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;    
}
.input-group-vertical .input-group:last-child {
    padding-top: 0;
}
.input-group-vertical .input-group:last-child * {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}
.input-group-vertical .input-group:not(:last-child):not(:first-child) {
    padding-top: 0;
    padding-bottom: 0;
}
.input-group-vertical .input-group:not(:last-child):not(:first-child) * {
    border-radius: 0;
}  
.input-group-vertical .input-group:not(:first-child) * {
    border-top: 0;
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
      @include('admin.pages.timelines.create')
      </div>
    </div>

    <div class="row">
      <div class="col-12 text-center mb-4">
        <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
          @foreach($centuries as $century => $array)
          <a href="{{route('admin.timelines.index', ['century' => $century])}}" class="btn btn-{{$century == request('century') ? 'default' : 'light border'}}">{{$century}}s <span class="badge badge-light">{{count($array)}}</span></a>
          @endforeach
        </div>
      </div>
    </div>

    @datatableRaw(['model' => 'timelines', 'columns' => ['Year', 'Type', 'Event', 'Creator', '']])

  </div>
</div>

@include('admin.pages.timelines.edit')
@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$('.event').on('click', function (e) {
  let $event = $(this);
  let year = $event.attr('data-year');
  let event = $event.attr('data-event');
  let url = $event.attr('data-url');
  let type = $event.attr('data-type');
  let edit_url = $event.attr('data-edit-url');

  $('#event-modal').find('form#edit-event').attr('action', edit_url);
  $('#event-modal').find('input#year').val(year);
  $('#event-modal').find('input#url').val(url);
  $('#event-modal').find('textarea#event').val(event);
  $('#event-modal').find('select#type option[value="'+type+'"]').prop('selected', true);
});

(new DataTableRaw({
  table: '#timelines-table', 
  options: {pageLength: 50, order: [[0, 'asc']]}
})).create();

</script>
@endsection