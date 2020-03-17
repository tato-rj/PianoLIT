@extends('layouts.app', [
	'raw' => true,
	'title' => 'PianoLIT Crash Course: ' . $crashcourse->title,
    'shareable' => [
      'keywords' => '',
      'title' => $crashcourse->title,
      'description' => $crashcourse->description,
      'thumbnail' => $crashcourse->thumbnail_image(),
      'created_at' => $crashcourse->created_at->format(DateTime::ISO8601),
      'updated_at' => $crashcourse->updated_at->format(DateTime::ISO8601)
	]])

@push('header')
<link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:700&display=swap" rel="stylesheet">
<style type="text/css">

</style>
@endpush

@section('content')

<div class="cc-container container-fluid p-4">
  <div class="position-relative d-flex d-apart flex-column">
    <div class="position-absolute bg-white rounded-circle d-flex flex-center cc-icon">
      <a target="_blank" href="{{route('home')}}"><img src="{{asset('images/brand/app-icon.svg')}}"></a>
    </div>

    <div class="text-center cc-hero w-100">
      <h1 class="text-white m-0">CRASHCOURSE</h1>
      <h2>Daily lessons delivered to your email</h2>
    </div>

    <div class="mx-auto position-relative cc-card">
      <div class="position-absolute cc-phone">
        <img src="{{asset('images/mockup/crashcourse.png')}}" class="w-100">
      </div>
      <div class="bg-white pt-6 pr-6 pb-4 rounded-top cc-body">
        <div class="mb-4">
          <p class="text-warning text-uppercase"><strong>...by signing up to this {{$crashcourse->lessons_count}}-day course you'll learn about:</strong></p>
          <h4>{{$crashcourse->title}}</h4>
          <p class="m-0 text-muted"><i class="fas fa-envelope-open-text mr-2"></i>This course has {{$crashcourse->lessons_count}} {{ str_plural('lesson', $crashcourse->lessons_count) }}</p>
          {{-- <p class="m-0 text-muted">{{$crashcourse->description}}</p> --}}
        </div>

        <form method="POST" action="{{route('crashcourses.signup', $crashcourse)}}" class="cc-form">
          @csrf
          <input type="hidden" name="origin_url" value="{{url()->current()}}">
            <div class="form-row">
              <div class="col-lg-6 col-md-6 col-sm-6 col-12"> 
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'first_name', 'placeholder' => 'First name', 'limit' => 120])
              </div>
              <div class="col-lg-6 col-md-6 col-sm-6 col-12"> 
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'email', 'placeholder' => 'Your email', 'limit' => 120])
              </div>
            </div>
            <div class="my-2">
              <button type="submit" class="btn btn-primary btn-sm-block shadow btn-wide mb-2"><strong>START FREE COURSE!</strong></button>
              <div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="container text-center pb-4">
  <p class="text-muted m-0"><small>MADE WITH ❤ BY LEFTLANE</small></p>
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">

</script>
@endpush