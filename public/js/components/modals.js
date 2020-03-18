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