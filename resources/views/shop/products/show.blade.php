@extends('layouts.app', [
	'title' => $product->title . ' | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => '',
		'title' => $product->title,
		'description' => $product->subtitle,
		'thumbnail' => $product->cover_image(),
		'created_at' => $product->created_at,
		'updated_at' => $product->created_at
	]])

@push('header')
<style type="text/css">
#magazine{
	width: 800px;
	height: 400px;
}
#magazine .turn-page{
	background-color:#ccc;
}

.flipbook {
  transition: margin-left 0.25s ease-out;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

</style>
@endpush

@section('content')

<section class="container mb-5">
	<div class="row mb-5 pb-5 border-bottom">
		<div class="col-lg-6 col-md-6 col-12 d-flex align-items-center">
			<div>
				@topics(['topics' => \App\Blog\Post::first()->topics, 'route' => 'ebooks.topic'])
				<div>
					<h4 class="mb-4 clamp-2"><strong>{{$product->title}}</strong></h4>
					<ul class="list-style-none p-0 mb-4">
						<li class="lead">@fa(['icon' => 'check', 'color' => 'green'])Instant Digital Download</li>
						<li class="lead">@fa(['icon' => 'check', 'color' => 'green'])Lifetime Access</li>
						<li class="lead">@fa(['icon' => 'check', 'color' => 'green'])Easy one click payment</li>
					</ul>
				</div>
				
				<div>
					<div class="mb-2">
						@include('components.shop.price')
					</div>
					<div>
						@include('components.shop.action')
					</div>
					<div>
						@include('components.shop.preview.button')
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-12 position-relative">
			<img src="{{$product->cover_image()}}" class="w-100">
			@include('components.shop.discount-tag', ['position' => 'right'])
		</div>
	</div>

	<div class="row mb-5 pb-5 border-bottom">
		<div class="col-12">
			<h5 class="mb-4 text-center">What's it about?</h5>
			<div>{!! $product->description !!}</div>
		</div>
	</div>

	<div class="row">
		<div class="col-12 text-center">
			<h5 class="mb-4">You might also be interested in</h5>
		</div>
		@each('components.shop.display.sm', $product->suggestions()->get(), 'suggestion')
	</div>
</section>

@include('components.shop.preview.magazine')

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript" src="{{asset('js/vendor/turn.min.js')}}"></script>

<script type="text/javascript">
$('#preview-product').modal({backdrop: 'static', show: false});

$(document).ready(function() {
	let $flipbook;
	let $flipbookContainer = $('#flipbook-container');
	let $pagesContainer = $('#pages-container');
	let $loading = $('#product-loading');

	$('#preview-product').on('shown.bs.modal', function (e) {
		let productWidth = $flipbookContainer.width();
		$flipbook = $("#flipbook-model").clone().appendTo('#flipbook-container').attr('id', 'flipbook').addClass('flipbook');

		$loading.hide();
		createFlipbook($flipbook, productWidth);
	});

	$('#preview-product').on('hidden.bs.modal', function (e) {
		$flipbook.turn("destroy").remove();
		$loading.show();
	});

	$('#preview-product .product-turn').click(function() {
		let direction = $(this).data('direction');

		if ($flipbookContainer.is(':visible'))
			turnFlipbook(direction);
		
		if ($pagesContainer.is(':visible'))
			turnPreviews(direction);
	});

	function createFlipbook($flipbook, width) {
		let height = width / $flipbook.data('ratio');

		$flipbook.turn({
			width: width,
			height: height/2,
			autoCenter: true,
			when: {
				turning: function(event, page, pageObject) {
					console.log(page);
				}
			}
		});

		$flipbook.show();
	}

	function turnFlipbook(direction) {
		$flipbook.turn(direction);
	}

	function turnPreviews(direction) {
		let $current = $('#product-pages > div:visible');
		if (direction == 'next') {
			if ($current.next().length) {
				$current.toggle();
				$current.next().toggle();
			}
		} else {
			if ($current.prev().length) {
				$current.toggle();
				$current.prev().toggle();
			}
		}
	}
});

</script>

@endpush