@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.blog.components.topics')

<h2 class="mb-4">{{$post->title}}</h2>
<p class="text-muted blog-font">{{$post->description}}</p>
<div class="d-apart text-muted">
	<p><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
	<p><small><i class="fas fa-eye mr-2"></i>{{$post->views}}</small></p>
</div>

<figure class="figure w-100">
	<img src="{{$post->cover_image()}}" class="figure-img img-fluid rounded w-100">
	<figcaption class="figure-caption">{{$post->cover_credits}}</figcaption>
</figure>

<section id="blog-post" class="blog-font w-100">
	{!! $post->content !!}
</section>

@endsection

@push('scripts')
<script type="text/javascript">
$('#inner-subscribe').html('');
</script>
@endpush