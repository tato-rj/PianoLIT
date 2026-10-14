<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="no-referrer">
  @if(!$failed)<meta http-equiv="refresh" content="0;url={{ $target }}">@endif
  <title>Apple sign-in | PianoLIT</title>
  <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5"><div class="row"><div class="col-lg-5 col-md-8 col-12 mx-auto"><div class="bg-white rounded shadow p-4">
    <h3>{{ $failed ? 'Please start sign-in again' : 'Completing Apple sign-in' }}</h3>
    <p>{{ $failed ? 'This Apple sign-in attempt is invalid or expired. Return to login and try again.' : 'Returning to PianoLIT to finish signing in in this browser.' }}</p>
    <a class="btn btn-primary w-100" href="{{ $target }}">{{ $failed ? 'Back to login' : 'Continue' }}</a>
  </div></div></div></main>
</body>
</html>
