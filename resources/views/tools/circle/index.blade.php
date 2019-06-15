@extends('layouts.app')

@push('header')
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
<style type="text/css">
#mode-tabs .nav-link {
	color: #b8c2cc;
}

#mode-tabs .active {
	color: #343a40!important;
	font-weight: bold;
}

svg#circle {
	transition-duration: .5s;
	z-index: 1;
}
div#highlight {
    left: 50%;
    top: -12px;
    transform: translateX(-50%);
    z-index: -1;
    width: 112%;
    opacity: .2;
}
g#letters > g {
	opacity: 0.5;
	transition: .2s;
	-webkit-filter: grayscale(100%); /* Safari 6.0 - 9.0 */
	filter: grayscale(100%);
}
g.key {
	opacity: 1!important;
}

.key-neighbors span:not(:last-of-type)::after {
	content: '|';
	margin-left: .4rem;
	margin-right: .4rem;
}
.key-negative > div:not(:last-of-type) {
	padding-right: .25rem;
}
</style>
@endpush

@section('content')
<div class="container mb-7">
	<div class="row mb-6">
		<div class="col-lg-5 col-md-6 col-12 px-4 mb-6">
			<div id="wheel-container" class="w-100 position-relative">
				@include('tools.circle.wheel')
			</div>
			<div id="wheel-controls" class="w-100 d-flex align-items-center justify-content-between px-5">
				<button direction="left" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-left"></i></button>
				<button direction="right" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-right"></i></button>
			</div>
		</div>
		<div class="col-lg-7 col-md-6 col-12" id="labels-container">
			<div id="mode-controls" class=" mb-2">
				<ul class="nav nav-tabs mb-3" id="mode-tabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="major-tab" data-toggle="tab" href="#mode-major" role="tab" aria-controls="major" aria-selected="true">Major</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="minor-tab" data-toggle="tab" href="#mode-minor" role="tab" aria-controls="minor" aria-selected="false">Minor</a>
					</li>
				</ul>
				<div class="tab-content p-1 t-2" id="mode-panels" style="opacity: 0;">
					@include('tools.circle.labels.major')
					@include('tools.circle.labels.minor')
				</div>
			</div>
		</div>
	</div>
</div>

@include('tools.circle.info.key')
@include('tools.circle.info.relative')
@include('tools.circle.info.neighbors')
@include('tools.circle.info.functional')
@include('tools.circle.info.negative')
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
<script type="text/javascript">
///////////////////////////////
// -------  rotate  -------- //
///////////////////////////////

