var player;

$('.audio-control button').on('click', function(e) {
	e.stopPropagation();
	let $btn = $(this);

	$btn.hide();
	$btn.siblings('button').show();

	if (isPlaying()) {
		resetAudio();
	} else {
		play($btn.siblings('audio'));
	}
});

function play($audio)
{
	player = $audio.get(0);
	player.play();
}

function resetAudio()
{
	if (player) {
		player.pause();
		player = null;
		$('button[data-action="pause"]').hide();
		$('button[data-action="play"]').show();
	}
}

function isPlaying()
{
	return player ? true : false;
}


let tags = $('#dial').data('tags');
let $dialLabel = $('#dial-label');

$('#dial').knob({
	'step': 1,
	'thickness': .5,
    'min': 0,
    'max': 12,
    'displayInput': false,
    'fgColor': '#222222',
    'change' : function (v) {
    	let pos = Math.round(v);
    	let tag = ucfirst(tags[pos]);
    	$dialLabel.removeClass('opacity-2').addClass('alert-green selected-answer').text(tag).attr('value', tag);
    	releaseButton();
    }
});

$('button#carousel-submit').click(function() {
	let $btn = $(this);
	let answers = $('[data-carousel="answer"].selected-answer').attrToArray('value');
	
	$btn.addLoader();
	
	setTimeout(function() {	
		axios.get($btn.data('url'), {params: {input: answers}})
			 .then(function(response) {
			 	console.log(answers);
			 	$('body').append(response.data);
			 	$('#match-modal').modal('show');

			 	$('#match-modal').on('hidden.bs.modal', function (e) {
					$('#match-modal').remove();
				});

				new Plyr('#'+$('.video-container video').attr('id'));
			 })
			 .catch(function(error) {
			 	console.log(error);
			 })
			 .then(function() {
			 	$btn.removeLoader();
			 });
	}, 1000);
});

$('button#carousel-control').click(function() {
	$('#find-match-carousel').carousel('next');
});

$('#find-match-carousel').on('slide.bs.carousel', function (event) {
  let $panel = $(event.relatedTarget);

	$('.carousel-buttons button').prop('disabled', true);

  if ($panel.is(':last-child')) {
  	$('button#carousel-control').hide();
  	$('button#carousel-submit').show();
  }
});

$('.carousel-answers [data-carousel="answer"][data-type="single"]').click(function() {
	selectOnly(this);
	releaseButton();
});

$('.carousel-answers [data-carousel="answer"][data-type="multi"]').click(function() {
	toggle(this);
	checkSelected(this)
});

$(document).on('hide.bs.modal', '#match-modal', function (e) {
  reset();
})

function toggle(elem) 
{
	resetAnimations();

	if (isSelected(elem)) {
		unselect(elem);
	} else {
		if (allSelected(elem)) {
			animate(elem);
		} else {
			select(elem);
		}
	}
}

function select(elem, onlyThis = false)
{
	resetAudio();
	$(elem).addClass('alert-green selected-answer').removeClass('opacity-6 list-group-item-action');

	if (onlyThis) {
		unselectAllExcept(elem);
	} else {
		if (allSelected(elem))
			unselectAllExcept(elem, true);
	}
}

function selectOnly(elem)
{
	select(elem, true);
}

function unselect(elem) 
{
	$(elem).removeClass('alert-green selected-answer').addClass('opacity-6 list-group-item-action');	

	if (selectedCount(elem) == 0)
		$(elem).closest('.carousel-answers').find('[data-carousel="answer"]').removeClass('opacity-6');	
}

function unselectAllExcept(elem, all = false)
{
	$(elem).closest('.carousel-answers').find('[data-carousel="answer"]').not(all ? '.selected-answer' : elem).removeClass('alert-green selected-answer').addClass('opacity-6 list-group-item-action');
}

function isSelected(elem) 
{
	return $(elem).hasClass('selected-answer');
}

function selectedCount(elem) 
{
	return $(elem).closest('.carousel-answers').find('[data-carousel="answer"].selected-answer').length;
}

function allSelected(elem) 
{
	return selectedCount(elem) == 3;
}

function checkSelected(elem) {
	let remaining = 3 - selectedCount(elem);

	if (remaining > 0 && remaining < 3) {
		showAlert(remaining);
		lockButton();

		return false;
	} else {
		hideAlert();
		releaseButton();

		return true;
	}
}

function animate(elem)
{
	$(elem).addClass('animated headShake');
}

function resetAnimations()
{
	$('.carousel-answers [data-carousel="answer"]').removeClass('animated headShake');
}

function releaseButton()
{
	$('.carousel-buttons button').prop('disabled', false);
}

function lockButton()
{
	$('.carousel-buttons button').prop('disabled', true);
}

function showAlert(remaining)
{
	$('#remaining-alert span').text(remaining);
	$('#remaining-alert').css('opacity', 1);
}

function hideAlert()
{
	$('#remaining-alert').css('opacity', 0);
}

function reset()
{
	$('.carousel-answers [data-carousel="answer"]').removeClass('animated headShake selected-answer alert-green opacity-6').addClass('list-group-item-action');
	$('#carousel-container > #success-overlay').hide();
	$('#find-match-carousel').carousel(0);
  	$('button#carousel-control').show();
  	$('button#carousel-submit').hide();
}