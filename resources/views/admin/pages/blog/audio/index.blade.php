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
    
    @if($audio)
    <div class="row my-3">
      <div class="col-12">
        <p class="text-center">We have {{count($audio)}} audio files</p>
      </div>
      <div class="col-12">
        <div class="d-flex flex-wrap mb-2">
          @each('components.blog.file', $audio, 'file')
        </div>
      </div>
    </div>
    @endif
  </div>
</div>

@include('admin.components.modals.topic')

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
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
<script type="text/javascript">
function showTooltip(element) {
    $(element).tooltip('show');

    setTimeout(function(){
        $(element).tooltip('hide');
    }, 1000);
}

$('[data-toggle="tooltip"]').tooltip();

var clipboard = new ClipboardJS('.clip');

clipboard.on('success', function(e) {
    showTooltip(e.trigger);
    e.clearSelection();
});
</script>
<script type="text/javascript">
$('.remove-file').on('click', function(){
  let $button = $(this);
  let url = $button.attr('data-path');

  if (! $button.hasClass('removing')) {
    $button.addClass('removing');

    $button.attr('disabled', true).find('i').removeClass('fa-trash-alt').addClass('fa-hourglass-half');

    $.ajax({
      url: url,
      method: 'DELETE'
    }).done(function() {
      $button.parent().parent().fadeOut(function() {
        $(this).remove();
      });
    }).fail(function(data) {
      alert(data.responseJSON);
    }).always(function() {
      $button.removeClass('removing');
    });
  }
});
</script>
@endsection