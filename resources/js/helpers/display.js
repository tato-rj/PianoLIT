jQuery.fn.showAfter = function(time) {
	let element = this;
    setTimeout(function() {element.fadeIn('fast')}, time * 1000);
};