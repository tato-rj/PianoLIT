@extends('admin.layouts.app')

@section('head')
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.4.3/cropper.min.css">
<style type="text/css">
.image-container canvas { width: 100% !important; }
.tox {border-radius: 0.25rem !important}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    
      <form id="create-quiz" class="row my-3" method="POST" action="{{route('admin.crashcourses.store')}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-4 col-md-6 col-12 mb-4">
          @image(['name' => 'cover_image', 'image' => asset('images/misc/placeholder-image.png'), 'empty' => true])
        </div>
        <div class="col-lg-8 col-md-6 col-12 mb-4">
          <div class="rounded bg-light px-3 py-2 mb-3">
            <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
            <div class="d-flex flex-wrap">
                @foreach($topics as $topic)
                <div class="custom-control custom-checkbox mx-2 mb-2">
                  <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}">
                  <label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
                </div>
                @endforeach
            </div>
          </div>
          @input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'Course title', 'limit' => 120])
          @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Course description', 'limit' => 238])
        </div>
        <div class="col-12 text-right">
          <button type="submit" id="submit-button" class="btn btn-default">Save and continue</button>
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
