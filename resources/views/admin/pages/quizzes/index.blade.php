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
    'title' => 'Quiz',
    'description' => 'Manage the quizzes'])

    <div class="row d-none d-sm-flex">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
          <a href="{{route('admin.quizzes.create')}}" class="btn btn-sm btn-default">
            <i class="fas fa-plus mr-2"></i>Create a new quiz
          </a>
        </div>
        <div>
          {{-- @include('admin.components.filters.blog', ['filters' => []]) --}}
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover w-100" id="quiz-table">
          <thead>
            <tr>
              <th class="border-0 d-none d-sm-block" scope="col">Date</th>
              <th class="border-0" scope="col">Title</th>
              <th class="border-0 d-none d-sm-block" scope="col">Number of questions</th>
              <th class="border-0" scope="col">Status</th>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($quizzes as $quiz)
            <tr>
              <td class="d-none d-sm-block" style="white-space: nowrap;">{{$quiz->created_at->toFormattedDateString()}}</td>
              <td>{{$quiz->title}}</td>
              <td class="d-none d-sm-block">{{count($quiz->questions)}} questions</td>
              <td id="status-{{$quiz->slug}}" class="status-text text-{{$quiz->published_at ? 'success' : 'warning'}}">{{ucfirst($quiz->status)}}</td>
              <td class="justify-content-end d-flex">
                <a href="{{route('quizzes.show', $quiz->slug)}}" target="_blank" class="text-muted"><i class="far fa-eye align-middle"></i></a>
                <a href="{{route('admin.quizzes.edit', $quiz->slug)}}" class="text-muted mx-2 d-none d-sm-block"><i class="far fa-edit align-middle"></i></a>
                <a href="#" data-name="{{$quiz->title}}" data-url="{{route('admin.quizzes.destroy', $quiz->slug)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted d-none d-sm-block"><i class="far fa-trash-alt align-middle"></i></a>
              </td>
              <td class="text-right">
                @include('admin.components.toggle.quiz')
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'quiz'])

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
  $('#quiz-table').DataTable({
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
  $quiz = $(this);
  name = $quiz.attr('data-name');
  url = $quiz.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
});

</script>
@endsection