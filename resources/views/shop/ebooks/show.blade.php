@extends('layouts.app', [
	'title' => 'eBooks | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => 'ebooks,ebook music,learn music,music theory,music sheet',
		'title' => $ebook->title,
		'description' => $ebook->subtitle,
		'thumbnail' => $ebook->cover_image(),
		'created_at' => $ebook->created_at,
		'updated_at' => $ebook->created_at
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
	@include('shop.ebooks.show._lead')
	@include('shop.ebooks.show.description')
	@include('shop.ebooks.show.more')
</section>

@include('shop.ebooks.show.preview')

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript" src="{{asset('js/vendor/turn.min.js')}}"></script>

<script type="text/javascript">
$('#preview-ebook').modal({backdrop: 'static', show: false});

$(document).ready(function() {
	let $flipbook;
	let $flipbookContainer = $('#flipbook-container');
	let $pagesContainer = $('#pages-container');
	let $loading = $('#ebook-loading');

	$('#preview-ebook').on('shown.bs.modal', function (e) {
		let ebookWidth = $flipbookContainer.width();
		$flipbook = $("#flipbook-model").clone().appendTo('#flipbook-container').attr('id', 'flipbook').addClass('flipbook');

		$loading.hide();
		createFlipbook($flipbook, ebookWidth);
	});

	$('#preview-ebook').on('hidden.bs.modal', function (e) {
		$flipbook.turn("destroy").remove();
		$loading.show();
	});

	$('#preview-ebook .ebook-turn').click(function() {
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
		let $current = $('#ebook-pages > div:visible');
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