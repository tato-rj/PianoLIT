@extends('layouts.app', ['title' => $post->title . ' | PianoLIT Blog'])

@push('header')
<meta name="twitter:card" value="{{$post->description}}">
<meta property="og:site_name" content="PianoLIT Blog" />
<meta property="og:title" content="{{$post->title}}" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{route('posts.show', $post->slug)}}" />
<meta property="og:image" content="{{$post->thumbnail_image()}}" />
<meta property="og:image:width" content="400" />
<meta property="og:image:height" content="225" />
<meta property="og:description" content="{{$post->description}}" />
<meta property="article:published_time" content="{{$post->created_at->format(DateTime::ISO8601)}}">
<meta property="article:modified_time" content="{{$post->updated_at->format(DateTime::ISO8601)}}">
<meta property="og:updated_time" content="{{$post->updated_at->format(DateTime::ISO8601)}}">

<meta name="twitter:card" content="article">
<meta name="twitter:site" content="@pianolit">
<meta name="twitter:title" content="{{$post->title}}">
<meta name="twitter:description" content="{{$post->description}}">
<meta name="twitter:app:country" content="US">
<meta name="twitter:app:name:iphone" content="PianoLIT">
<meta name="twitter:app:id:iphone" content="00000000">

<meta itemprop="name" content="{{$post->title}}"/>
<meta itemprop="headline" content="{{$post->title}}"/>
<meta itemprop="description" content="{{$post->description}}"/>
<meta itemprop="image" content="{{$post->cover_image()}}"/>
<meta itemprop="datePublished" content="{{$post->created_at->format(DateTime::ISO8601)}}"/>
<meta itemprop="dateModified" content="{{$post->updated_at->format(DateTime::ISO8601)}}" />
<meta itemprop="author" content="PianoLIT"/>

<link rel="canonical" href="{{url()->current()}}" />
<style type="text/css">
p img {
	max-width: 100%;
	height: auto;
}
</style>

<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'page_url' => url()->current(),
        'page_id' => $post->slug
    ]); ?>
</script>
@endpush

@section('content')
<section id="blog-post" class="container mb-5">
	<div class="row border-bottom pb-4 mb-4">
		<div class="col-lg-8 col-12 mx-auto">
			@if(! empty($preview))
			<div class="alert alert-warning" role="alert">
			  <i class="fas fa-exclamation-triangle mr-2"></i>This post is <u>not published</u>. Only admins can see this page.
			</div>
			@endif
			<div class="mb-4">
				<div class="d-flex flex-wrap mb-2">
					@each('components.blog.topic', $post->topics, 'topic')
				</div>
				<h1 class="mb-4">{{$post->title}}</h1>
				<p class="text-muted font-lg">{{$post->description}}</p>
				<div class="d-apart text-muted">
					<p><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
					<p><small><i class="fas fa-eye mr-2"></i>{{$post->views}}</small></p>
				</div>
				<figure class="figure">
					<img src="{{$post->cover_image()}}" class="figure-img img-fluid rounded">
					<figcaption class="figure-caption">{{$post->cover_credits}}</figcaption>
				</figure>
			</div>
			<div class="blog-post mb-4">
				{!! $post->content !!}
			</div>
		</div>
	</div>

	<div class="row mb-5">
		<div class="col-12 mb-4">
			<div><strong>READ NEXT</strong></div>
		</div>
		@each('components.blog.cards.small', $suggestions, 'suggestion')
	</div>

	<div class="row">
		<div class="col-12 mb-2">
			<div id="disqus_thread"></div>
		</div>
	</div>
</section>
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
<script type="text/javascript">
$('.card-title').each(function() {
  $clamp(this, {clamp: 2});
});
</script>

<script type="text/javascript">
var disqus_config = function () {
this.page.url = app.page_url;
this.page.identifier = app.page_id;
};

(function() { // DON'T EDIT BELOW THIS LINE
var d = document, s = d.createElement('script');
s.src = 'https://pianolit.disqus.com/embed.js';
s.setAttribute('data-timestamp', +new Date());
(d.head || d.body).appendChild(s);
})();
</script>
@endpush