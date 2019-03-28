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
		
		@each('components.blog.cards.large', $posts, 'post')

	</div>
</section>

@include('components.popups.subscribe-1')
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>

<script type="text/javascript">
$(function() {
    setTimeout(function() {
        $("#subscribe-overlay").fadeIn('fast');
    }, 2000);
});
</script>
@endpush