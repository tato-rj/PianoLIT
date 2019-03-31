@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
<style type="text/css">
div.dataTables_paginate li.first a:before, div.dataTables_paginate li.previous a:before {
    top: 8.5;
}

div.dataTables_paginate li.next a:after, div.dataTables_paginate li.last a:after {
    top: 8.5px;
}

table.dataTable thead .sorting:before, table.dataTable thead .sorting_asc:before, table.dataTable thead .sorting_desc:before, table.dataTable thead .sorting_asc_disabled:before, table.dataTable thead .sorting_desc_disabled:before {
    content: none;
}

</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Subscriptions',
    'description' => 'Manage the subscription list'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div class="w-100">
        <form method="POST" action="{{route('admin.timelines.store')}}" class="d-flex">
          @csrf
          <input type="number" required name="year" placeholder="Year" min="1600" max="{{now()->year}}" class="form-control mr-2" style="max-width: 100px">
          <input type="text" required name="event" placeholder="New event here..." class="form-control flex-grow-1 mr-2">
          <button type="submit" class="btn btn-default" style="white-space: nowrap;">Create event</button>
        </form>
        @include('admin.components.feedback', ['field' => 'year'])
        @include('admin.components.feedback', ['field' => 'event'])
        </div>
        <div>
          {{-- @include('admin.components.filters', ['filters' => []]) --}}
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center mb-4">
        <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
          @foreach($centuries as $century => $array)
          <a href="{{route('admin.timelines.index', ['century' => $century])}}" class="btn btn-{{$century == request('century') ? 'default' : 'light border'}}">{{$century}}s <span class="badge badge-light">{{count($array)}}</span></a>
          @endforeach
        </div>
      </div>

      <div class="col-12">
        <table class="table table-hover" id="blog-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Year</th>
              <th class="border-0" scope="col">Event</th>
              <th class="border-0" scope="col">Creator</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($timelines as $timeline)
            <tr>
              <td>{{$timeline->year}}</td>
              <td>{{$timeline->event}}</td>
              <td class="text-muted"><i>Created by {{$timeline->creator->name}}</i></td>
              <td class="text-right">
                <a href="#" data-toggle="modal" data-target="#event-modal" class="text-muted cursor-pointer mr-2 event" data-year="{{$timeline->year}}" data-event="{{$timeline->event}}" data-edit-url="{{route('admin.timelines.update', $timeline->id)}}"><i class="far fa-edit align-middle"></i></a>
                <a href="#" data-name="{{$timeline->event}}" data-url="{{route('admin.timelines.destroy', $timeline->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'timeline'])
@include('admin.components.modals.timeline')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">

$('.delete').on('click', function (e) {
  let $post = $(this);
  let name = $post.attr('data-name');
  let url = $post.attr('data-url');

  $('#delete-modal').find('form').attr('action', url);
});

$('.event').on('click', function (e) {
  let $event = $(this);
  let year = $event.attr('data-year');
  let event = $event.attr('data-event');
  let edit_url = $event.attr('data-edit-url');

  $('#event-modal').find('form#edit-event').attr('action', edit_url);
  $('#event-modal').find('input#year').val(year);
  $('#event-modal').find('textarea#event').val(event);
})

$(document).ready( function () {
    $('#blog-table').DataTable({
    'order': [[0, 'asc']],
    });
} );
</script>
@endsection