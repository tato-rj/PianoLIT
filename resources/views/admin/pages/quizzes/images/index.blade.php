@extends('admin.layouts.app')

@section('head')
<style type="text/css">

</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    
    <div class="row mb-3">
      <div class="col-12">
        <form action="{{route('admin.quizzes.media.store', 'images')}}" class="dropzone" id="filesDropzone"></form>
      </div>
    </div>
    
    
    <div class="row my-3">
{{--       <div class="col-12">
        <p>We have {{count($files)}} {{str_plural('image', count($files))}}</p>
      </div> --}}
      <div class="col-12">
        @foreach($files as $date => $group)
          <div class="mb-3">
            <p class="text-center mb-0"><small>Images uploaded on {{carbon($date)->toFormattedDateString()}}</small></p>
            <div class="d-flex" style="overflow-x: scroll;">
              @each('admin.pages.quizzes.images.image', $group, 'file')
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
<script type="text/javascript" src="{{asset('js/vendor/dropzone.js')}}"></script>
<script type="text/javascript">
Dropzone.options.filesDropzone = {
  acceptedFiles: 'image/*,application/.jpg,.jpeg',
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
    alert('Great, the file was uploaded!');
    console.log(response);
  },
  error: function(file, response, request) {
    if (request) {
      alert(response.message);
    } else {  
      alert(response);
    }
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

    $button.attr('disabled', true).text('removing');

    $.ajax({
      url: url,
      method: 'DELETE'
    }).done(function() {
      $button.parent().parent().parent().parent().fadeOut(function() {
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