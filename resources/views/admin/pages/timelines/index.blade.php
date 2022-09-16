@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
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
    @include('admin.components.page.title', [
      'icon' => 'list-ul', 
      'title' => 'Timeline', 
      'subtitle' => 'Manage events used in the timeline.',
      'action' => ['label' => 'Create a new event', 'modal' => 'add-modal']
    ])

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
@include('admin.pages.timelines.create')
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

  $('#edit-modal').find('form').attr('action', edit_url);
  $('#edit-modal').find('input[name="year"]').val(year);
  $('#edit-modal').find('input[name="url"]').val(url);
  $('#edit-modal').find('input[name="event"]').val(event);
  $('#edit-modal').find('select option[value="'+type+'"]').prop('selected', true);
});

(new DataTableRaw({
  table: '#timelines-table', 
  options: {pageLength: 50, order: [[0, 'asc']]}
})).create();

</script>
@endsection