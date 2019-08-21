@extends('layouts.app', ['title' => $post->title . ' | PianoLIT Blog',
	'shareable' => [
		'keywords' => '',
		'title' => $post->title,
		'description' => $post->description,
		'thumbnail' => $post->thumbnail_image(),
		'created_at' => $post->created_at->format(DateTime::ISO8601),
		'updated_at' => $post->updated_at->format(DateTime::ISO8601)
	]])

@push('header')
<style type="text/css">
p img {
	max-width: 100%;
	height: auto;
}

.blog-font p, .blog-font h4, .blog-font iframe {
	margin-bottom: 1.75rem;
}

iframe {
	display: block;
    margin: 0 auto;
}

.mce-excerpt {
	margin: 0 3rem;
	font-style: italic;
}

</style>
<script async defer data-pin-hover="true" data-pin-tall="true" src="//assets.pinterest.com/js/pinit.js"></script>
<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'page_url' => url()->current(),
        'page_id' => $post->slug
    ]); ?>
</script>
@endpush

@section('content')
@include('components.progressbar')

<section id="blog-post" class="container mb-5">
	<div class="row mb-6" id="main-content">
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
				<p class="text-muted blog-font">{{$post->description}}</p>
				<div class="d-apart text-muted">
					<p><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
					<p><small><i class="fas fa-eye mr-2"></i>{{$post->views}}</small></p>
				</div>
				<figure class="figure">
					<img src="{{$post->cover_image()}}" class="figure-img img-fluid rounded">
					<figcaption class="figure-caption">{{$post->cover_credits}}</figcaption>
				</figure>
				<div class="border-bottom mb-3 pb-3 text-center">
					<p class="m-0 text-muted">Want a heads up when a new story comes out? <span class="text-blue cursor-pointer btn-subscribe">Subscribe here</span></p>
				</div>
			</div>
			<div id="blog-content" class="blog-font">
				{!! $post->content !!}
			</div>
			@if($post->references)
			<div class="mb-5 pb-4 border-bottom text-muted">
				<div class="mb-1"><strong>References</strong></div>
				<ul class="pl-4">
					<small>
						@foreach($post->referencesArray as $reference)
							<li>{{$reference}}</li>
						@endforeach
					</small>
				</ul>
			</div>
			@endif
			<div class="mb-5 d-apart">
				<div>
					<div class="d-inline-block align-middle mr-3">
						<img src="{{asset('images/brand/app-icon.svg')}}" class="rounded-circle" width="50">
					</div>
					<div class="d-inline-block align-middle" style="max-width: 320px; line-height: 1.12">
						<div style="font-size: .9rem"><strong>PianoLIT Team</strong></div>
						<div class="text-muted"><small>Where pianists discover new pieces and find inspiration to play only what they love.</small></div>
					</div>
				</div>
				<div class="text-right">

					<button  class="btn btn-primary-outline btn-sm btn-subscribe">Subscribe</button>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-6">
		<div class="col-12 mb-4">
			<div><i class="fas fa-glasses mr-2"></i><strong>READ NEXT</strong></div>
		</div>
		@each('components.blog.cards.small', $suggestions, 'suggestion')
	</div>

	<div class="row">
		<div class="col-12 mb-2">
			<div id="disqus_thread"></div>
		</div>
	</div>
</section>

@include('components.overlays.subscribe.model-1')
@include('components.blog.inner-subscribe')
@if($post->hasGift())
@include('components.blog.gift')
@endif
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
<script type="text/javascript">
$(document).ready(function() {
	showScrollProgressBar($('#main-content'));
});

$(document).on('click', "button#gift", function() {
	let $button = $(this);
	$button.find('i').removeClass('animated');
	$('#gift-post-overlay').fadeIn();
});
</script>

<script type="text/javascript">
$('.card-title').each(function() {
  $clamp(this, {clamp: 2});
});

$('.btn-subscribe').on('click', function() {
	$("#subscribe-overlay").fadeIn('fast');
});

$('#inner-subscribe').html($('#inner-subscribe-model > div'));
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