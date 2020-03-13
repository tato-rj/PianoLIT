@extends('admin.layouts.app')

@section('head')
  <script src="https://cloud.tinymce.com/5/tinymce.min.js?apiKey=80i9j60sixlsp84wyu3rquuelix1zbkhrodmrne6znnns8j1"></script>
  <script type="text/javascript" src="{{asset('js/tinyeditor/tiny.js')}}"></script>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @return(['url' => route('admin.crashcourses.edit', $crashcourse), 'to' => 'course page'])
    <div class="row mb-4">
      <div class="col-12">
        <div class="alert alert-grey text-center">This course currently has <strong>{{$crashcourse->lessons_count}}</strong> {{str_plural('lesson', $crashcourse->lessons_count)}}.</div>
      </div>
      <div class="col-12">
        <form id="create-lesson" method="POST" action="{{route('admin.crashcourses.lessons.update', compact(['crashcourse', 'lesson']))}}" autocomplete="off">
          @method('PATCH')
          @csrf
          @input(['bag' => 'default', 'value' => $lesson->subject, 'name' => 'subject', 'placeholder' => 'Subject (will show as email subject)', 'limit' => 120])

          @tinyeditor(['bag' => 'default', 'name' => 'body', 'value' => $lesson->body])
          <div class="w-100 text-right">
            <button type="submit" id="submit-button" class="btn btn-default">Update lesson</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@endsection
