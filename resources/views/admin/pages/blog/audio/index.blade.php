@extends('admin.layouts.app')

@section('head')
<style type="text/css">
.dropzone {
  border: 4px dashed #1876f6;
  border-radius: 0;
}

.dropzone .dz-message {
  color: #6c757d;
  line-height: 3;
  font-weight: bold;
}

.dropzone .dz-preview .dz-error-message {
  background: #e3342f;
  border-radius: 0;
}

.dropzone .dz-preview .dz-error-message:after {
  border-bottom: 6px solid #e3342f;
}

.dropzone .dz-preview.dz-file-preview .dz-image {
    border-radius: 20px;
    background: linear-gradient(to bottom, #d7f3e3, #a3e4bf);
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Audio',
    'description' => 'Manage the audio used in blog posts'])
    
    <div class="row mb-3">
      <div class="col-12">
        <form action="{{route('admin.posts.audio.store')}}" class="dropzone" id="filesDropzone"></form>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>We have {{count($audio)}} audio files</small></p>
      </div>
      <div class="col-12">
        <div class="d-flex flex-wrap mb-2">
          @each('components.blog.file', $audio, 'file')
        </div>
      </div>
    </div>

  </div>
</div>

@include('admin.components.modals.topic')

@endsection

@section('scripts')

<script type="text/javascript" src="{{asset('js/vendor/dropzone.js')}}"></script>
<script type="text/javascript">
Dropzone.options.filesDropzone = {
  acceptedFiles: 'audio/*,application/.mp3',
  maxFilesize: 5,
  maxFiles: 8,
  accept: function(file, done) {
    console.log(file);
    done();
  },
  sending: function(file, xhr, formData) {
    formData.append("_token", window.app.csrfToken);
  },
  success: function(file, response) {
    console.log(response);
  },
  error: function(file, response) {
    alert(response.message);
  }
};
</script>
@endsection