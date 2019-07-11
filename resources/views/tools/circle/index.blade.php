@extends('layouts.app', ['title' => 'The (interactive) Circle of Fifths | ' . config('app.name')])

@push('header')
<meta name="twitter:card" value="An interactive Circle of Fifths that will help you understand what it is and how to use it.">
<meta property="og:site_name" content="PianoLIT" />
<meta property="og:title" content="The Interactive Circle of Fifths" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{url()->current()}}" />
<meta property="og:image" content="{{asset('images/misc/thumbnails/circle.jpg')}}" />
<meta property="og:image:width" content="400" />
<meta property="og:image:height" content="245" />
<meta property="og:description" content="An interactive Circle of Fifths that will help you understand what it is and how to use it." />
<meta property="article:published_time" content="{{carbon('20-06-2019')}}">
<meta property="article:modified_time" content="{{carbon('20-06-2019')}}">
<meta property="og:updated_time" content="{{carbon('20-06-2019')}}">

<meta name="twitter:site" content="@litpiano">
<meta name="twitter:card" content="summary">
<meta name="twitter:image" content="{{asset('images/misc/thumbnails/circle.jpg')}}">
<meta name="twitter:title" content="The Interactive Circle of Fifths">
<meta name="twitter:description" content="An interactive Circle of Fifths that will help you understand what it is and how to use it.">
<meta name="twitter:app:country" content="US">
<meta name="twitter:app:name:iphone" content="PianoLIT">
<meta name="twitter:app:id:iphone" content="00000000">

<meta itemprop="name" content="The Interactive Circle of Fifths"/>
<meta itemprop="headline" content="An interactive Circle of Fifths that will help you understand what it is and how to use it."/>
<meta itemprop="description" content="An interactive Circle of Fifths that will help you understand what it is and how to use it."/>
<meta itemprop="image" content="{{asset('images/misc/thumbnails/circle.jpg')}}"/>
<meta itemprop="datePublished" content="{{carbon('20-06-2019')}}"/>
<meta itemprop="dateModified" content="{{carbon('20-06-2019')}}" />
<meta itemprop="author" content="PianoLIT"/>

<link rel="canonical" href="{{url()->current()}}" />
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

.key-neighbors span:not(:last-of-type)::after, .key-enharmonic-neighbors span:not(:last-of-type)::after {
	content: '|';
	margin-left: .4rem;
	margin-right: .4rem;
}

</style>
@endpush

@section('content')
<div class="container mb-4">
	<div class="mb-5 text-center">
		<h3>The Circle of Fifths</h3>
		<p class="text-grey">An interactive and fun tool to explore music harmony in an innovative way. Enjoy!</p>
	</div>
	<div class="row mb-6">
		<div class="col-lg-5 col-md-6 col-12 px-4 mb-6">
			<div id="wheel-container" class="w-100 position-relative">
				@include('tools.circle.wheel')
			</div>
			<div id="wheel-controls" class="w-100 d-flex align-items-center px-5">
				<button direction="right" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-left"></i></button>
				<div class="flex-grow text-grey text-center mx-2"><div><small>Use the arrows to turn the wheel</small></div></div>
				<button direction="left" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-right"></i></button>
			</div>
		</div>
		<div class="col-lg-7 col-md-6 col-12" id="labels-container">
			<div id="mode-controls" class=" mb-2">
				<ul class="nav nav-tabs mode-tabs mb-3" id="mode-tabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" data-name="major" id="major-tab" data-toggle="tab" href="#mode-major" role="tab" aria-controls="major" aria-selected="true">Major</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-name="minor" id="minor-tab" data-toggle="tab" href="#mode-minor" role="tab" aria-controls="minor" aria-selected="false">Minor</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-name="enharmonic" id="enharmonic-tab" data-toggle="tab" href="#mode-enharmonic" role="tab" aria-controls="minor" aria-selected="false">Enharmonic</a>
					</li>
				</ul>
				<div class="tab-content p-1 t-2" id="mode-panels" style="opacity: 0;">
					@include('tools.circle.labels.major')
					@include('tools.circle.labels.minor')
					@include('tools.circle.labels.enharmonic')
				</div>
			</div>
		</div>
		<div class="col-12 mt-3 text-center">
			<p class="text-grey">The scale on a piano keyboard</p>
			@include('components.piano.keyboard')
		</div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('tools.circle.info.key')
