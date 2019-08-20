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
  @include('admin.components.breadcrumb', [
    'title' => 'Quizzes',
    'description' => 'Add a new quiz'])
    
      <form id="create-quiz" class="row my-3" method="POST" action="{{route('admin.quizzes.store')}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-4 col-md-6 col-12 mb-4">
          @image(['name' => 'cover_image', 'image' => asset('images/misc/placeholder-image.png'), 'empty' => true])
        </div>
        <div class="col-lg-8 col-md-6 col-12 mb-4">
          @input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'Quiz title', 'limit' => 120])
          @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Quiz description', 'limit' => 238])
        </div>

        <div class="col-12 mb-4">
          
        </div>

        <div class="col-12 mb-4">
          
        </div>

        <div class="col-12 text-right">
          <button type="submit" id="submit-button" class="btn btn-default">Create quiz</button>
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
