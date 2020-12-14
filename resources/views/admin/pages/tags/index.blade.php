@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'music', 
      'title' => 'Tags', 
      'subtitle' => 'Manage all the tags used by pieces.',
      'action' => ['label' => 'Create a new tag', 'modal' => 'add-modal']
    ])

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{\App\Tag::count()}} tags</small></p>
      </div>
      <div class="col-12">
        @foreach($types as $type => $tags)
          <label class="p-2 border-bottom w-100"><strong>{{ucfirst($type)}}</strong></label>
          <div class="d-flex flex-wrap mb-2">
          @foreach($tags as $tag)
            @include('admin.pages.tags.tag')
          @endforeach
          </div>
        @endforeach
      </div>
      <div class="col-12 mt-4 ml-2">
        <p class="text-muted"><small>Ps: Tags with a <i class="fas fa-star text-warning fa-xs"></i> are the ones showing in the tour screen on the app.</small></p>
      </div>
    </div>

  </div>
</div>

@include('admin.components.modals.tag')
@include('admin.pages.tags.create')

@endsection

@section('scripts')
<script type="text/javascript">
$('.tag').on('click', function (e) {
  $tag = $(this);
  name = $tag.attr('data-name');
  type = $tag.attr('data-type');
  creator = $tag.attr('data-creator');
  edit_url = $tag.attr('data-edit-url');
  delete_url = $tag.attr('data-delete-url');

  $('#tag-modal').find('form#delete-tag').attr('action', delete_url);
  $('#tag-modal').find('form#edit-tag').attr('action', edit_url);
  $('#tag-modal').find('input#name').val(name);
  $('#tag-modal').find('select[name="type"] option[value="'+type+'"]').prop('selected', true);
  $('#tag-modal').find('#creator').text(creator);
})
</script>
@endsection