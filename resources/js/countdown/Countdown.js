$('#clock').countdown('2020/01/20', function(event) {
  var $this = $(this).html(event.strftime(''
    + '<div><span class="number">%w</span><div class="label">weeks</div></div> '
    + '<div><span class="number">%d</span><div class="label">days</div></div> '
    + '<div><span class="number">%H</span><div class="label">hours</div></div> '
    + '<div><span class="number">%M</span><div class="label">minutes</div></div> '
    + '<div><span class="number">%S</span><div class="label">seconds</div></div>'));
});