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

	line() {
		return new Chart(this.canvas, {
	        type: 'line',
	        data: {
	            labels: this.data.labels,
	            datasets: [
	            {
	                label: this.data.title,
	                data: this.data.records,
	                pointBackgroundColor: convertHex(this.data.colors[0], 5),
	                pointBorderColor: this.data.colors[0],
	                backgroundColor: convertHex(this.data.colors[0], 5),
	                borderColor: this.data.colors[0],
	                borderWidth: 1
	            },
	        ]},
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

	pie() {
	    return new Chart(this.canvas, {
	        type: 'pie',
	        data: {
	            labels: this.data.labels,
	            datasets: [{
	                data: this.data.records,
	                backgroundColor: this.data.colors
	            }]
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