(function() {
  let $labels = $('#mode-panels'),
  	  $circle = $('#circle'),
	  $letters = $('g#letters > g'),
	  $mode = $('input[name="mode"]'),
	  $next,
      enabled = true,
      rotation = 0;

  init = function() {

  	showDescription();

    $('#wheel-controls button').on('click', function() {
    	if (enabled) {
    		let $button = $(this);
    		disable();
	    	let direction = $button.attr('direction') == 'left' ? -30 : 30;
	    	rotation += direction;
	    	applyCss(rotation);
	    	hideLabels();
		    highlightKey($button.attr('direction'));
		}
    });
  };

  applyCss = function(degrees) {
    $circle.css({
    	'-webkit-transform': "rotate(" + degrees + "deg)",
    	'-moz-transform': "rotate(" + degrees + "deg)",
    	'-ms-transform': "rotate(" + degrees + "deg)",
    	'-o-transform': "rotate(" + degrees + "deg)",
    	'transform': "rotate(" + degrees + "deg)"
    });
  }

  disable = function() {
    enabled = false;
  };

  enable = function() {
    enabled = true;
  };

  hideLabels = function() {
  	$labels.css('opacity', 0);
  };

  highlightKey = function(direction) {
  	$next = $($('g.key').first().attr('control-'+direction));

  	$('g').removeClass('key');
    setTimeout( function() {
    	$next.addClass('key');
    	showDescription();
	  	// let offsets = [];  	
	  	
	  	// $letters.each(function() {
	  	// 	offsets.push($(this).offset().top);
	  	// });
	  	
	  	// offsets.sort();
	  	
	  	// $letters.each(function() {
	  	// 	if ($(this).offset().top == offsets[0]) {
	  	// 		$(this).addClass('key');
	  	// 		showDescription();
		  // 		return false;
		  // 	}
	  	// });

	  	enable();
    }, 400);
  };

  showDescription = function() {
  	let $key = $('g.key');
  	let neighbors = JSON.parse($key.attr('key-neighbors'));

  	let majorRoman = JSON.parse($key.attr('key-major-roman'));
  	let majorTonic = JSON.parse($key.attr('key-major-tonic'));
  	let majorDom = JSON.parse($key.attr('key-major-dominant'));
  	let majorSub = JSON.parse($key.attr('key-major-subdominant'));
  	let majorNeg = JSON.parse($key.attr('key-major-negative'));

  	let minorRoman = JSON.parse($key.attr('key-minor-roman'));
  	let minorTonic = JSON.parse($key.attr('key-minor-tonic'));
  	let minorDom = JSON.parse($key.attr('key-minor-dominant'));
  	let minorSub = JSON.parse($key.attr('key-minor-subdominant'));
  	let minorNeg = JSON.parse($key.attr('key-minor-negative'));

	$('#mode-major .key-name').text($key.attr('key-major'));
	$('#mode-major .key-relative').text($key.attr('key-minor'));

	$('#mode-minor .key-name').text($key.attr('key-minor'));
	$('#mode-minor .key-relative').text($key.attr('key-major'));


	$('.key-neighbors').html('');
	neighbors.forEach(function(neighbor) {
		$('.key-neighbors').append('<span><strong>'+neighbor+'</strong></span>');
	});

	/////////////////////
	// MAJOR FUNCTIONS //
	/////////////////////
	$('#mode-major .key-tonic').html('');
	majorTonic.forEach(function(tonic) {
		$('#mode-major .key-tonic').append('<div>'+tonic+'</div>');
	});

	$('#mode-major .key-dominant').html('');
	majorDom.forEach(function(dominant) {
		$('#mode-major .key-dominant').append('<div>'+dominant+'</div>');
	});

	$('#mode-major .key-subdominant').html('');
	majorSub.forEach(function(subdominant) {
		$('#mode-major .key-subdominant').append('<div>'+subdominant+'</div>');
	});

	$('#mode-major .key-negative').html('');
	for (var i=0; i<7; i++) {
		$('#mode-major .key-negative').append(`
			<div style="width:14.25%">
				<div class="rounded border">
					<div class="text-center text-grey pt-1">`+majorNeg['regular'][i]+`</div>
					<div class="mb-1 text-grey text-center"><i class="fas fa-sort-down"></i></div>
					<div class="text-center text-dark pb-1"><strong>`+majorNeg['negative'][i]+`</strong></div>
				</div>
			</div>`);		
	}

	$('.key-major-roman').html('');
	for (key in majorRoman) {
		$('.key-major-roman').append(`
			<div class="mr-2 border-bottom border-1x">
				<strong>`+key+`</strong> `+majorRoman[key]+`</div>
			</div>`);
	}
	/////////////////////
	// MINOR FUNCTIONS //
	/////////////////////
	$('#mode-minor .key-tonic').html('');
	minorTonic.forEach(function(tonic) {
		$('#mode-minor .key-tonic').append('<div>'+tonic+'</div>');
	});

	$('#mode-minor .key-dominant').html('');
	minorDom.forEach(function(dominant) {
		$('#mode-minor .key-dominant').append('<div>'+dominant+'</div>');
	});

	$('#mode-minor .key-subdominant').html('');
	minorSub.forEach(function(subdominant) {
		$('#mode-minor .key-subdominant').append('<div>'+subdominant+'</div>');
	});

	$('#mode-minor .key-negative').html('');
	for (var i=0; i<7; i++) {
		$('#mode-minor .key-negative').append(`
			<div style="width:14.25%">
				<div class="rounded border">
					<div class="text-center text-grey pt-1">`+minorNeg['regular'][i]+`</div>
					<div class="mb-1 text-grey text-center"><i class="fas fa-sort-down"></i></div>
					<div class="text-center text-dark pb-1"><strong>`+minorNeg['negative'][i]+`</strong></div>
				</div>
			</div>`);		
	}

	$('.key-minor-roman').html('');
	for (key in minorRoman) {
		$('.key-minor-roman').append(`
			<div class="mr-2 border-bottom border-1x">
				<strong>`+key+`</strong> `+minorRoman[key]+`</div>
			</div>`);
	}

	$labels.css('opacity', 1);
  };

  init();

}).call(this);
</script>

@endpush