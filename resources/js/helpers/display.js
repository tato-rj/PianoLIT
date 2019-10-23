jQuery.fn.showAfter = function(time) {
	let element = this;
    setTimeout(function() {element.fadeIn('fast')}, time * 1000);
};

jQuery.fn.toggleCssBetween = function(style, options) {
	let element = this;
      if (element.css(style) == options[0]) {
        element.css(style, options[1]);
      } else {
        element.css(style, options[0]);
      }
};