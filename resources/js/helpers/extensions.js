jQuery.fn.visible = function() {
    return this.css('visibility', 'visible');
};

jQuery.fn.invisible = function() {
    return this.css('visibility', 'hidden');
};

jQuery.fn.visibilityToggle = function() {
    return this.css('visibility', function(i, visibility) {
        return (visibility == 'visible') ? 'hidden' : 'visible';
    });
};

jQuery.fn.addLoader = function() {
    let spinner = `<div class="loader-spinner animated fadeIn" style="position: absolute;
                                top: 50%;
                                left: 50%;
                                -webkit-transform: transform: translate(-50%, -50%);
                                transform: translate(-50%, -50%);">
                        <div class="spinner-border opacity-8" style="width: 1rem; height: 1rem; border-width: .16em; margin-bottom: .1rem;"></div>
                    </div>`;
    
    $(this).prop('disabled', true).addClass('position-relative').contents().wrapAll('<div class="invisible"></div>');
    $(this).append(spinner);
};

jQuery.fn.removeLoader = function() {
    $(this).removeClass('position-relative').find('.invisible').removeClass('invisible');
    $(this).prop('disabled', false)
    $(this).find('.loader-spinner').remove();
};


jQuery.fn.sortChildrenBy = function(button) {
    let filter = $(button).data('filter');
    let direction = $(button).val() ? $(button).val() : 'asc';
    let results = this.children();

    results.sort(function(a, b){ 
        let first = $(a).data("sort-" + filter);
        let second = $(b).data("sort-" + filter);

        return direction == 'asc' ? first - second : second - first;
    });

    this.html(results);
};

jQuery.fn.filterableBy = function(elem) {
    let $container = this;
    let $cards = $(this.attr('data-cards'));

    $(elem).on('keyup', function() {
        let needle = $(this).val().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        $cards.unmark();

        if (needle.length > 2) {
            console.log('Find elements with: '+needle);
            $cards.each(function() {
                let haystack = $(this).text().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                if (haystack.includes(needle)) {
                    $(this).show();
                    $(this).mark(needle);
                } else {
                    $(this).hide();
                }
            });
        } else {
            console.log('Show all');
            $cards.show();
        }
    });

    return this;
};

jQuery.fn.cleanVal = function() {
	return this.val().replace(/\D/g,'');
};

jQuery.fn.disable = function() {
    return this.attr('disabled', true);
};

jQuery.fn.enable = function() {
    return this.attr('disabled', false);
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

jQuery.fn.clamp = function(lines) {
    return this.each(function() {
      $clamp(this, {clamp: lines});
    });
};

jQuery.fn.attrToArray = function(attr) {
return this.map(function(){
     return $.trim($(this).attr(attr));
  }).get();
};

jQuery.fn.sortElements = (function(){
    var sort = [].sort;
    return function(comparator, getSortable) {
        getSortable = getSortable || function(){return this;};
        var placements = this.map(function(){
            var sortElement = getSortable.call(this),
                parentNode = sortElement.parentNode,
                // Since the element itself will change position, we have
                // to have some way of storing its original position in
                // the DOM. The easiest way is to have a 'flag' node:
                nextSibling = parentNode.insertBefore(
                    document.createTextNode(''),
                    sortElement.nextSibling
                );
            return function() {
                if (parentNode === this) {
                    throw new Error(
                        "You can't sort elements if any one is a descendant of another."
                    );
                } 
                // Insert before flag:
                parentNode.insertBefore(this, nextSibling);
                // Remove flag:
                parentNode.removeChild(nextSibling);
            }; 
        });
        return sort.call(this, comparator).each(function(i){
            placements[i].call(getSortable.call(this));
        });
    };
})();