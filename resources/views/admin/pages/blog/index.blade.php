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
    'title' => 'Blog',
    'description' => 'Manage the blog posts'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <a href="{{route('admin.posts.create')}}" class="btn btn-sm btn-default">
            <i class="fas fa-plus mr-2"></i>Create a new post
          </a>
        </div>
        <div>
          @include('admin.components.filters', ['filters' => []])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="blog-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Date</th>
              <th class="border-0" scope="col">Title</th>
              <th class="border-0" scope="col">Reading Time</th>
              <th class="border-0" scope="col">Status</th>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($posts as $post)
            <tr>
              <td>{{$post->created_at->toFormattedDateString()}}</td>
              <td>{{$post->title}}</td>
              <td>{{$post->reading_time}} min</td>
              <td class="status-text text-{{$post->is_published ? 'success' : 'warning'}}">{{ucfirst($post->status)}}</td>
              <td class="text-right">
                <a href="{{route('posts.show', $post->slug)}}" target="_blank" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
                <a href="{{route('admin.posts.edit', $post->slug)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
                <a href="#" data-name="{{$post->title}}" data-url="{{route('admin.posts.destroy', $post->slug)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
              </td>
              <td class="text-right">
                @include('components.blog.toggle')
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
$('input.status-toggle').on('change', function() {
  let $input = $(this);
  let $label = $('.status-text');

  $label.text('Just a sec...').addClass('text-muted').removeClass('text-warning text-success');
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

$(document).ready( function () {
    $('#blog-table').DataTable({
    'order': [[0, 'desc']],
    });
} );
</script>
@endsection