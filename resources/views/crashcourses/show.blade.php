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
p {letter-spacing: 1px;}
</style>
@endpush

@section('content')

<div class="container-fluid p-4">
  <div class="position-relative d-flex d-apart flex-column" style="min-height: 100vh; background-color: rgba(222,237,249,0.8)">
    <div class="position-absolute bg-white rounded-circle d-flex flex-center" style="width: 140px; height: 140px; right: 140px; top: -55px;">
      <img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 52px; margin-top: 32px;">
    </div>

    <div class="text-center" style="padding-top: 90px; padding-bottom: 32px; margin-bottom: 60px">
      <h1 style="font-family: 'Roboto Condensed', sans-serif; font-size: 163px; letter-spacing: 4px" class="text-white m-0">CRASHCOURSE</h1>
      <h2 style="margin-top: -96px;">Daily lessons delivered to your email</h2>
    </div>

    <div class="mx-auto position-relative" style="max-width: 72%; padding: 0 182px; overflow-y: hidden;">
      <div class="position-absolute" style="max-width: 312px; transform: rotate(-8deg); left: 40px; top: 32px">
        <img src="{{asset('images/mockup/crashcourse.png')}}" class="w-100">
      </div>
      <div class="bg-white pt-6 pr-6 pb-4" style="padding-left: 160px;">
        <div class="mb-4">
          <p class="text-warning text-uppercase" style="opacity: 0.9; font-size: 90%; letter-spacing: 1.4px"><strong>...by signing up to this {{$crashcourse->lessons_count}}-day course you'll learn about:</strong></p>
          <h4 style="letter-spacing: 1px">{{$crashcourse->title}}</h4>
          {{-- <p class="m-0 text-muted">{{$crashcourse->description}}</p> --}}
        </div>

        <form method="POST" action="{{route('crashcourses.signup', $crashcourse)}}">
          @csrf
          <input type="hidden" name="origin_url" value="{{url()->current()}}">
            <div class="form-row">
              <div class="col"> 
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'first_name', 'placeholder' => 'First name', 'limit' => 120])
              </div>
              <div class="col"> 
                @input(['styles' => 'border: none', 'classes' => 'border-dark border-bottom rounded-0 bg-transparent','bag' => 'default', 'name' => 'email', 'placeholder' => 'Your email', 'limit' => 120])
              </div>
            </div>
            <div class="">
              <button type="submit" class="btn btn-primary shadow btn-wide mb-2"><strong>START FREE COURSE!</strong></button>
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