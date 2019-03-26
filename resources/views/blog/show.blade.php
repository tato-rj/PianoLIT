@extends('layouts.app')

@push('header')
<meta name="twitter:card" value="{{$post->description}}">
<meta property="og:title" content="{{$post->title}}" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{route('posts.show', $post->slug)}}" />
<meta property="og:image" content="{{$post->cover_image()}}" />
<meta property="og:description" content="{{$post->description}}" />
@endpush

@section('content')
<section class="container mb-5">
	<div class="row border-bottom pb-5 mb-5">
		<div class="col-8 mx-auto">
			@if(! empty($preview))
			<div class="alert alert-warning" role="alert">
			  <i class="fas fa-exclamation-triangle mr-2"></i>This post is <u>not published</u>. Only admins can see this page.
			</div>
			@endif
			<div class="mb-4">
				<h1 class="font-serif">{{$post->title}}</h1>
				<p class="text-muted font-serif font-lg">{{$post->description}}</p>
				<p class="text-muted"><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
				<figure class="figure">
					<img src="{{$post->cover_image()}}" class="figure-img img-fluid rounded">
					<figcaption class="figure-caption">{{$post->cover_credits}}</figcaption>
				</figure>
			</div>
			<div class="font-serif font-lg">
				{!! $post->content !!}
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-4">
			{{-- @include('components.blog.card') --}}
		</div>
	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
@endpush