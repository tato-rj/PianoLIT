$('.timeline-btn').on('click', function() {
	$('.timeline-btn').removeClass('btn-teal').addClass('btn-teal-outline');
	$(this).toggleClass('btn-teal btn-teal-outline');
});

$('.collapse').on('hide.bs.collapse', function () {
  let $title = $(this).prev('div');
  $title.find('i').removeClass('icon-chevron-up').addClass('icon-chevron-down');
  $title.find('span small').text('click to show');
});

$('.collapse').on('show.bs.collapse', function () {
  let $title = $(this).prev('div');
  $title.find('i').addClass('icon-chevron-up').removeClass('icon-chevron-down');
  $title.find('span small').text('click to hide');
});