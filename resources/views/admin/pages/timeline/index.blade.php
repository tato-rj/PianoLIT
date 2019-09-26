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
  @include('admin.components.breadcrumb', [
    'title' => 'Subscriptions',
    'description' => 'Manage the subscription list'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div class="w-100">
          <form method="POST" action="{{route('admin.timelines.store')}}">
            @csrf
            <div class="form-row mb-2">
              <div class="col-2 input-group-vertical">
                <div class="input-group">
                  <input type="number" required name="year" placeholder="Year" min="1600" max="{{now()->year}}" class="form-control">
                </div>
                <div class="input-group">
                  <select name="type" class="form-control">
                    <option selected disabled>Type</option>
                    <option value="history">History</option>
                    <option value="music">Music</option>
                  </select>
                </div>
              </div>
              <div class="col-10 input-group-vertical">
                  <div class="input-group">
                    <input type="text" required name="event" placeholder="New event here..." class="form-control">
                  </div>
                  <div class="input-group">
                    <input type="text" required name="url" placeholder="Url address" class="form-control">
                  </div>
              </div>
            </div>
            <div class="form-group text-right">
              <button type="submit" class="btn btn-default" style="white-space: nowrap;">Create event</button>
            </div>
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
              <th class="border-0" scope="col">Type</th>
              <th class="border-0" scope="col">Event</th>
              <th class="border-0" scope="col">Creator</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($timelines as $timeline)
            <tr>
              <td>{{$timeline->year}}</td>
              <td>{{ucfirst($timeline->type)}}</td>
              <td>
                @if($timeline->url)
                <a href="{{$timeline->url}}" target="_blank" class="link-blue"><i class="fas fa-globe"></i></a>
                @endif
                {{$timeline->event}}
              </td>
              <td class="text-muted" style="white-space: nowrap;"><i><small>Created by {{$timeline->creator->name}}</small></i></td>
              <td class="text-right" style="white-space: nowrap;">
                <a href="#" 
                  data-toggle="modal" 
                  data-target="#event-modal" 
                  data-type="{{$timeline->type}}" 
                  data-year="{{$timeline->year}}" 
                  data-event="{{$timeline->event}}" 
                  data-url="{{$timeline->url}}" 
                  data-edit-url="{{route('admin.timelines.update', $timeline->id)}}" 
                  class="text-muted cursor-pointer mr-2 event"><i class="far fa-edit align-middle"></i></a>
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
  let url = $event.attr('data-url');
  let type = $event.attr('data-type');
  let edit_url = $event.attr('data-edit-url');

  $('#event-modal').find('form#edit-event').attr('action', edit_url);
  $('#event-modal').find('input#year').val(year);
  $('#event-modal').find('input#url').val(url);
  $('#event-modal').find('textarea#event').val(event);
  $('#event-modal').find('select#type option[value="'+type+'"]').prop('selected', true);
})

$(document).ready( function () {
    $('#blog-table').DataTable({
    'order': [[0, 'asc']],
    });
} );
</script>
@endsection