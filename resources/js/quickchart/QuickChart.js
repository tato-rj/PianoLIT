class QuickChart
{
	constructor() {
		this.instances = [];
		new ChartBootstrap(this).init();
	}

	setup(params) {
		this.elem = params.element;
		this.url = params.url;

		return this;
	}

	make(type) {
		let obj = this;
		let $canvas = $(obj.elem).find('canvas');

		obj._loading($canvas, true);

		$.get(obj.url, function(data) {
		    obj._destroy($canvas);
		    obj._save($canvas, (new ChartFactory({canvas: $canvas, data: data})).get(type));
		    obj._loading($canvas, false);
		});
	}

	_save($canvas, chart) {
		this.instances[$canvas.attr('id')] = chart;
	}

	_destroy($canvas) {
	    let chart = this.instances[$canvas.attr('id')];

	    if (chart) {
	        chart.destroy();
	        console.log('The canvas has been destroyed.');
	    }
	}

	_loading($canvas, bool) {
		if (bool) {
			$canvas.addClass('opacity-4');
		} else {
			$canvas.removeClass('opacity-4');
		}
	}
}

window.QuickChart = QuickChart;