@include('tools.circle.info.signature')
@include('tools.circle.info.relative')
@include('tools.circle.info.neighbors')
@include('tools.circle.info.functional')
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
		let $button = $(this);
		$button.siblings('div').find('div').fadeOut();
    	if (enabled) {
    		disable();
	    	let direction = $button.attr('direction') == 'left' ? -30 : 30;
	    	rotation += direction;
	    	applyCss(rotation);
	    	hideLabels();
		    highlightKey($button.attr('direction'));
		}
    });
  };

  highlightKeyboard = function() {
  	let mode = $('.mode-tabs .active').attr('data-name');
  	let $keysArray = $('.keyboard-key');
  	let scale = JSON.parse($('g.key').attr('key-'+mode+'-scale'));
  	let count = 1;
  	let index;

  	$('.keyboard .dot').hide();
  	
  	$keysArray.each(function() {
  		let $key = $(this);
  		let keyNames = JSON.parse($key.attr('data-names'));

  		if (keyNames.includes(scale[0])) {
  			index = $('.keyboard-key').index(this);
  			return false;
  		}
  	});

  	$keysArray.splice(0, index);

  	$keysArray.each(function() {
  		let $key = $(this);
  		let keyNames = JSON.parse($key.attr('data-names'));

  		if (count > 12)
  			return false;

  		let found = scale.filter(function(n) {
  			return keyNames.indexOf(n) !== -1;
  		});

  		if (found.length)
  			$key.find(' > .dot').fadeIn();

		count++;
  	});
  }

	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		highlightKeyboard();
	});

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
		highlightKeyboard();
	  	enable();
    }, 400);
  };

  showDescription = function() {
  	let $key = $('g.key');
  	let $majorSignature = $('#mode-major .key-signature');
  	let $minorSignature = $('#mode-minor .key-signature');
  	let $enharmonicSignature = $('#mode-enharmonic .key-signature');

  	let id = $key.attr('id');
  	let enharmonicId = $key.attr('enharmonic-id');
  	let neighbors = JSON.parse($key.attr('key-neighbors'));
  	let enharmonicNeighbors = JSON.parse($key.attr('key-enharmonic-neighbors'));

  	let majorRoman = JSON.parse($key.attr('key-major-roman'));
  	let majorTonic = JSON.parse($key.attr('key-major-tonic'));
  	let majorDom = JSON.parse($key.attr('key-major-dominant'));
  	let majorSub = JSON.parse($key.attr('key-major-subdominant'));

  	let enharmonicRoman = JSON.parse($key.attr('key-enharmonic-roman'));
  	let enharmonicTonic = JSON.parse($key.attr('key-enharmonic-tonic'));
  	let enharmonicDom = JSON.parse($key.attr('key-enharmonic-dominant'));
  	let enharmonicSub = JSON.parse($key.attr('key-enharmonic-subdominant'));

  	let minorRoman = JSON.parse($key.attr('key-minor-roman'));
  	let minorTonic = JSON.parse($key.attr('key-minor-tonic'));
  	let minorDom = JSON.parse($key.attr('key-minor-dominant'));
  	let minorSub = JSON.parse($key.attr('key-minor-subdominant'));

  	$majorSignature.attr('src', $majorSignature.attr('data-folder') + '/key-loading.svg').attr('src', $majorSignature.attr('data-folder') + '/' + id + '.svg');
  	$minorSignature.attr('src', $minorSignature.attr('data-folder') + '/key-loading.svg').attr('src', $minorSignature.attr('data-folder') + '/' + id + '.svg');
  	$enharmonicSignature.attr('src', $enharmonicSignature.attr('data-folder') + '/key-loading.svg').attr('src', $enharmonicSignature.attr('data-folder') + '/' + enharmonicId + '.svg');

	$('#mode-major .key-name').text($key.attr('key-major'));
	$('#mode-major .key-relative').text($key.attr('key-minor'));

	$('#mode-minor .key-name').text($key.attr('key-minor'));
	$('#mode-minor .key-relative').text($key.attr('key-major'));

	$('#mode-enharmonic .key-name').text($key.attr('key-enharmonic-major'));
	$('#mode-enharmonic .key-relative').text($key.attr('key-enharmonic-minor'));

	$('.key-neighbors').html('');
	neighbors.forEach(function(neighbor) {
		$('.key-neighbors').append('<span><strong>'+neighbor+'</strong></span>');
	});

	$('.key-enharmonic-neighbors').html('');
	enharmonicNeighbors.forEach(function(neighbor) {
		$('.key-enharmonic-neighbors').append('<span><strong>'+neighbor+'</strong></span>');
	});

	//////////////////////////
	// ENHARMONIC FUNCTIONS //
	//////////////////////////
	$('#mode-enharmonic .key-tonic').html('');
	enharmonicTonic.forEach(function(tonic) {
		$('#mode-enharmonic .key-tonic').append('<div>'+tonic+'</div>');
	});

	$('#mode-enharmonic .key-dominant').html('');
	enharmonicDom.forEach(function(dominant) {
		$('#mode-enharmonic .key-dominant').append('<div>'+dominant+'</div>');
	});

	$('#mode-enharmonic .key-subdominant').html('');
	enharmonicSub.forEach(function(subdominant) {
		$('#mode-enharmonic .key-subdominant').append('<div>'+subdominant+'</div>');
	});

	$('.key-enharmonic-roman').html('');
	for (key in enharmonicRoman) {
		$('.key-enharmonic-roman').append(`
			<div class="mr-2 mb-2 bg-light px-2 py-1">
				<strong>`+key+`</strong> `+enharmonicRoman[key]+`</div>
			</div>`);
	}

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

	$('.key-major-roman').html('');
	for (key in majorRoman) {
		$('.key-major-roman').append(`
			<div class="mr-2 mb-2 bg-light px-2 py-1">
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

	$('.key-minor-roman').html('');
	for (key in minorRoman) {
		$('.key-minor-roman').append(`
			<div class="mr-2 mb-2 bg-light px-2 py-1">
				<strong>`+key+`</strong> `+minorRoman[key]+`</div>
			</div>`);
	}

	$labels.css('opacity', 1);
  };

  init();

}).call(this);
</script>

@endpush