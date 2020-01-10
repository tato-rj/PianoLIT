$(document).on('change', 'input.status-toggle',function() {
  let $input = $(this);
  $input.closest('label').css('pointer-events', 'none');

  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(response) {
      console.log(response.status);
      $input.closest('label').css('pointer-events', 'all');
    },
    error: function(xhr,status,error) {
      alert('Something went wrong: ' + error);
    }
  });
});