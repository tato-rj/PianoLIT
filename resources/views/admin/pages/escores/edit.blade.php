@extends('admin.layouts.app')

@section('head')
  <script src="https://cloud.tinymce.com/5/tinymce.min.js?apiKey=80i9j60sixlsp84wyu3rquuelix1zbkhrodmrne6znnns8j1"></script>
  <script type="text/javascript" src="{{asset('js/tinyeditor/tiny.js')}}"></script>
<style type="text/css">
.image-container canvas { width: 100% !important; }
.tox {border-radius: 0.25rem !important}
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
    @include('admin.components.page.title', [
      'theme' => 'edit',
      'title' => $escore->title, 
      'subtitle' => 'Use this page to edit this eScore.', 
      'back' => ['view all eScores' => route('admin.escores.index')]
    ])

    <div class="row">
      <div class="col-12 mb-3">
        <form action="{{route('admin.escores.previews.upload', $escore)}}" class="dropzone" id="filesDropzone"></form>
      </div>
      <div class="col-12 d-flex flex-wrap">
        @foreach($escore->previews as $preview)
        <a href="#" data-url="{{route('admin.escores.previews.remove', ['escore' => $escore, 'preview_path' => $preview])}}" title="Delete" data-toggle="modal" data-target="#delete-modal" class="delete">
          <div class="position-relative m-1" style="width: 80px">
            <img src="{{storage($preview)}}" class="w-100 border">
            <div class="absolute-center d-flex flex-center w-100 h-100 show-on-hover" style="background-color: rgba(0,0,0,0.4)">
              <h1 class="text-white m-0"><strong>{{$loop->iteration}}</strong></h1>
            </div>
          </div>
        </a>
        @endforeach
      </div>
    </div>

    <form id="update-ebook" class="row my-3" method="POST" action="{{route('admin.escores.update', $escore)}}" autocomplete="off" enctype="multipart/form-data">
      @csrf
      @method('PATCH')
      <div class="col-12">
        <div class="rounded bg-light px-3 py-2 mb-3">
          <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
          <div class="d-flex flex-wrap">

              @foreach($topics as $topic)
              <div class="custom-control custom-checkbox mx-2 mb-2">
                <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}" {{($escore->topics->contains($topic->id)) ? 'checked' : ''}}>
                <label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
              </div>
              @endforeach

          </div>
        </div>
      </div>
      <div class="col-12">
        @input(['bag' => 'default', 'label' => 'Title', 'value' => $escore->title, 'name' => 'title', 'placeholder' => 'eScore title', 'limit' => 120])
        @input(['bag' => 'default', 'label' => 'Composer(s)', 'value' => $escore->author, 'name' => 'author', 'placeholder' => 'eScore composer(s)', 'limit' => 120, 'required' => false])
        @textarea(['bag' => 'default', 'label' => 'Short description', 'value' => $escore->subtitle, 'name' => 'subtitle', 'placeholder' => 'eScore subtitle', 'limit' => 238])
      </div>
      <div class="col-4"> 
        @input(['bag' => 'default', 'label' => 'Number of pages', 'value' => $escore->pages_count, 'type' => 'number', 'name' => 'pages_count', 'placeholder' => 'Number of pages', 'limit' => 400])
      </div>
      <div class="col-4">
        @input(['bag' => 'default', 'label' => 'Price in USD', 'value' => $escore->price, 'type' => 'number', 'name' => 'price', 'placeholder' => 'eScore price', 'limit' => 200])
      </div>
      <div class="col-4">
        @input(['bag' => 'default', 'label' => 'Discount', 'value' => $escore->discount, 'type' => 'number', 'name' => 'discount', 'placeholder' => '% discount', 'limit' => 100, 'step' => 5, 'required' => false])
      </div>
      <div class="col">
        @file(['bag' => 'default', 'name' => 'cover_image', 'value' => $escore->cover_path, 'label' => 'Cover image', 'required' => false])
      </div>
      <div class="col">
        @file(['bag' => 'default', 'name' => 'mockup_image', 'value' => $escore->mockup_path, 'label' => 'Mockup image', 'required' => false])
      </div>
      <div class="col">
        @file(['bag' => 'default', 'name' => 'pdf_file', 'value' => $escore->pdf_path, 'label' => 'Pdf file', 'required' => false])
      </div>
      <div class="col">
        @file(['bag' => 'default', 'name' => 'audio_file', 'value' => $escore->audio_path, 'label' => 'Audio file', 'required' => false])
      </div>

      <div class="col-12 mb-4">
        <div class="rounded bg-light px-3 py-2 mb-3">
          <p class="text-brand mb-2"><strong>DESCRIPTION</strong></p>
          @tinyeditor(['bag' => 'default', 'name' => 'description', 'value' => $escore->description])
        </div>
      </div>

      <div class="col-12 text-right">
        <button type="submit" id="submit-button" class="btn btn-default">Update eScore</button>
      </div>
    </form>
    @include('admin.pages.reviews.fake', ['product' => $escore])

  </div>
</div>

@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="{{asset('js/vendor/dropzone.js')}}"></script>
<script type="text/javascript">
Dropzone.options.filesDropzone = {
  acceptedFiles: 'image/*,application/.jpg,.jpeg',
  maxFilesize: 5,
  maxFiles: 8,
  paramName: 'preview_image',
  accept: function(file, done) {
    console.log(file);
    done();
  },
  sending: function(file, xhr, formData) {
    formData.append("_token", window.app.csrfToken);
  },
  success: function(file, response) {
    alert('Great, the file was uploaded!');
    console.log(response);
  },
  error: function(file, response, request) {
    console.log(file);
    console.log(response);
    console.log(request);
    if (request) {
      alert(response.message);
    } else {  
      alert(response);
    }
  }
};
</script>
@endsection
