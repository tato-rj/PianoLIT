@extends('layouts.app')

@push('header')
<style type="text/css">
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
</style>
@endpush

@section('content')
<div class="container mb-7">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-12 px-5">
			<div id="wheel-container" class="w-100 position-relative">
				@include('tools.circle.wheel')
			</div>
			<div id="wheel-controls" class="w-100 d-flex align-items-center justify-content-between px-5">
				<button direction="left" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-left"></i></button>
				<button direction="right" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-right"></i></button>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-12" id="labels-container">
			<div id="mode-controls" class=" mb-2">
				<ul class="nav nav-tabs mb-4" id="mode-tabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="major-tab" data-toggle="tab" href="#mode-major" role="tab" aria-controls="major" aria-selected="true">Major</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="minor-tab" data-toggle="tab" href="#mode-minor" role="tab" aria-controls="minor" aria-selected="false">Minor</a>
					</li>
				</ul>
				<div class="tab-content p-3" id="mode-panels" style="display: none;">
					<div class="tab-pane fade show active" id="mode-major" role="tabpanel">
						<div class="mb-3">
							<label class="text-muted m-0">KEY</label>
							<h4 class="key-name"></h4>
						</div>
						<div class="mb-3">
							<label class="text-muted m-0">RELATIVE</label>
							<h4 class="key-relative"></h4>
						</div>
						<div class="mb-4">
							<label class="text-muted">NEIGHBORS</label>
							<div class="key-neighbors d-flex flex-wrap"></div>
						</div>
						<div class="bg-light p-2 rounded border row">
							<div class="col-6 border-right">
								<label><small><strong>DOMINANTS</strong></small></label>
								<div class="key-dominants"></div>
							</div>
							<div class="col-6">
								<label><small><strong>SUBDOMINANTS</strong></small></label>
								<div class="key-subdominants"></div>
							</div>
						</div>
					</div>
					<div class="tab-pane fade" id="mode-minor" role="tabpanel" aria-labelledby="profile-tab">
						<div class="mb-3">
							<label class="text-muted m-0">KEY</label>
							<h4 class="key-name"></h4>
						</div>
						<div class="mb-3">
							<label class="text-muted m-0">RELATIVE</label>
							<h4 class="key-relative"></h4>
						</div>
						<div class="mb-4">
							<label class="text-muted">NEIGHBORS</label>
							<div class="key-neighbors d-flex flex-wrap"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
///////////////////////////////
// -------  rotate  -------- //
///////////////////////////////

(function() {
  let $labels = $('#mode-panels'),
  	  $circle = $('#circle'),
	  $letters = $('g#letters > g'),
	  $mode = $('input[name="mode"]'),
      enabled = true,
      rotation = 0;

  init = function() {

  	showDescription();

    $('#wheel-controls button').on('click', function() {
    	if (enabled) {
    		disable();
	    	let direction = $(this).attr('direction') == 'left' ? -30 : 30;
	    	rotation += direction;
	    	applyCss(rotation);
	    	hideLabels();
		    highlightKey();
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
  	$('g').removeClass('key');
  	$labels.fadeOut('fast');
  };

  highlightKey = function() {
    setTimeout( function() {
	  	let offsets = [];  	
	  	
	  	$letters.each(function() {
	  		offsets.push($(this).offset().top);
	  	});
	  	
	  	offsets.sort();
	  	
	  	$letters.each(function() {
	  		if ($(this).offset().top == offsets[0]) {
	  			$(this).addClass('key');
	  			showDescription();
		  		return false;
		  	}
	  	});

	  	enable();
    }, 400);
  };

  showDescription = function() {
  	let $key = $('g.key');
  	let relatives = $key.attr('key-relatives');
  	let majorDom = $key.attr('key-major-dominants');
  	let minorDom = $key.attr('key-minor-dominants');
  	let majorSub = $key.attr('key-major-subdominants');
  	let minorSub = $key.attr('key-minor-subdominants');

	$('#mode-major .key-name').text($key.attr('key-major') || '');
	$('#mode-major .key-relative').text($key.attr('key-minor') || '');

	$('#mode-minor .key-name').text($key.attr('key-minor') || '');
	$('#mode-minor .key-relative').text($key.attr('key-major') || '');

	if (relatives) {
		$('.key-neighbors').html('');
		relatives.split('|').forEach(function(relative) {
			$('.key-neighbors').append('<h5 class="mr-2"><span class="border badge badge-light px-2 py-1">'+relative+'</span></h5>');
		});
	}

	if (majorDom) {
		$('#mode-major .key-dominants').html('');
		majorDom.split('|').forEach(function(dominant) {
			$('#mode-major .key-dominants').append('<div>'+dominant+'</div>');
		});
	}

	if (minorDom) {
		$('#mode-minor .key-dominants').html('');
		minorDom.split('|').forEach(function(dominant) {
			$('#mode-minor .key-dominants').append('<div>'+dominant+'</div>');
		});
	}

	if (majorSub) {
		$('#mode-major .key-subdominants').html('');
		majorSub.split('|').forEach(function(subdominant) {
			$('#mode-major .key-subdominants').append('<div>'+subdominant+'</div>');
		});
	}

	if (minorSub) {
		$('#mode-minor .key-subdominants').html('');
		minorSub.split('|').forEach(function(subdominant) {
			$('#mode-minor .key-subdominants').append('<div>'+subdominant+'</div>');
		});
	}

	$labels.fadeIn();
  };

  init();

}).call(this);
</script>
@endpush