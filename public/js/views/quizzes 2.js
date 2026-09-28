var answers = [];

$('#start-quiz').click(function() {
	$(this).parent().remove();
	$('#quiz-content').show();
	showScrollProgressBar($('#main-content'));
});

$('.quiz-answers button').on('click', function() {
	$button = $(this);
	$parent = $button.parent();
	
	stopAll();
	
	$($button.attr('data-overlay')).show();

	$button.addClass('selected');
	
	getAnswers();

	console.log(answers);

	$parent.find('button[correct]').toggleClass('list-group-item-action alert-green').find('.fas').show();

	if (! $button.is('[correct]'))
		$button.toggleClass('list-group-item-action alert-red').find('.fas').show();

	if (! answers.includes(null))
		submit();
});

function getAnswers()
{
	answers = [];
	
	$('.quiz-answers').each(function(index) {
		selection = $(this).find('button.selected').index();
		answers.push(selection >= 0 ? selection : null);
	});
}

function submit() {
	axios.get($('#quiz').attr('data-url'), {params: {answers: answers}})
	.then(function(response) {
		$('#game-feedback').html(response.data);
		$('#game-results').modal('show');
	})
	.catch(function(response) {
        console.log(response);
	});
}

function stopAll() {
    var media = document.getElementsByClassName('audio'),
        i = media.length;

    while (i--) {
        media[i].pause();
        media[i].currentTime = 0;
    }
}