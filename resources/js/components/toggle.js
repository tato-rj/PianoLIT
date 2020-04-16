$(document).on('change', 'input.status-toggle',function() {
  let $input = $(this);
  $input.closest('label').css('pointer-events', 'none');

  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(response) {
      $('.alert-container').remove();

      $('body').append(response);
      
      setTimeout(function() {
        $('.alert-temporary').fadeOut(function() {
          $(this).remove();
        });
      }, 2000);
      $input.closest('label').css('pointer-events', 'all');
    },
    error: function(xhr,status,error) {
      alert('Something went wrong: ' + error);
    }
  });
});