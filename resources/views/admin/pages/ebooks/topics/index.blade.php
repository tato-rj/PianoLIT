@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'eBooks topics',
    'description' => 'Manage the eBooks topics'])
    
    <div class="row mb-3">
      <div class="col-12">
        <form method="POST" action="{{route('admin.ebooks.topics.store')}}" class="form-inline">
          @csrf
          <input type="text" name="name" placeholder="Create a new topic here" class="form-control mr-2">
          
          <button type="submit" class="btn btn-default">Save</button>
        </form>
        @include('admin.components.feedback', ['field' => 'name'])
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$topics->count()}} topics</small></p>
      </div>
      <div class="col-12">
        <div class="d-flex flex-wrap mb-2">
          @each('admin.pages.ebooks.topics.topic', $topics, 'topic')
        </div>
      </div>
    </div>

  </div>
</div>

@include('admin.components.modals.topic')

@endsection

@section('scripts')
<script type="text/javascript">
$('.topic').on('click', function (e) {
  $topic = $(this);
  name = $topic.attr('data-name');
  creator = $topic.attr('data-creator');
  edit_url = $topic.attr('data-edit-url');
  delete_url = $topic.attr('data-delete-url');

  $('#topic-modal').find('form#delete-topic').attr('action', delete_url);
  $('#topic-modal').find('form#edit-topic').attr('action', edit_url);
  $('#topic-modal').find('input#name').val(name);
  $('#topic-modal').find('#creator').text(creator);
})
</script>
@endsection