class DataTable
{
	constructor(table) {
		this.$table = $(table);
		this.sortBy = [ 0, "desc" ];
	}

	columns(array) {
		let obj = this;
		obj.columns = array;

		array.some( function(element, index) {
			if (element.hasOwnProperty('sort')) {
				obj.sortBy = [ index, "desc" ];
				return true;
			}
		});

		return this;
	}

	create() {
		let obj = this;
		console.log('Sorting by column ' + obj.sortBy);
	    obj.$table.DataTable({
	        processing: true,
	        serverSide: true,
	        aaSorting: obj.sortBy,
	        language: {
	          processing: `
	          <div class="d-flex flex-center w-100 h-100">
		          <div class="spinner-border" role="status">
		          	<span class="sr-only">Loading...</span>
		          </div>
	          </div>`
	        },
	        ajax: window.location.href,
	        columns: obj.columns,
	        initComplete: function(settings, json) {
	          obj.$table.find('thead').removeClass('invisible');
	        }
	    });
	}

	dontSort() {
		this.sortBy = [];

		return this;	
	}
}

window.DataTable = DataTable;
