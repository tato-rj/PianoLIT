@component('layouts.funnel', [
  'title' => 'PianoLIT Crash Course: ' . $crashcourse->title,
  'shareable' => [
      'keywords' => '',
      'title' => $crashcourse->title,
      'description' => $crashcourse->description,
      'thumbnail' => $crashcourse->thumbnail_image(),
      'created_at' => $crashcourse->created_at->format(DateTime::ISO8601),
      'updated_at' => $crashcourse->updated_at->format(DateTime::ISO8601)
  ]
])

@slot('header')
<link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:700&display=swap" rel="stylesheet">
@endslot

<div class="text-center cc-hero w-100">
  <h1 class="text-white m-0">CRASHCOURSE</h1>
  <h2>Daily lessons delivered to your email</h2>
</div>

@include('crashcourses.card', ['hidePhoneOnOverflow' => true])

@endcomponent
