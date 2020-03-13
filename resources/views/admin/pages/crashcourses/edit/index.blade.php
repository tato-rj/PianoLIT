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
    @return(['url' => route('admin.crashcourses.index'), 'to' => 'view all courses'])
    <div class="row">
      <div class="col-12">
        @php($subscribers_count = count($crashcourse->activeSubscriptions))
        <div class="alert alert-{{$subscribers_count == 0 ? 'green' : 'yellow'}} text-center">
          This course has {{$subscribers_count}} active {{str_plural('subscriber', $subscribers_count)}}
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-12 mb-4">
        <form method="POST" action="{{route('admin.crashcourses.update', $crashcourse)}}" autocomplete="off" enctype="multipart/form-data">
          @csrf
          @method('PATCH')
            @image(['name' => null, 'image' => $crashcourse->cover_image(), 'empty' => true])
            <div class="rounded bg-light px-3 py-2 mb-3">
              <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
              <div class="d-flex flex-wrap">
                  @foreach($topics as $topic)
                  <div class="custom-control custom-checkbox mx-2 mb-2">
                    <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}" {{($crashcourse->topics->contains($topic->id)) ? 'checked' : ''}}>
                    <label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
                  </div>
                  @endforeach
              </div>
            </div>

            @input(['bag' => 'default', 'value' => $crashcourse->title, 'name' => 'title', 'placeholder' => 'Crash Course title', 'limit' => 120])
            @textarea(['bag' => 'default', 'value' => $crashcourse->description, 'name' => 'description', 'placeholder' => 'Crash Course description', 'limit' => 238])

            <div>
              <button type="submit" id="submit-button" class="btn btn-default">Update course</button>
              @include('admin.components.creator', ['model' => $crashcourse, 'type' => 'course'])
            </div>
        </form>
      </div>
      <div class="col-lg-8 col-md-6 col-12 mb-4">
        <div class="alert bg-light mb-4 text-center text-brand position-relative">
          <strong>COURSE LESSONS</strong>
          <a href="{{route('admin.crashcourses.lessons.create', $crashcourse)}}" class="btn btn-sm btn-default position-absolute" style="top: 50%; right: 12px; transform: translateY(-50%);">
            <i class="fas fa-plus mr-2"></i><strong>NEW</strong>
          </a>
        </div>
        <div id="course-lessons" data-url-reorder="{{route('admin.crashcourses.lessons.reorder', $crashcourse)}}">
          @forelse($crashcourse->lessons as $lesson)
          
            @include('admin.pages.crashcourses.edit.lesson-card')
          
          @empty
          <h2 class="text-center mt-6 text-grey"><strong>No lessons yet...</strong></h2>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

@include('admin.components.modals.delete')
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
$('#course-lessons').sortable({
  handle: '.sort-handle',
  update: function(element) {
    let url = $('#course-lessons').attr('data-url-reorder');
    let ids = $('#course-lessons > .ordered').attrToArray('data-id');
    $.ajax({
      url: url,
      type: 'PATCH',
      data: {ids: ids}
    })
    .done(function(response) {
      alert('The lessons have been re-ordered!');
    })
    .fail(function(response) {
      alert('Something went wrong...');
    });
  }
});
</script>
@endsection
