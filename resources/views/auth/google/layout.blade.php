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
</head>
<body class="bg-light">
  <main class="container py-5">
    <div class="row"><div class="col-lg-5 col-md-8 col-12 mx-auto">
      <div class="bg-white rounded shadow p-4">
        <a href="/" class="d-inline-block mb-3"><img src="{{ asset('images/brand/app-icon.svg') }}" width="60" height="60" alt="PianoLIT"></a>
        @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if(session('error') || $errors->any())<div class="alert alert-danger" role="alert">{{ session('error') ?? $errors->first() }}</div>@endif
        @yield('content')
      </div>
    </div></div>
  </main>
  <script src="{{ mix('js/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
