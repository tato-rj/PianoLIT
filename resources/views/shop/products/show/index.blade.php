@extends('layouts.app', [
	'title' => $product->title . ' | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => $product->keywords(),
		'title' => $product->title,
		'description' => $product->subtitle,
		'thumbnail' => $product->mockup_image(),
		'created_at' => $product->created_at,
		'updated_at' => $product->created_at
	]])

@push('header')
<style type="text/css">
#preview-product .modal-body {
	overflow: hidden;
}

#thumbnails-container > div:not(.selected) {
	opacity: 0.4;
	-webkit-filter: grayscale(100%); /* Safari 6.0 - 9.0 */
	filter: grayscale(100%);
}

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
	@include('shop.products.show.lead')

	@include('shop.products.show.about')

	@include('shop.products.show.cta')

	@include('shop.components.reviews.layout')

	@include('components.display.suggestions', [
		'title' => 'You might also be interested in',
		'card' => 'shop.components.card',
		'collection' => $product->suggestions(8)->get()])
</section>

@include('shop.components.preview.magazine')

@endsection

@push('scripts')
@addthis
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
					highlightThumbnail(page - 1);
				}
			}
		});

		$flipbook.show();
	}

	$('#thumbnails-container > div').on('click', function() {
		let index = $(this).index();
		turnToPage(index);
	});

	function highlightThumbnail(index) {
		$('#thumbnails-container > div').removeClass('selected');
		$('#thumbnails-container > div').eq(index).addClass('selected');
	}

	function turnToPage(index) {
		$flipbook.turn('page', index + 1);

		$('#product-pages > div').hide();
		$('#product-pages > div').eq(index).show();
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
				turnToPage($current.next().index());
			}
		} else {
			if ($current.prev().length) {
				$current.toggle();
				$current.prev().toggle();
				turnToPage($current.prev().index());
			}
		}
	}
});

</script>

<script type="text/javascript">
var audio = new Audio;
let $playBtn = $('button[data-action="play"]');
let $stopBtn = $('button[data-action="stop"]');

$playBtn.click(function() {
	$(this).disable();
	audio.src = $(this).data('src');
	audio.play();
});

$('button[data-action="stop"]').click(function() {
	$(this).hide();
	$playBtn.show();
	audio.pause();
	audio.src = null;
});

audio.oncanplay = function() {
	$playBtn.hide().enable();
	$stopBtn.show();
};
</script>

<script type="text/javascript">
$('.editable-star').on('mouseover', function() {
	let $selection = $(this);

	resetSelections(false);
	$selection.addClass('fas').removeClass('far');
	$selection.prevAll('i').addClass('fas').removeClass('far');
});

$('.editable-star').on('mouseleave', function() {
	highlightSelected();
});

$('.editable-star').on('click', function() {
	let $selection = $(this);

	selectStars($selection);
	highlightSelected();
});

function selectStars($selection)
{
	resetSelections();
	$selection.attr('selected', true);
	$selection.prevAll('i').attr('selected', true);

	$('input[name="rating"]').val(selectedStars());
}

function highlightSelected()
{
	$('.editable-star').each(function() {
		if ($(this).attr('selected')) {
			$(this).addClass('fas').removeClass('far');
		} else {
			$(this).removeClass('fas').addClass('far');
		}
	});
}

function selectedStars()
{
	return $('.editable-star[selected]').length;
}

function resetSelections(hard = true)
{
	if (hard) {
		$('.editable-star').removeAttr('selected').removeClass('fas').addClass('far');		
	} else {
		$('.editable-star').removeClass('fas').addClass('far');
	}
}

$('#review-modal').on('hidden.bs.modal', function (e) {
  resetSelections();
  $('#review-modal input, #review-modal textarea').val('');
})

</script>

@endpush