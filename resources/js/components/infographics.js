$('.infograph-type-btn').on('click', function() {
	let $button = $(this);

	if ($button.hasClass('btn-teal'))
		return;

	let topic = $button.attr('data-topic');
	let $container = $('#infographics-container');

	$container.addClass('opacity-4');
	$button.removeClass('btn-teal-outline').addClass('btn-teal');
	$button.siblings().addClass('btn-teal-outline').removeClass('btn-teal');
	
	axios.get($container.attr('data-url-load'), {params: {topic: topic}})
	.then(function(response) {
		$container.html(response.data);
		$('#pagination-links').hide();
	})
	.catch(function(error) {
		console.log(error.response.data.message);
	})
	.then(function() {
		$container.removeClass('opacity-4');
	});

	$('input#search-infograph').val('');
});

$('input#search-infograph').on('keyup', function() {
	let input = searchable($(this).val());
	let $container = $('#infographics-container');
	let searching = false;

	if (input.length > 2 && ! searching) {
		setTimeout(function() {
			searching = true;
			console.log('Find infographs with: '+input);
			$container.addClass('opacity-4');

			$('#infographs-types').children().addClass('btn-teal-outline').removeClass('btn-teal');
			
			axios.get($container.attr('data-url-search'), {params: {search: input}})
			.then(function(response) {
				$container.html(response.data);
				$('#pagination-links').hide();
			})
			.catch(function(error) {
				console.log(error.response.data.message);
			})
			.then(function() {
				$container.removeClass('opacity-4');
				searching = false;
			});			
		}, 200);
	}
});