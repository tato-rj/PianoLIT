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
    'title' => 'Quiz',
    'description' => 'Edit the quiz'])
    
    <div class="d-flex justify-content-end">
      <div class="text-{{$quiz->published_at ? 'success' : 'warning'}} mr-3 status-text">{{ucfirst($quiz->status)}}</div>
      @include('admin.components.toggle.quiz')
    </div>

      <form class="row my-3" method="POST" action="{{route('admin.quizzes.update', $quiz->slug)}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="col-lg-4 col-md-6 col-12 mb-4">
          @image(['name' => null, 'image' => $quiz->cover_image(), 'empty' => true])

          <div class="form-group">
            <select class="form-control required {{$errors->has('level') ? 'is-invalid' : ''}}" name="level_id" >
              <option class="default" selected disabled>Level</option>
              @foreach($levels as $level)
              <option value="{{$level->id}}" {{ $quiz->level_id == $level->id ? 'selected' : ''}}>{{ucfirst($level->name)}}</option>
              @endforeach
            </select>
          </div>

          <div class="rounded bg-light px-3 py-2 mb-3">
            <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
            <div class="d-flex flex-wrap">
                @foreach($topics as $topic)
                <div class="custom-control custom-checkbox mx-2 mb-2">
                  <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}" {{($quiz->topics->contains($topic->id)) ? 'checked' : ''}}>
                  <label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
                </div>
                @endforeach
            </div>
          </div>
        </div>
        <div class="col-lg-8 col-md-6 col-12 mb-4">
          @input(['bag' => 'default', 'value' => $quiz->title, 'name' => 'title', 'placeholder' => 'Quiz title', 'limit' => 120])
          @textarea(['bag' => 'default', 'value' => $quiz->description, 'name' => 'description', 'placeholder' => 'Quiz description', 'limit' => 238])
          @component('admin.pages.quizzes.question.layout')
            @foreach($quiz->questions as $question)
            @include('admin.pages.quizzes.question.input', [
              'names' => ["questions[{$loop->index}][0]", 
                          "questions[{$loop->index}][1]", 
                          "questions[{$loop->index}][2]", 
                          "questions[{$loop->index}][3]", 
                          "questions[{$loop->index}][4]"],
              'question' => $question['Q'],
              'answers' => $question['A']])
            @endforeach
          @endcomponent
        </div>

        <div class="col-12 text-right">
          <div class="d-flex justify-content-end">
            <a href="{{route('quizzes.show', $quiz->slug)}}" target="_blank" class="btn btn-outline-dark mr-2">
              @if($quiz->published_at)
              <i class="fas fa-globe mr-2"></i>Visit
              @else
              <i class="far fa-eye mr-2"></i>Preview
              @endif
            </a>
            <button type="submit" id="submit-button" class="btn btn-default">Update quiz</button>
          </div>
          @include('admin.components.creator', ['model' => $quiz, 'type' => 'quiz'])
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

$('input.status-toggle').on('change', function() {
  let $input = $(this);
  let $label = $('.status-text');

  $label.addClass('text-muted').removeClass('text-warning text-success');
  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      if ($input.is(':checked')) {
        $label.text('Published').toggleClass('text-muted text-success');
      } else {
        $label.text('Unpublished').toggleClass('text-muted text-warning');
      }
    }
  });
});
</script>
<script type="text/javascript">
//////////////////////
// ADD NEW QUESTION //
//////////////////////
$('a.add-new-field').on('click', function() {
  $button = $(this);
  $type = $button.attr('data-type');
  $clone = $button.siblings('.original-type').clone();

  number = $('.quiz-form:not(.original-type)').length;

  $clone.find('input').each(function(index) {
    $(this).attr('name', 'questions['+number+']['+index+']')
  });

  $clone.removeClass('original-type').insertBefore($button).show();

});

/////////////////////
// REMOVE QUESTION //
/////////////////////
$(document).on('click', 'a.remove-field', function() {
  $(this).parent().remove();
});
</script>
@endsection
