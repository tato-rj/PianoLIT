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
    'description' => 'Manage the playlists'])
    <div class="text-center mb-2">
      <a href="{{route('api.playlists.all', 'journey')}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    </div>
    <div class="row mb-3">
      <div class="col-12">
        <div class="bg-light rounded px-4 py-3">
          <label class="text-muted">Create a new playlist</label>
          <form method="POST" action="{{route('admin.playlists.store')}}">
            @csrf
            <div class="form-row mb-2">
              <div class="col">
                <input type="text" name="name" placeholder="Name" class="form-control mb-2" value="{{old('name')}}" required>
                <input type="text" name="subtitle" placeholder="Subtitle" class="form-control mb-2" value="{{old('subtitle')}}" required>
                <select name="group" class="form-control">
                  <option selected disabled>Select the group</option>
                  <option value="journey" {{ old('group') == 'journey' ? 'selected' : ''}}>Journey</option>
                </select>
              </div>
              <div class="col">
                <textarea name="description" placeholder="Description" class="form-control h-100" maxlength="255" required>{{old('description')}}</textarea>
              </div>
            </div>
            <div class="w-100 text-right">
              <button type="submit" class="btn btn-default">Create</button>
            </div>
          </form>
          @include('admin.components.feedback', ['field' => 'name'])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover w-100" id="playlist-table">
          <thead>
            <tr>
              <th class="border-0 d-none d-sm-block" scope="col">Date</th>
              <th class="border-0" scope="col">Name</th>
              <th class="border-0" scope="col">Group</th>
              <th class="border-0" scope="col">Number of pieces</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($playlists as $playlist)
            <tr>
              <td class="d-none d-sm-block" style="white-space: nowrap;">{{$playlist->created_at->toFormattedDateString()}}</td>
              <td>{{$playlist->name}}</td>
              <td>{{slug_str($playlist->group)}}</td>
              <td>{{$playlist->pieces_count}} pieces</td>
              <td class="justify-content-end d-flex">
                <a href="{{route('admin.playlists.edit', $playlist->id)}}" class="text-muted mx-2 d-none d-sm-block"><i class="far fa-edit align-middle"></i></a>
                <a href="#" data-name="{{$playlist->name}}" data-url="{{route('admin.playlists.destroy', $playlist->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted d-none d-sm-block"><i class="far fa-trash-alt align-middle"></i></a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </div>

  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'playlist'])
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
  $('#playlist-table').DataTable({
    'responsive': true,
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [4, 5] } ],
  });
});

$('.delete').on('click', function (e) {
  $post = $(this);
  name = $post.attr('data-name');
  url = $post.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
});
</script>
@endsection