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
<script async defer data-pin-hover="true" data-pin-tall="true" src="//assets.pinterest.com/js/pinit.js"></script>
<script>
    window.app.page_url = <?php echo json_encode(url()->current()); ?>;
    window.app.page_id = <?php echo json_encode($post->slug); ?>;
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
				
				@topics(['topics' => $post->topics, 'route' => 'posts.topic'])

				<h1 class="mb-4">{{$post->title}}</h1>
				<p class="text-muted blog-font">{{$post->description}}</p>
				<div class="d-apart text-muted">
					<p><small>{{$post->created_at->toFormattedDateString()}} &bull; {{$post->reading_time}} min read</small></p>
					<p><small><i class="fas fa-eye mr-2"></i>{{$post->views}}</small></p>
				</div>
				<figure class="figure w-100">
					<img src="{{$post->cover_image()}}" class="figure-img img-fluid rounded w-100">
					<figcaption class="figure-caption">{{$post->cover_credits}}</figcaption>
				</figure>
				<div class="border-bottom mb-3 pb-3 text-center">
					<p class="m-0 text-muted">Want a heads up when a new story comes out? <span class="text-blue cursor-pointer show-overlay" data-target="#subscribe-overlay">Subscribe here</span></p>
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
							<li><div class="d-flex">{{$reference}}</div></li>
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
@include('components.addthis')
<script type="text/javascript" src="{{asset('js/components/disqus.js')}}"></script>
<script type="text/javascript">
$(document).ready(function() {
	showScrollProgressBar($('#main-content'));
});

$(document).on('click', "button#gift", function() {
	let $button = $(this);
	$button.find('i').removeClass('animated');
	$('#gift-post-overlay').fadeIn();
});

$('.card-title').clamp(2);
$('#inner-subscribe').html($('#inner-subscribe-model > div'));
</script>
@endpush