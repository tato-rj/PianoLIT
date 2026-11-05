@extends('errors::minimal')

@section('title', 'PianoLIT is temporarily unavailable')

@section('message')
<img src="{{ asset(mix('images/brand/app-icon.svg')) }}" style="border-radius: 20%;filter: grayscale(100%); opacity: 0.6; width: 60px ">
<p>PianoLIT is undergoing maintenance. Please try again later.</p>
@endsection
