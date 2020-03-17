@extends('layouts.app', [
	'raw' => true,
	'title' => 'PianoLIT Crash Courses'
])

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')
<div class="h-100vh d-flex flex-column">
  <div style="height: 84vh">
    <video class="w-100 h-100" style="background-color: black" controls>
      <source src="{{$video}}" type="video/mp4">
      Your browser does not support the video tag.
    </video>
  </div>
  <div class="bg-light h-100 w-100 d-flex flex-center flex-wrap">
      <div class="hide-on-sm">
        <a target="_blank" href="{{route('home')}}"><img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 50px"></a>
      </div>
      <div class="mx-4 hide-on-sm">
        <p class="m-0" style="font-size: 86%">CHECKOUT THE <strong>PIANOLIT</strong> APP ON THE APP STORE</p>
      </div>
      <div>
        <a href="{{config('app.stores.ios')}}" target="_blank" class="btn btn-default shadow m-0" style="padding: .425rem 1rem;">
          <h6 class="m-0" style="margin: 0; line-height: 1.25; font-size: .95rem;">Download PianoLIT app</h6>
        </a>
      </div>
  </div>
</div>
@endsection