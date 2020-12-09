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
    
      <form id="create-quiz" class="row my-3" method="POST" action="{{route('admin.quizzes.store')}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-4 col-md-6 col-12 mb-4">
          @image(['name' => 'cover_image', 'image' => asset('images/misc/placeholder-image.png'), 'empty' => true])
          
          <div class="form-group">
            <select class="form-control required {{$errors->has('level') ? 'is-invalid' : ''}}" name="level_id" >
              <option class="default" selected disabled>Level</option>
              @foreach($levels as $level)
              <option value="{{$level->id}}" {{ old('level') == $level->id ? 'selected' : ''}}>{{ucfirst($level->name)}}</option>
              @endforeach
            </select>
          </div>

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
        </div>
        <div class="col-lg-8 col-md-6 col-12 mb-4">
          @input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'Quiz title', 'limit' => 120])
          @textarea(['bag' => 'default', 'name' => 'description', 'placeholder' => 'Quiz description', 'limit' => 238])

          @component('admin.pages.quizzes.question.layout')
            @if(old('questions'))
              @php($questions = array_values(old('questions')))
              @foreach($questions as $question)
              @php($array = $questions[$loop->index])
              @include('admin.pages.quizzes.question.input', [
                'names' => ["questions[{$loop->index}][0]", 
                            "questions[{$loop->index}][1]", 
                            "questions[{$loop->index}][2]", 
                            "questions[{$loop->index}][3]", 
                            "questions[{$loop->index}][4]"],
                'question' => array_shift($array),
                'answers' => $array])
              @endforeach
            @endif
          @endcomponent
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
function updateCounters() {
  $counters = $('.question-counter:visible');

  $counters.each(function(index) {
    $(this).text('- question ' + (index + 1) + ' of ' + $counters.length + ' -');
  });
}

updateCounters();

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

  updateCounters();
});

/////////////////////
// REMOVE QUESTION //
/////////////////////
$(document).on('click', 'a.remove-field', function() {
  $(this).parent().remove();
  
  updateCounters();
});
</script>
@endsection
