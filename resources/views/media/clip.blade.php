@extends('layouts.app', [
	'raw' => true,
	'title' => 'PianoLIT Clips'
])

@push('header')

@endpush

@section('content')
<div class="h-100vh d-flex flex-column cc-video">
  <div class="player" style="height: 84vh">
    <video id="clip" class="w-100 h-100" style="background-color: black" controls>
      <source src="{{$url}}" type="video/mp4">
      Your browser does not support the video tag.
    </video>
  </div>
  <div class="bg-light h-100 w-100">
    <div class="d-flex flex-center flex-wrap credits" style="height: 100%">
      <div>
        <a target="_blank" href="{{route('home')}}"><img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 50px"></a>
      </div>
      <div class="mx-4 text-center my-3">
        <p class="m-0" style="font-size: 86%">CHECKOUT THE <strong>PIANOLIT</strong> APP ON THE APP STORE</p>
      </div>
      <div>
        <a href="{{config('app.stores.ios')}}" target="_blank" class="btn btn-outline-dark rounded-0 m-0" style="padding: .425rem 1rem;">
          Download PianoLIT app
        </a>
      </div>
    </div>
  </div>
</div>
@endsection
