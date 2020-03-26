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
  <div class="bg-lightest-primary position-relative d-flex d-apart flex-column">
    <div class="position-absolute bg-white rounded-circle d-flex flex-center cc-icon">
      <a target="_blank" href="{{route('home')}}"><img src="{{asset('images/brand/app-icon.svg')}}"></a>
    </div>

    <div class="text-center cc-hero w-100">
      <h1 class="text-white m-0">CRASHCOURSE</h1>
      <h2>Daily lessons delivered to your email</h2>
    </div>

    @include('crashcourses.card', ['hidePhoneOnOverflow' => true])
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