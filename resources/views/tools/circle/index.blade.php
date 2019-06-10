@extends('layouts.app')

@push('header')
<style type="text/css">
svg#target {
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
g#LETTERS > g {
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
<div class="container">
	<div class="row h-100vh">
		<div class="col-lg-6 col-md-6 col-12 p-5">
			<div id="wheel-container" class="w-100 position-relative">
				@include('tools.circle.wheel')
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-12" id="labels-container" style="display: none;">
			<div class="mb-3">
				<label class="text-muted m-0">KEY</label>
				<h4 id="key-name"></h4>
			</div>
			<div class="mb-3">
				<label class="text-muted m-0">RELATIVE</label>
				<h4 id="key-relative"></h4>
			</div>
			<div class="mb-3">
				<label class="text-muted m-0">NEIGHBORS</label>
				<div id="key-neighbors"></div>
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
  let init, rotate, start, stop,
  	$labels = $('#labels-container'),
	$letters = $('g#LETTERS > g'),
    active = false,
    angle = 0,
    rotation = 0,
    startAngle = 0,
    center = {
      x: 0,
      y: 0
    },
    R2D = 180 / Math.PI,
    rot = document.getElementById('target');

  init = function() {
    rot.addEventListener("mousedown", start, false);

    $(document).bind('mousemove', function(event) {
      if (active === true) {
        event.preventDefault();
	  	$letters.removeClass('key');
	  	$labels.fadeOut();
        rotate(event);
      }
    });
    $(document).bind('mouseup', function(event) {
		event.preventDefault();
		stop(event);
	    setTimeout( function() {
	    	highlightKey();
	    }, 600);
    });
  };

  start = function(e) {
    e.preventDefault();

    let bb = this.getBoundingClientRect(),
      t = bb.top,
      l = bb.left,
      h = bb.height,
      w = bb.width,
      x, y;
    center = {
      x: l + (w / 2),
      y: t + (h / 2)
    };
    x = e.clientX - center.x;
    y = e.clientY - center.y;
    startAngle = R2D * Math.atan2(y, x);

    return active = true;
  };

  rotate = function(e) {
    e.preventDefault();
    let x = e.clientX - center.x,
      y = e.clientY - center.y,
      d = R2D * Math.atan2(y, x);

    rotation = snap(d - startAngle);

    $(rot).css('transform', "rotate(" + (angle + rotation) + "deg)");
  };

  stop = function() {
    angle += rotation;
    return active = false;
  };

  snap = function(rotation) {
  	return Math.ceil(rotation/30)*30;
  };

  highlightKey = function(angle) {
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

  	if ($('g.key').length > 1)
  		$('g').removeClass('key');
  };

  showDescription = function() {
  	let $key = $('g.key');
  	let relatives = $key.attr('key-major-relatives');

	$('#key-name').text($key.attr('key-major') || '');
	$('#key-relative').text($key.attr('key-minor') || '');

	if (relatives) {
		$('#key-neighbors').html('');
		relatives.split('|').forEach(function(relative) {
			$('#key-neighbors').append('<span class="shadow-sm badge badge-light mr-2">'+relative+'</span>');
		});
	}

	$labels.fadeIn();
  };

  init();

}).call(this);
</script>
@endpush