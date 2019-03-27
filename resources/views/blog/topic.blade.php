@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@push('header')
<meta name="twitter:card" value="A blog about all piano related topics">
<meta property="og:title" content="PianoLIT Blog" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{route('posts.index')}}" />
<meta property="og:image" content="" />
<meta property="og:description" content="A blog about all piano related topics" />

  <meta name="twitter:card" content="article">
  <meta name="twitter:site" content="@pianolit">
  <meta name="twitter:description" content="A blog about all piano related topics.">
  <meta name="twitter:app:country" content="US">
  <meta name="twitter:app:name:iphone" content="PianoLIT">
  <meta name="twitter:app:id:iphone" content="00000000">
  <!-- END Twitter Card -->
  <meta name="apple-itunes-app" content="app-id=00000000" />
@endpush

@section('content')
<section class="container mb-5">
	<div class="row">
    <div class="col-12 border-bottom mb-4 pb-4">
      <p class="text-muted mb-1"><small>POSTS ABOUT</small></p>
      <h2>{{ucfirst($topic->name)}}</h2>
    </div>
		<div class="col-lg-3 col-md-3 col-12">
      <p><strong>Other topics</strong></p>
      <div class="d-flex flex-wrap">
        @each('components.blog.topic', $topics, 'topic')
      </div>
    </div>
    <div class="col-lg-9 col-md-9 col-12">
      @each('components.blog.cards.horizontal', $posts, 'post')
    </div>

	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
@endpush