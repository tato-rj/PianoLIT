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
    'description' => 'Add a new post'])
    
      <form class="row my-3" method="POST" action="{{route('admin.posts.store')}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-4 col-md-6 col-12 mb-4">
          @image(['name' => 'cover_image', 'image' => asset('images/misc/placeholder-image.png'), 'empty' => true])
        </div>
        <div class="col-lg-8 col-md-6 col-12 mb-4">
          @input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'Post title', 'limit' => 120])
          @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Post description', 'limit' => 238])
          @input(['bag' => 'default', 'name' => 'reading_time', 'placeholder' => 'Reading time', 'type' => 'number'])
          @input(['bag' => 'default', 'name' => 'cover_credits', 'placeholder' => 'Cover image credits', 'limit' => 120, 'required' => false])
        </div>
        <div class="col-12 mb-4">
          @tinyeditor(['bag' => 'default', 'name' => 'content'])
        </div>
        <div class="col-12 text-right">
          <button type="submit" id="submit-button" class="btn btn-default">Create post</button>
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
@endsection
