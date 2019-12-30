@extends('admin.layouts.app')

@section('head')
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.4.3/cropper.min.css">
  <script src="https://cloud.tinymce.com/5/tinymce.min.js?apiKey=80i9j60sixlsp84wyu3rquuelix1zbkhrodmrne6znnns8j1"></script>
  <script type="text/javascript" src="{{asset('js/tinyeditor/tiny.js')}}"></script>
<style type="text/css">
.image-container canvas { width: 100% !important; }
.tox {border-radius: 0.25rem !important}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Blog',
    'description' => 'Edit the post'])
  
      <form class="row my-3" method="POST" action="{{route('admin.posts.update', $post->slug)}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="col-12">
          <div class="rounded bg-light px-3 py-2 mb-3">
            <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
            <div class="d-flex flex-wrap">
                @foreach($topics as $topic)
                <div class="custom-control custom-checkbox mx-2 mb-2">
                  <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}" {{($post->topics->contains($topic->id)) ? 'checked' : ''}}>
                  <label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
                </div>
                @endforeach
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12 mb-4">
          @image(['name' => null, 'image' => $post->cover_image(), 'empty' => true])
        </div>
        <div class="col-lg-8 col-md-6 col-12 mb-4">
          @input(['bag' => 'default', 'value' => $post->title, 'name' => 'title', 'placeholder' => 'Post title', 'limit' => 120])
          @textarea(['bag' => 'default', 'value' => $post->description, 'name' => 'description', 'placeholder' => 'Post description', 'limit' => 238])
          @input(['bag' => 'default', 'value' => $post->cover_credits, 'name' => 'cover_credits', 'placeholder' => 'Cover image credits', 'limit' => 120, 'required' => false])
          @input(['bag' => 'default', 'value' => $post->gift_path, 'name' => 'gift_path', 'placeholder' => 'Gift path here', 'limit' => 180, 'required' => false])
        </div>
        <div class="col-12 mb-4">
          @tinyeditor(['bag' => 'default', 'name' => 'content', 'value' => $post->content])
        </div>

        <div class="col-12 mb-4"> 
          @component('admin.pages.blog.post.references.layout')
            @if($post->referencesArray)
              @foreach($post->referencesArray as $reference)
              @include('admin.pages.blog.post.references.input', [
                'link' => $reference,
                'name' => 'references['.$loop->index.']'])
              @endforeach
            @endif
          @endcomponent
        </div>

        <div class="col-12 text-right">
          <div class="d-flex justify-content-end">
            <a href="{{route('posts.show', $post->slug)}}" target="_blank" class="btn btn-outline-dark mr-2">
              @if($post->published_at)
              <i class="fas fa-globe mr-2"></i>Visit
              @else
              <i class="far fa-eye mr-2"></i>Preview
              @endif
            </a>
            <button type="submit" id="submit-button" class="btn btn-default">Update post</button>
          </div>
          @include('admin.components.creator', ['model' => $post, 'type' => 'post'])
        </div>
      </form>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.4.3/cropper.min.js"></script>

<script type="text/javascript">
(new SimpleCropper({
  imageInput: 'input#image-input',
  uploadButton: '#upload-button',
  confirmButton: '#confirm-button',
  cancelButton: '#cancel-button',
  submitButton: 'button[type="submit"]'
})).create();
</script>

<script type="text/javascript">
$('#image-input').on('change', function() {
  $(this).attr('name', 'cover_image');
});
</script>
<script type="text/javascript">
/////////////////
// ADD NEW TIP //
/////////////////
$('a.add-new-field').on('click', function() {
  $button = $(this);
  $type = $button.attr('data-type');
  $clone = $button.siblings('.original-type').clone();

  number = $('.reference-form:not(.original-type)').length;
  input = $clone.find('input');
  $(input).attr('name',  'references['+number+']');
  $clone.removeClass('original-type').insertBefore($button).show();

});

////////////////
// REMOVE TIP //
////////////////
$(document).on('click', 'a.remove-field', function() {
  $(this).parent().remove();
});
</script>
@endsection
