$('#delete-modal').on('show.bs.modal', function(e) {
  let url = $(e.relatedTarget).attr('data-url');
  $(this).find('form').attr('action', url);
});

$('#confirm-modal').on('show.bs.modal', function(e) {
  let url = $(e.relatedTarget).attr('data-url');
  let action = $(e.relatedTarget).attr('data-action');

  $(this).find('#form-action').text(action);
  $(this).find('form').attr('action', url);
});

$('[data-toggle="panel"]').on('click', function() {
    let target = $(this).attr('href');
    let parent = $(this).attr('href-parent');
    $(parent).add(target).toggle();
});

$('#share-modal').on('show.bs.modal', function (e) {
  $('[data-toggle="fixed-panel"]').click();
});