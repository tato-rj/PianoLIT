$('table.table-sortable th')
    .wrapInner('<span sorting="true"/>')
    .each(function(){
        var th = $(this),
            table = th.closest('.table-sortable'),
            thIndex = th.index(),
            inverse = false;
        th.click(function(){
            table.find('td').filter(function(){
                return $(this).index() === thIndex;
            }).sortElements(function(a, b){
                if( $.text([a]) == $.text([b]) )
                    return 0;
                return $.text([a]) > $.text([b]) ?
                    inverse ? -1 : 1
                    : inverse ? 1 : -1;
            }, function(){
                // parentNode is the element we want to move
                return this.parentNode; 
            });
            inverse = !inverse;
        });
    });

$('.custom-table .load-more').on('click', function() {
  let $button = $(this);
  let url = $button.attr('data-url');
  let $table = $($button.attr('data-target'));
  let start_at = $table.find('tr').siblings().length;

  $button.text('LOADING...');
  $button.prop('disabled', true);

  $.get(url, {start_at: start_at}, function(data) {
    if (data) {
      $table.find('tbody').append($(data));
      $button.text('LOAD MORE');
    } else {
      $button.text('NO MORE RESULTS');
    }
    $button.prop('disabled', false);
  })
  .fail(function() {
    $button.text('SORRY, WE COULDN\'T LOAD MORE RESULTS');
  });
});