@extends('layouts.app', ['title' => 'PianoLIT Blog'])

@push('header')
<meta name="twitter:card" value="A blog about all piano related topics">
<meta property="og:title" content="PianoLIT Blog" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{route('posts.index')}}" />
<meta property="og:image" content="" />
<meta property="og:description" content="A blog about all piano related topics" />
@endpush

@section('content')
<section class="container mb-5">
	<div class="row">
		
		@each('components.blog.card', $posts, 'post')

	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
@endpush