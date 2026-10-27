<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="referrer" content="{{ $referrerPolicy ?? 'no-referrer' }}">
  <title>{{ $title ?? 'Sign in' }} | PianoLIT</title>
  @include('layouts.html.google.fonts')
  <link href="{{ mix('css/app.css') }}" rel="stylesheet">
  @include('layouts.html.js-app')
    @include('layouts.html.theme')
</head>
@php($publicHeader = ! in_array(request()->getHost(), ['my.'.config('app.short_url'), 'admin.'.config('app.short_url')], true))
<body class="bg-white{{ $publicHeader ? ' public-site d-flex flex-column min-vh-100' : '' }}">
  @if($publicHeader)
    @include('components.layout.public.header')
  @endif
  <main class="container py-5{{ $publicHeader ? ' flex-grow-1' : '' }}">
    <div class="row"><div class="col-lg-5 col-md-8 col-12 mx-auto">
      <div class="bg-white rounded shadow p-4">
        @unless($publicHeader)
          <a href="/" class="d-inline-block mb-3"><img src="{{ asset(mix('images/brand/app-icon.svg')) }}" width="60" height="60" alt="PianoLIT"></a>
        @endunless
        @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if(session('error') || $errors->any())<div class="alert alert-danger" role="alert">{{ session('error') ?? $errors->first() }}</div>@endif
        @yield('content')
      </div>
    </div></div>
  </main>
  @if($publicHeader)
    @include('components.layout.public.footer')
  @endif
  <script src="{{ mix('js/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
