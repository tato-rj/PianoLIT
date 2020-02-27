class ChartFactory
{	
	constructor(params) {
		this.canvas = params.canvas;
		this.data = params.data;
	}

	get(type) {
		switch (type) {
			case "line": return this.line(); break;
			case "pie": return this.pie(); break;
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
	                padding: 20
	              }
	            }
	        }
	    }); 
	}
}

window.ChartFactory = ChartFactory;
