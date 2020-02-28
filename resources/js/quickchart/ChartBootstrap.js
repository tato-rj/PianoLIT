class ChartBootstrap
{
	constructor(factory) {
		this.factory = factory;
	}

	init() {
		let obj = this;

		$('.chart-btn').on('click', function() {
		    let $button = $(this);
		    let container = $button.attr('data-parent');
		    let chart = $(container).attr('data-chart');

		    obj._selectBtn($button);

		    obj.factory.setup({
		      element: container, 
		      url: obj._buildURL(container)
		    }).make(chart);
		});

		$('.chart-select').on('change', function() {
		    let container = $(this).attr('data-parent');
		    let chart = $(container).attr('data-chart');

		    obj.factory.setup({
		      element: container, 
		      url: obj._buildURL(container)
		    }).make(chart);
		});
	}

	_selectBtn($button) {
	    $button.siblings().removeClass('btn-secondary').addClass('btn-outline-secondary').removeAttr('selected');
	    $button.removeClass('btn-outline-secondary').addClass('btn-secondary').attr('selected', true);
	}

	_buildURL(container) {
	  let params = [];
	  let url = $(container).attr('data-url') + '?';
	  let $form = $(container).find('.chart-btn[selected], .chart-select option:selected');

	  $form.each(function() {
	    params.push({key: $(this).attr('name'), value: $(this).val()});
	  });

	  console.log(params);

	  params.forEach(function(query) {
	    if (query.key != null && query.value != null)
	      url += query.key + '=' + query.value + '&';
	  });

	  return url;
	}
}

window.ChartBootstrap = ChartBootstrap;
