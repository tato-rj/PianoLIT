$('#tags-search .tag').on('click', function() {
  let $btn = $(this);
  let $container = $('#pieces-container');
  let $label = $('#pieces-label');

  $btn.disable().toggleClass('btn-teal');
  $container.parent().addClass('opacity-4');

  axios.get($container.attr('data-url'), {params: {
    'ids': $('#tags-search .btn-teal').attrToArray('data-id'), 
    'names': $('#tags-search .btn-teal').attrToArray('data-name')
  }}).then(function(response) {
      $container.html(response.data.view); 
      $container.parent().removeClass('opacity-4');
      $label.text(response.data.label);
    })
    .catch(function(error) {
      console.log(error);
    })
    .then(function() {
      $btn.enable();
    });
});