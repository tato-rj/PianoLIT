jQuery.fn.cleanVal = function() {
	return this.val().replace(/\D/g,'');
};

jQuery.fn.toggleAttr = function(attr) {
	return this.each(function() {
		var $this = $(this);
		$this.attr(attr) ? $this.removeAttr(attr) : $this.attr(attr, attr);
	});
};

jQuery.fn.textToArray = function() {
return this.map(function(){
     return $.trim($(this).text());
  }).get();
};
