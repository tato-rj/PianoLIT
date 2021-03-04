@extends('layouts.app', [
	'raw' => true,
	'title' => $title ?? null,
  'shareable' => $shareable ?? null])

@push('header')
{{$header ?? null}}
@endpush

@section('content')

<div class="cc-container container-fluid p-4">
  <div class="{{empty($theme) ? 'bg-lightest-primary' : 'bg-'.$theme.'-lightest'}} position-relative d-flex d-apart flex-column">
    <div class="position-absolute bg-white rounded-circle d-flex flex-center cc-icon">
      <a target="_blank" href="{{route('home')}}"><img src="{{asset('images/brand/app-icon.svg')}}"></a>
    </div>

  {{ $slot }}

  </div>
</div>

<div class="container text-center pb-4">
  <p class="text-muted m-0"><small>MADE WITH ❤ BY LEFTLANE</small></p>
</div>

@endsection

@push('scripts')
{{$scripts ?? null}}
@endpush