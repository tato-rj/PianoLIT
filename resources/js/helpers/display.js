jQuery.fn.showAfter = function(time) {
	let element = this;
    setTimeout(function() {
    	if ($('.modal:visible').length == 0)
	    	element.fadeIn('fast');
    }, time * 1000);
};

jQuery.fn.fadeAfter = function(time) {
  let element = this;
    setTimeout(function() {
      element.fadeOut(function() {
        $(this).remove();
      });
    }, time * 1000);
};

jQuery.fn.toggleCssBetween = function(style, options) {
	let element = this;
      if (element.css(style) == options[0]) {
        element.css(style, options[1]);
      } else {
        element.css(style, options[0]);
      }
};

jQuery.fn.toggleSelect = function(classname) {
  this.on('click', function() {
    $(this).toggleClass(classname);  
  });
};