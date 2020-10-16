$(document).on('submit', 'form[disable-on-submit]', function() {
	let spinner = '<div class="spinner-border mr-1 opacity-6" style="margin-bottom: .13rem; width: 1rem; height: 1rem; border-width: .16em" role="status"></div>';
	let $button = $(this).find('button[type="submit"]');

	$button.prop('disabled', true);
	$button.prepend(spinner);
});