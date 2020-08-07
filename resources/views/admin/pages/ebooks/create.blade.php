@extends('admin.layouts.app')

@section('head')
  <script src="https://cloud.tinymce.com/5/tinymce.min.js?apiKey=80i9j60sixlsp84wyu3rquuelix1zbkhrodmrne6znnns8j1"></script>
  <script type="text/javascript" src="{{asset('js/tinyeditor/tiny.js')}}"></script>
<style type="text/css">
.image-container canvas { width: 100% !important; }
.tox {border-radius: 0.25rem !important}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">    
      <form id="create-ebook" class="row my-3" method="POST" action="{{route('admin.ebooks.store')}}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <div class="col-12">
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
        <div class="col-12">
          @input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'eBook title', 'limit' => 120])
          @textarea(['bag' => 'default', 'name' => 'subtitle', 'placeholder' => 'eBook subtitle', 'limit' => 238])
          @input(['bag' => 'default', 'type' => 'number', 'name' => 'pages_count', 'placeholder' => 'Number of pages', 'limit' => 400])
        </div>
        <div class="col">
          @input(['bag' => 'default', 'type' => 'number', 'name' => 'price', 'placeholder' => 'eBook price', 'limit' => 200])
          @input(['bag' => 'default', 'type' => 'number', 'name' => 'discount', 'placeholder' => '% discount', 'limit' => 100, 'step' => 5, 'required' => false])
        </div>
        <div class="col">
          <div class="form-group">
            <div class="custom-file">
              <input type="file" required class="custom-file-input {{$errors->has('cover_image') ? 'is-invalid' : ''}}" name="cover_image" id="cover-file">
              <label class="custom-file-label truncate" for="cover-file">Cover image</label>
            </div>
          </div>

        </div>

        <div class="col">
          <div class="form-group">
            <div class="custom-file">
              <input type="file" required class="custom-file-input {{$errors->has('pdf_file') ? 'is-invalid' : ''}}" name="pdf_file" id="pdf-file">
              <label class="custom-file-label truncate" for="pdf-file">PDF file</label>
            </div>
          </div>
          <div class="form-group">
            <div class="custom-file">
              <input type="file" required class="custom-file-input {{$errors->has('epub_file') ? 'is-invalid' : ''}}" name="epub_file" id="epub-file">
              <label class="custom-file-label truncate" for="epub-file">EPUB file</label>
            </div>
          </div>
        </div>

        <div class="col-12 mb-4">
          <div class="rounded bg-light px-3 py-2 mb-3">
            <p class="text-brand mb-2"><strong>DESCRIPTION</strong></p>
            @tinyeditor(['bag' => 'default', 'name' => 'description'])
          </div>
        </div>

        <div class="col-12 text-right">
          <button type="submit" id="submit-button" class="btn btn-default">Create eBook</button>
        </div>
      </form>

  </div>
</div>

@endsection

@section('scripts')
@endsection
