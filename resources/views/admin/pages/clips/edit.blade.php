@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'theme' => 'edit',
      'title' => 'Edit clip', 
      'subtitle' => 'Use this page to edit this clip.', 
      'back' => ['view all clips' => route('admin.clips.index')]
    ])

    <div class="row">
      <div class="col-lg-6 col-md-6 col-8 mx-auto">
        <form method="POST" action="{{route('admin.clips.update', $clip)}}">
          @csrf
          @method('PATCH')
          
          @input(['bag' => 'default', 'value' => $clip->name, 'name' => 'name', 'placeholder' => 'Name', 'limit' => 120])
          @input(['bag' => 'default', 'value' => $clip->url, 'name' => 'url', 'placeholder' => 'URL', 'limit' => 220])

          <div class="text-center mt-5">
            <button type="submit" class="btn btn-block btn-default">Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@endsection
