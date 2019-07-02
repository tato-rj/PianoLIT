@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
<style type="text/css">
.gift:hover img {
  display: block !important;
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Blog',
    'description' => 'Manage the blog posts'])

    <div class="row d-none d-sm-flex">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
          <a href="{{route('admin.posts.create')}}" class="btn btn-sm btn-default">
            <i class="fas fa-plus mr-2"></i>Create a new post
          </a>
        </div>
        <div>
          @include('admin.components.filters.blog', ['filters' => []])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover w-100" id="blog-table">
          <thead>
            <tr>
              <th class="border-0 d-none d-sm-block" scope="col">Date</th>
              <th class="border-0" scope="col">Title</th>
              <th class="border-0 d-none d-sm-block" scope="col">Reading Time</th>
              <th class="border-0" scope="col">Status</th>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($posts as $post)
            <tr>
              <td class="d-none d-sm-block" style="white-space: nowrap;">{{$post->created_at->toFormattedDateString()}}</td>
              <td>{{$post->title}}
              @if($post->hasGift())
              <span class="ml-2 gift position-relative"><i class="fas fa-gift" style="color: #E92C59"></i>
                <img src="{{$post->gift_path}}" class="position-absolute t-2 shadow-sm" style="left: 20px; top: 0; display: none;" width="100">
              </span>
              @endif
              </td>
              <td class="d-none d-sm-block">{{$post->reading_time}} min</td>
              <td id="status-{{$post->slug}}" class="status-text text-{{$post->published_at ? 'success' : 'warning'}}">{{ucfirst($post->status)}}</td>
              <td class="justify-content-end d-flex">
                <a href="{{route('posts.show', $post->slug)}}" target="_blank" class="text-muted"><i class="far fa-eye align-middle"></i></a>
                <a href="{{route('admin.posts.edit', $post->slug)}}" class="text-muted mx-2 d-none d-sm-block"><i class="far fa-edit align-middle"></i></a>
                <a href="#" data-name="{{$post->title}}" data-url="{{route('admin.posts.destroy', $post->slug)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted d-none d-sm-block"><i class="far fa-trash-alt align-middle"></i></a>
              </td>
              <td class="text-right">
                @include('admin.components.toggle.blog')
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'post'])

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
  $('#blog-table').DataTable({
    'responsive': true,
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [4, 5] } ],

  });
} );

$('input.status-toggle').on('change', function() {
  let $input = $(this);
  let $label = $($input.attr('data-target'));

  $label.addClass('text-muted').removeClass('text-warning text-success');
  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      if ($input.is(':checked')) {
        $label.text('Published').toggleClass('text-muted text-success');
      } else {
        $label.text('Unpublished').toggleClass('text-muted text-warning');
      }
    }
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