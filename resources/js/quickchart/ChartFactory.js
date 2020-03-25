class ChartFactory
{	
	constructor(params) {
		this.canvas = params.canvas;
		this.data = params.data;
		this.options = params.options;
	}

	get(type) {
		switch (type) {
			case "line": return this.line(); break;
			case "pie": return this.pie(); break;
			case "bar": return this.bar(); break;
		}
	}

	_getLineData() {
		let datasets = [];

		for (i=0; i < this.data.datasets.length; i++) {
			datasets.push({
                label: this.data.datasets[i].title,
                data: this.data.datasets[i].records,
                pointBackgroundColor: convertHex(this.data.datasets[i].colors[i], 5),
                pointBorderColor: this.data.datasets[i].colors[i],
                backgroundColor: convertHex(this.data.datasets[i].colors[i], 5),
                borderColor: this.data.datasets[i].colors[i],
                borderWidth: 1,
			});
		}

		return datasets;
	}

	line() {        
		return new Chart(this.canvas, {
	        type: 'line',
	        data: {
	            labels: this.data.labels,
	            datasets: this._getLineData()
	        },
	        options: {
	        maintainAspectRatio: false,
	            scales: {
	                yAxes: [{
	                    ticks: {
	                        min: 0,
	                        beginAtZero: true,
	                        callback: function(value, index, values) {
	                            if (Math.floor(value) === value) {
	                                return value;
	                            }
	                        }
	                    }
	                }],
	                xAxes: [{
		                ticks: {
		                    display: this.options.hasOwnProperty('xAxis') && this.options.xAxis == 'hide' ? false : true
		                }
		            }]
	            }
	        }
	    });
	}

	_getPieData() {
		let datasets = [];

		this.data.datasets.forEach(function(data) {
			datasets.push({
	                data: data.records,
	                backgroundColor: data.colors
	            });
		});

		return datasets;
	}

	pie() {
	    return new Chart(this.canvas, {
	        type: 'pie',
	        data: {
	            labels: this.data.labels,
	            datasets: this._getPieData()
	        },
	        options: {
	            legend: {
	              display: true,
	              position: 'bottom',
	              labels: {
	                // padding: 20
	              }
	            }
	        }
	    }); 
	}

	_getBarData() {
		let datasets = [];

		this.data.datasets.forEach(function(data) {
			datasets.push({
	                data: data.records,
	                backgroundColor: data.colors
	            });
		});
		
		return datasets;
	}

	bar() {
	    return new Chart(this.canvas, {
	        type: 'bar',
	        data: {
	            labels: this.data.labels,
	            datasets: this._getBarData()
	        },
	        options: {
	            legend: {
	              display: false
	            },
		        maintainAspectRatio: false,
		        scales: {
		            yAxes: [{
		                ticks: {
		                    beginAtZero: true,
		                    stepSize: 20
		                }
		            }],
		            xAxes: [{
		                ticks: {
		                  autoSkip: false
		                }
		            }]
		        },
	        }
	    }); 
	}
}

window.ChartFactory = ChartFactory;
