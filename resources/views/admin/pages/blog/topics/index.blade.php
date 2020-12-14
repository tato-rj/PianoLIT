@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">    
    @include('admin.components.page.title', [
      'icon' => 'newspaper', 
      'title' => 'Blog Topics', 
      'subtitle' => 'Manage the topics used by blog posts.',
      'action' => ['label' => 'Create a new topic', 'modal' => 'add-modal']
    ])

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$topics->count()}} topics</small></p>
      </div>
      <div class="col-12">
        <div class="d-flex flex-wrap mb-2">
          @each('admin.pages.blog.topics.topic', $topics, 'topic')
        </div>
      </div>
    </div>

  </div>
</div>

@include('admin.components.modals.topic')
@include('admin.pages.blog.topics.create')

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